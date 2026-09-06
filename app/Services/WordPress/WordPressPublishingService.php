<?php

namespace App\Services\WordPress;

use App\Enums\SeoPlugin;
use App\Models\Company;
use App\Models\WordPressContentPost;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Publishes one generated article to the company's own WordPress site over
 * the REST API, authenticated with the same Application Password the
 * connection test already proved (RFC 7617 Basic Auth).
 *
 * Three calls, in order:
 *
 *  1. POST /wp/v2/media — the featured image, sent as a raw body with a
 *     Content-Disposition filename, because the create request's body IS the
 *     file. Alt text therefore needs a second call.
 *  2. POST /wp/v2/media/{id} — the SEO alt text.
 *  3. POST /wp/v2/posts — title, content, excerpt, slug, featured_media, and
 *     the SEO plugin's meta keys.
 *
 * SEO META CAVEAT: neither plugin accepts these over REST out of the box.
 * Yoast's REST API is documented read-only, and Rank Math's keys are not
 * registered with show_in_rest, so WordPress answers 200 and silently drops
 * them. They are sent anyway and the post is read back to see whether they
 * survived; the answer is recorded on the company so the dashboard can tell
 * the owner their site needs a helper plugin for full SEO control.
 */
class WordPressPublishingService
{
    protected const TIMEOUT = 30;

    /**
     * Posts go live immediately — the feature is monthly auto-publishing.
     * Change to 'draft' to have owners review each article first.
     */
    protected const POST_STATUS = 'publish';

    /**
     * The SEO plugins' post-meta keys. Yoast's are its internal underscored
     * keys; Rank Math's REST form drops the leading underscore.
     *
     * @var array<string, array<string, string>>
     */
    protected const SEO_META_KEYS = [
        SeoPlugin::Yoast->value => [
            'title' => '_yoast_wpseo_title',
            'description' => '_yoast_wpseo_metadesc',
            'focus_keyword' => '_yoast_wpseo_focuskw',
        ],
        SeoPlugin::RankMath->value => [
            'title' => 'rank_math_title',
            'description' => 'rank_math_description',
            'focus_keyword' => 'rank_math_focus_keyword',
        ],
    ];

    /**
     * @param  string|null  $imageBytes  The featured image; null skips the upload.
     */
    public function publish(WordPressContentPost $post, ?string $imageBytes): WordPressPublishResult
    {
        $company = $post->company;
        $site = $this->siteUrl($company);
        $username = trim((string) $company->wp_username);
        $password = trim((string) $company->wp_application_password);

        if ($site === null || $username === '' || $password === '') {
            return WordPressPublishResult::failed(WordPressPublishFailureReason::IncompleteSettings);
        }

        $mediaId = null;

        if ($imageBytes !== null) {
            $mediaResult = $this->uploadFeaturedImage($post, $site, $username, $password, $imageBytes);

            if ($mediaResult instanceof WordPressPublishResult) {
                return $mediaResult;
            }

            $mediaId = $mediaResult;
        }

        return $this->createPost($post, $site, $username, $password, $mediaId);
    }

    /**
     * @return int|WordPressPublishResult The attachment id, or the failure to return.
     */
    protected function uploadFeaturedImage(
        WordPressContentPost $post,
        string $site,
        string $username,
        string $password,
        string $bytes,
    ): int|WordPressPublishResult {
        $filename = Str::slug(Str::limit($post->title ?: $post->topic, 60, '')).'.png';
        $filename = $filename === '.png' ? 'featured-image.png' : $filename;

        try {
            $response = $this->request($username, $password)
                ->withHeaders([
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    'Content-Type' => 'image/png',
                ])
                ->withBody($bytes, 'image/png')
                ->post($site.'/wp-json/wp/v2/media');
        } catch (Throwable $exception) {
            $this->logException($post, 'media upload', $exception);

            return WordPressPublishResult::failed(WordPressPublishFailureReason::Unreachable);
        }

        if ($failure = $this->authFailure($response)) {
            return WordPressPublishResult::failed($failure);
        }

        $mediaId = $response->json('id');

        if ($response->failed() || ! is_int($mediaId)) {
            $this->logResponse($post, 'media upload', $response);

            return WordPressPublishResult::failed(WordPressPublishFailureReason::MediaUploadFailed);
        }

        // Alt text has to be a second call: the upload request's body was
        // the image itself, so there was nowhere to put it.
        if (filled($post->image_alt)) {
            $alt = $this->post(
                $site.'/wp-json/wp/v2/media/'.$mediaId,
                $username,
                $password,
                ['alt_text' => $post->image_alt],
            );

            if ($alt === null || $alt->failed()) {
                // The image is already attached and usable; missing alt text
                // is an SEO regression, not a reason to abandon the article.
                Log::warning('WordPress featured image alt text could not be set.', [
                    'post_id' => $post->id,
                    'media_id' => $mediaId,
                    'status' => $alt?->status(),
                ]);
            }
        }

        return $mediaId;
    }

    protected function createPost(
        WordPressContentPost $post,
        string $site,
        string $username,
        string $password,
        ?int $mediaId,
    ): WordPressPublishResult {
        $metaKeys = $this->seoMetaKeys($post->company);

        $payload = array_filter([
            'title' => $post->title,
            'content' => $post->body,
            'excerpt' => $post->excerpt,
            'slug' => Str::slug(Str::limit((string) $post->title, 60, '')) ?: null,
            'status' => self::POST_STATUS,
            'featured_media' => $mediaId,
            'meta' => $this->seoMetaPayload($post, $metaKeys),
        ], fn ($value): bool => $value !== null && $value !== [] && $value !== '');

        $response = $this->post($site.'/wp-json/wp/v2/posts', $username, $password, $payload);

        if ($response === null) {
            return WordPressPublishResult::failed(WordPressPublishFailureReason::Unreachable);
        }

        if ($failure = $this->authFailure($response)) {
            return WordPressPublishResult::failed($failure);
        }

        $wpPostId = $response->json('id');

        if ($response->failed() || ! is_int($wpPostId)) {
            $this->logResponse($post, 'post creation', $response);

            return WordPressPublishResult::failed(WordPressPublishFailureReason::PostCreationFailed);
        }

        return WordPressPublishResult::published(
            postId: $wpPostId,
            postUrl: is_string($response->json('link')) ? $response->json('link') : null,
            mediaId: $mediaId,
            seoMetaWritable: $this->confirmSeoMeta($site, $username, $password, $wpPostId, $metaKeys),
        );
    }

    /**
     * Reads the created post back to see whether the SEO plugin's meta keys
     * were stored or silently dropped. Null when there is nothing to check
     * or the read failed — an unknown answer must not be recorded as a no.
     *
     * @param  array<string, string>  $metaKeys
     */
    protected function confirmSeoMeta(
        string $site,
        string $username,
        string $password,
        int $wpPostId,
        array $metaKeys,
    ): ?bool {
        if ($metaKeys === []) {
            return null;
        }

        $response = $this->get(
            $site.'/wp-json/wp/v2/posts/'.$wpPostId,
            $username,
            $password,
            ['context' => 'edit'],
        );

        if ($response === null || $response->failed()) {
            return null;
        }

        $meta = $response->json('meta');

        if (! is_array($meta)) {
            return false;
        }

        return filled($meta[$metaKeys['title']] ?? null);
    }

    /**
     * @return array<string, string> Empty when the company selected no plugin.
     */
    protected function seoMetaKeys(Company $company): array
    {
        return self::SEO_META_KEYS[$company->seo_plugin?->value] ?? [];
    }

    /**
     * @param  array<string, string>  $metaKeys
     * @return array<string, string>
     */
    protected function seoMetaPayload(WordPressContentPost $post, array $metaKeys): array
    {
        if ($metaKeys === []) {
            return [];
        }

        return array_filter([
            $metaKeys['title'] => (string) $post->title,
            $metaKeys['description'] => (string) $post->excerpt,
            $metaKeys['focus_keyword'] => (string) $post->focus_keyword,
        ], fn (string $value): bool => $value !== '');
    }

    protected function authFailure(Response $response): ?WordPressPublishFailureReason
    {
        if ($response->unauthorized()) {
            return WordPressPublishFailureReason::AuthenticationFailed;
        }

        // 403 after a successful connection test means the account exists but
        // may not publish or upload — a different fix from bad credentials.
        if ($response->forbidden()) {
            return WordPressPublishFailureReason::NotPermitted;
        }

        return null;
    }

    protected function siteUrl(Company $company): ?string
    {
        $website = rtrim(trim((string) $company->website), '/');

        return $website !== '' ? $website : null;
    }

    protected function request(string $username, string $password): PendingRequest
    {
        return Http::timeout(self::TIMEOUT)
            ->withBasicAuth($username, $password)
            ->acceptJson();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function post(string $url, string $username, string $password, array $payload): ?Response
    {
        try {
            return $this->request($username, $password)->asJson()->post($url, $payload);
        } catch (Throwable $exception) {
            Log::warning('WordPress publish request threw an exception.', [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function get(string $url, string $username, string $password, array $query = []): ?Response
    {
        try {
            return $this->request($username, $password)->get($url, $query);
        } catch (Throwable $exception) {
            Log::warning('WordPress publish read-back threw an exception.', [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function logResponse(WordPressContentPost $post, string $stage, Response $response): void
    {
        Log::warning('WordPress publishing failed during '.$stage.'.', [
            'post_id' => $post->id,
            'company_id' => $post->company_id,
            'status' => $response->status(),
            'body' => Str::limit($response->body(), 500),
        ]);
    }

    protected function logException(WordPressContentPost $post, string $stage, Throwable $exception): void
    {
        Log::warning('WordPress publishing threw during '.$stage.'.', [
            'post_id' => $post->id,
            'company_id' => $post->company_id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
