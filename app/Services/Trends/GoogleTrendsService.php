<?php

namespace App\Services\Trends;

use App\Models\Company;
use App\Models\WordPressContentPost;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches currently-trending search queries for a company's market from
 * Google's own public "Daily Search Trends" RSS feed
 * (trends.google.com/trending/rss?geo={geo}) — no official Google Trends
 * API exists, and this feed needs no account or key, unlike a paid
 * third-party provider.
 *
 * Trade-off: the feed is country-wide, not filtered to any industry, so
 * unlike a keyword-seeded provider it will hand back general-interest
 * queries (a football club, a TV episode) alongside anything genuinely
 * relevant to the company's field. Rather than guessing relevance here,
 * ALL surviving candidates are handed to the content-generation prompt
 * (see WordPressPostPrompt::user()), and the model itself picks the one
 * with a real connection to the company — or explicitly writes a
 * specialised-style article instead of forcing a weak one.
 *
 * Repeats are avoided per company by checking candidates against the
 * topics this company has already published (wordpress_content_posts), so
 * no extra ledger is needed. If every candidate has been used, the least
 * recently used one is reused rather than failing the generation.
 */
class GoogleTrendsService
{
    protected const TIMEOUT = 15;

    /** How many surviving candidates are offered to the content prompt. */
    protected const MAX_CANDIDATES = 8;

    /**
     * Locale → Google Trends geo code. The site's five supported locales
     * each map to the market that locale's readers are actually in — the
     * same country association the DataForSEO location codes this replaces
     * used to encode, just expressed as the plain ISO country code this
     * feed expects instead of a provider-specific numeric id.
     *
     * @var array<string, string>
     */
    protected const LOCALE_GEO = [
        'fa' => 'IR',
        'en' => 'US',
        'tr' => 'TR',
        'ru' => 'RU',
        'ar' => 'AE',
    ];

    public function pickTopic(Company $company, string $locale): TrendTopicResult
    {
        $geo = self::LOCALE_GEO[$locale] ?? 'US';

        $items = $this->fetchFeed($geo, $company);

        if ($items instanceof TrendFailureReason) {
            return TrendTopicResult::failed($items);
        }

        return $this->choose($company, $geo, $items);
    }

    /**
     * @return list<string>|TrendFailureReason
     */
    protected function fetchFeed(string $geo, Company $company): array|TrendFailureReason
    {
        $response = $this->get('https://trends.google.com/trending/rss', ['geo' => $geo]);

        if ($response === null || $response->failed()) {
            Log::warning('Google Trends feed request failed.', [
                'company_id' => $company->id,
                'geo' => $geo,
                'status' => $response?->status(),
            ]);

            return TrendFailureReason::RequestFailed;
        }

        $titles = $this->parseFeed($response->body());

        if ($titles === null) {
            Log::warning('Google Trends feed returned unparseable XML.', [
                'company_id' => $company->id,
                'geo' => $geo,
            ]);

            return TrendFailureReason::FeedUnavailable;
        }

        if ($titles === []) {
            return TrendFailureReason::NoCandidates;
        }

        return $titles;
    }

    /**
     * Parses the feed's <item> entries into a list of candidate titles.
     * Each item also carries ht:approx_traffic and pubDate, but nothing
     * downstream needs them — only the title is a candidate topic.
     *
     * @return list<string>|null Null means the body was not valid XML.
     */
    protected function parseFeed(string $body): ?array
    {
        $previousSetting = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($body);
        } catch (Throwable) {
            $xml = false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);
        }

        if ($xml === false || ! isset($xml->channel)) {
            return null;
        }

        $titles = [];

        foreach ($xml->channel->item as $item) {
            $title = trim((string) $item->title);

            if ($title !== '') {
                $titles[] = $title;
            }
        }

        return array_values(array_unique($titles));
    }

    /**
     * Normalizes and dedupes candidates against this company's own history,
     * then picks one — this selection algorithm is unchanged from the
     * DataForSEO-backed version it replaces. What is new is that the
     * survivors it did NOT pick are also returned as $candidates, so the
     * content-generation prompt can choose relevance-wise among all of them
     * instead of trusting this arbitrary pick alone.
     *
     * @param  list<string>  $candidates
     */
    protected function choose(Company $company, string $geo, array $candidates): TrendTopicResult
    {
        /** @var array<string, string> $byNormalized */
        $byNormalized = [];

        foreach ($candidates as $candidate) {
            $normalized = WordPressContentPost::normalizeTopic($candidate);

            if ($normalized !== '') {
                $byNormalized[$normalized] ??= $candidate;
            }
        }

        if ($byNormalized === []) {
            return TrendTopicResult::failed(TrendFailureReason::NoCandidates);
        }

        $used = WordPressContentPost::query()
            ->where('company_id', $company->id)
            ->whereIn('topic_normalized', array_keys($byNormalized))
            ->pluck('topic_normalized')
            ->all();

        $unused = array_diff_key($byNormalized, array_flip($used));

        if ($unused !== []) {
            $normalized = (string) array_rand($unused);

            return TrendTopicResult::picked(
                $unused[$normalized],
                $normalized,
                $geo,
                candidates: array_slice(array_values($unused), 0, self::MAX_CANDIDATES),
            );
        }

        // Everything on offer has been written about before: reuse the one
        // that has gone longest without an article rather than refusing to
        // generate at all. The full set is still offered to the prompt —
        // "already used" does not mean "no longer a legitimate topic".
        $leastRecent = WordPressContentPost::query()
            ->where('company_id', $company->id)
            ->whereIn('topic_normalized', array_keys($byNormalized))
            ->selectRaw('topic_normalized, MAX(created_at) as last_used_at')
            ->groupBy('topic_normalized')
            ->orderBy('last_used_at')
            ->first();

        $normalized = (string) ($leastRecent?->topic_normalized ?? array_key_first($byNormalized));

        return TrendTopicResult::picked(
            $byNormalized[$normalized] ?? reset($byNormalized),
            $normalized,
            $geo,
            reused: true,
            candidates: array_slice(array_values($byNormalized), 0, self::MAX_CANDIDATES),
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function get(string $url, array $query = []): ?Response
    {
        try {
            return Http::timeout(self::TIMEOUT)->get($url, $query);
        } catch (Throwable $exception) {
            Log::warning('Google Trends feed request threw an exception.', [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
