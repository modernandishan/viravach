<?php

namespace App\Ai\Input;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches the user's own website and reduces it to readable plain text as
 * OPTIONAL context for AI content generation. Network-facing by nature, so
 * it must never break the pipeline: every failure mode (bad URL, timeout,
 * connection error, non-2xx, non-HTML, oversized or near-empty body)
 * degrades to null, never an exception.
 *
 * Deliberately separate from CompanyInputCollector: the extracted text is
 * NOT part of the input payload or its hash, so an edit on the user's site
 * never invalidates an already generated payload.
 */
class WebsiteTextExtractor
{
    /**
     * Responses larger than this are rejected before parsing.
     */
    private const MAX_BYTES = 2 * 1024 * 1024;

    /**
     * Hard ceiling on the text handed to the model.
     */
    private const MAX_CHARS = 6000;

    /**
     * A JS-only or placeholder page yields less than this and is worse than
     * no context at all.
     */
    private const MIN_CHARS = 200;

    private const CACHE_KEY_PREFIX = 'ai.website-text.';

    /**
     * Cache::get's "key absent" default. Cache::remember/get treat a stored
     * null as a miss, so null itself can't be cached — a fetched-but-failed
     * result is stored as the CACHE_NONE sentinel instead and mapped back to
     * null on read, giving failures a genuine 1-hour rest while successes
     * rest for a full day.
     */
    private const CACHE_MISS = '__miss__';

    private const CACHE_NONE = '__none__';

    public function extract(?string $url): ?string
    {
        if ($url === null || trim($url) === '' || ! $this->isValidHttpUrl($url)) {
            return null;
        }

        $key = self::CACHE_KEY_PREFIX.sha1($this->normalize($url));

        $cached = Cache::get($key, self::CACHE_MISS);

        if ($cached !== self::CACHE_MISS) {
            return $cached === self::CACHE_NONE ? null : $cached;
        }

        $text = $this->fetchAndParse($url);

        // Keep the asymmetry: a failed read is retried after an hour, a
        // successful one rests for a full day.
        Cache::put(
            $key,
            $text ?? self::CACHE_NONE,
            $text === null ? now()->addHour() : now()->addDay(),
        );

        return $text;
    }

    private function fetchAndParse(string $url): ?string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        try {
            $response = Http::timeout(8)
                ->connectTimeout(4)
                ->maxRedirects(3)
                ->withUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36')
                ->accept('*/*')
                ->get($url);
        } catch (Throwable $e) {
            Log::warning('Website text extraction: request failed.', [
                'host' => $host,
                'exception' => $e::class,
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Website text extraction: non-successful response.', [
                'host' => $host,
                'status' => $response->status(),
            ]);

            return null;
        }

        // Only reject when a content type is actually present and clearly
        // not HTML — many real servers omit the header entirely.
        $contentType = trim((string) $response->header('Content-Type'));

        if ($contentType !== '' && ! str_contains(strtolower($contentType), 'text/html')) {
            Log::warning('Website text extraction: non-HTML content type.', [
                'host' => $host,
                'content_type' => $contentType,
            ]);

            return null;
        }

        $body = $response->body();

        if ($body === '' || strlen($body) > self::MAX_BYTES) {
            Log::warning('Website text extraction: empty or oversized body.', ['host' => $host]);

            return null;
        }

        return $this->toPlainText($body);
    }

    /**
     * symfony/dom-crawler is not installed, so this stays regex + strip_tags:
     * drop the boilerplate blocks entirely, strip the rest, then normalize
     * whitespace while keeping paragraph breaks.
     */
    private function toPlainText(string $html): ?string
    {
        $html = (string) preg_replace('#<(script|style|noscript|svg|nav|footer|header)\b[^>]*>.*?</\1>#is', ' ', $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = (string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = (string) preg_replace('/\R+/u', "\n", $text);

        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn (string $line) => $line !== '',
        ));
        $text = implode("\n", $lines);

        if (mb_strlen($text) < self::MIN_CHARS) {
            return null;
        }

        if (mb_strlen($text) <= self::MAX_CHARS) {
            return $text;
        }

        $text = mb_substr($text, 0, self::MAX_CHARS);

        // Truncate on a word boundary: drop the partially cut trailing word.
        $text = (string) preg_replace('/\s+\S*$/u', '', $text);

        return rtrim($text);
    }

    private function isValidHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && is_string($host) && $host !== '';
    }

    /**
     * Scheme/host casing and trailing slashes don't change the resource, so
     * the cache key must not see them either.
     */
    private function normalize(string $url): string
    {
        $url = rtrim(trim($url), '/');

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return preg_replace('#^'.preg_quote($scheme, '#').'://'.preg_quote($host, '#').'#i', $scheme.'://'.$host, $url);
    }
}
