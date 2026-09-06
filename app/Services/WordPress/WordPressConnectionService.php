<?php

namespace App\Services\WordPress;

use App\Enums\WordPressConnectionStatus;
use App\Models\Company;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifies that a company's stored WordPress credentials really work against
 * its own site, before anything is ever published to it.
 *
 * The check runs in three steps so a failure can be reported precisely:
 *
 *  1. GET /wp-json/ — unauthenticated. Confirms the URL is a WordPress REST
 *     API root at all, so Basic Auth credentials are never sent to a random
 *     host that merely happens to answer on that domain.
 *  2. GET /wp-json/wp/v2/users/me — Basic Auth with the WordPress username
 *     and the Application Password, which WordPress accepts over RFC 7617
 *     Basic Auth exactly as its own documentation demonstrates.
 *  3. GET /wp-json/wp/v2/posts — the site's most recent posts, shown back to
 *     the owner as concrete proof that we reached *their* site.
 */
class WordPressConnectionService
{
    /** Seconds to wait on any single request to the owner's site. */
    protected const TIMEOUT = 10;

    /** How many recent posts are shown back as identity confirmation. */
    protected const POSTS_TO_FETCH = 3;

    /**
     * Runs the test and records its outcome on the company, so the settings
     * page can keep showing the confirmation on reload without testing again.
     *
     * Nothing is recorded when the settings are still incomplete: no request
     * was made, so the stored status stays whatever it was.
     */
    public function testConnection(Company $company): WordPressConnectionResult
    {
        $result = $this->probe($company);

        if ($result->reason !== WordPressConnectionFailureReason::IncompleteSettings) {
            $this->record($company, $result);
        }

        return $result;
    }

    protected function probe(Company $company): WordPressConnectionResult
    {
        $site = $this->siteUrl($company);
        $username = trim((string) $company->wp_username);
        $password = trim((string) $company->wp_application_password);

        if ($site === null || $username === '' || $password === '') {
            return WordPressConnectionResult::failed(WordPressConnectionFailureReason::IncompleteSettings);
        }

        $root = $this->get($site.'/wp-json/');

        if ($root === null) {
            return WordPressConnectionResult::failed(WordPressConnectionFailureReason::Unreachable);
        }

        if (! $this->looksLikeWordPress($root)) {
            return WordPressConnectionResult::failed(WordPressConnectionFailureReason::NotWordPress);
        }

        $identity = $this->get($site.'/wp-json/wp/v2/users/me', $username, $password, ['context' => 'edit']);

        if ($identity === null) {
            return WordPressConnectionResult::failed(WordPressConnectionFailureReason::Unreachable);
        }

        // WordPress answers a bad username or a revoked application password
        // with 401, and a blocked/insufficient account with 403.
        if ($identity->unauthorized() || $identity->forbidden()) {
            return WordPressConnectionResult::failed(WordPressConnectionFailureReason::AuthenticationFailed);
        }

        if ($identity->failed() || ! is_int($identity->json('id'))) {
            Log::warning('WordPress identity request returned an unexpected response.', [
                'company_id' => $company->id,
                'status' => $identity->status(),
                'body' => $identity->body(),
            ]);

            return WordPressConnectionResult::failed(WordPressConnectionFailureReason::RequestFailed);
        }

        return WordPressConnectionResult::connected(
            siteName: $this->plainText($root->json('name')),
            siteDescription: $this->plainText($root->json('description')),
            userName: $this->plainText($identity->json('name')),
            userLogin: $this->plainText($identity->json('username')) ?? $username,
            posts: $this->recentPosts($company, $site, $username, $password),
        );
    }

    /**
     * The REST API root advertises every registered namespace; a WordPress
     * site always exposes `wp/v2`, which no unrelated JSON endpoint does.
     */
    protected function looksLikeWordPress(Response $response): bool
    {
        if ($response->failed()) {
            return false;
        }

        $namespaces = $response->json('namespaces');

        return is_array($namespaces) && in_array('wp/v2', $namespaces, true);
    }

    /**
     * The credentials are already proven at this point, so a posts request
     * that fails does not fail the whole test — the identity confirmation is
     * simply shown without a post list.
     *
     * @return list<array{title: string, link: string, date: string|null}>
     */
    protected function recentPosts(Company $company, string $site, string $username, string $password): array
    {
        $response = $this->get($site.'/wp-json/wp/v2/posts', $username, $password, [
            'per_page' => self::POSTS_TO_FETCH,
            '_embed' => 1,
        ]);

        $posts = $response?->json();

        if ($response === null || $response->failed() || ! is_array($posts)) {
            Log::warning('WordPress credentials verified, but the recent posts could not be read.', [
                'company_id' => $company->id,
                'status' => $response?->status(),
            ]);

            return [];
        }

        return collect($posts)
            ->filter(fn ($post): bool => is_array($post))
            ->take(self::POSTS_TO_FETCH)
            ->map(fn (array $post): array => [
                'title' => $this->plainText(data_get($post, 'title.rendered')) ?? '',
                'link' => $this->plainText(data_get($post, 'link')) ?? '',
                'date' => $this->publishedAt($post),
            ])
            ->values()
            ->all();
    }

    /**
     * Normalised to ISO-8601 (or dropped) here rather than at render time:
     * an unparseable date from a remote site must not be able to break the
     * settings page on every later page load.
     */
    protected function publishedAt(array $post): ?string
    {
        $date = $this->plainText(data_get($post, 'date_gmt')) ?? $this->plainText(data_get($post, 'date'));

        if ($date === null) {
            return null;
        }

        try {
            return Carbon::parse($date)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }

    protected function record(Company $company, WordPressConnectionResult $result): void
    {
        $company->forceFill([
            'wp_connection_status' => $result->successful
                ? WordPressConnectionStatus::Connected
                : WordPressConnectionStatus::Failed,
            'wp_last_checked_at' => now(),
            // A failed re-test drops the previous post list: stale posts next
            // to a failure badge would read as a confirmation that no longer
            // holds.
            'wp_last_posts' => $result->successful ? $result->posts : null,
        ])->save();
    }

    /**
     * The website is stored with its https:// scheme by the settings form;
     * only the trailing slash has to go so the REST paths append cleanly.
     */
    protected function siteUrl(Company $company): ?string
    {
        $website = rtrim(trim((string) $company->website), '/');

        return $website !== '' ? $website : null;
    }

    /**
     * WordPress returns titles and site names as rendered HTML, entities
     * included; the dashboard shows them as plain text.
     */
    protected function plainText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text !== '' ? $text : null;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function get(string $url, ?string $username = null, ?string $password = null, array $query = []): ?Response
    {
        $request = Http::timeout(self::TIMEOUT)->acceptJson();

        if ($username !== null && $password !== null) {
            $request = $request->withBasicAuth($username, $password);
        }

        try {
            return $request->get($url, $query);
        } catch (Throwable $exception) {
            Log::warning('WordPress connection request threw an exception.', [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
