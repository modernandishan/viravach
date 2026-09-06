<?php

namespace App\Jobs\WordPress;

use App\Enums\WordPressPostStatus;
use App\Models\WordPressContentPost;
use App\Services\WordPress\WordPressPublishingService;
use Illuminate\Support\Facades\Http;

/**
 * Step 3: uploads the featured image (if one was generated) and creates the
 * post on the company's own WordPress site.
 *
 * Expected failures (bad credentials, unreachable site, WordPress rejecting
 * the post) are recorded as a Failed status via fail() — not thrown — since
 * they are the WordPress connection's problem, not this job's.
 */
class PublishWordPressPost extends AbstractWordPressPostJob
{
    public const STEP = 3;

    public $timeout = 120;

    protected function run(WordPressContentPost $post): void
    {
        $post->loadMissing('company');

        $imageBytes = null;
        $media = $post->getFirstMedia('featured_image');

        if ($media !== null) {
            $imageBytes = Http::timeout(30)->get($media->getUrl())->body();
        }

        $result = app(WordPressPublishingService::class)->publish($post, $imageBytes);

        if (! $result->successful) {
            $this->fail($post, 'wordpress_content.publish_failed_'.$result->reason?->value);

            return;
        }

        $post->forceFill([
            'status' => WordPressPostStatus::Published,
            'wp_post_id' => $result->postId,
            'wp_post_url' => $result->postUrl,
            'wp_media_id' => $result->mediaId,
            'published_at' => now(),
        ])->save();

        if ($result->seoMetaWritable !== null) {
            $post->company->forceFill(['wp_seo_meta_writable' => $result->seoMetaWritable])->save();
        }
    }
}
