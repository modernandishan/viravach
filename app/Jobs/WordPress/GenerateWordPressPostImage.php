<?php

namespace App\Jobs\WordPress;

use App\Ai\ImageGenerator;
use App\Ai\Prompts\WordPressPostImagePrompt;
use App\Models\WordPressContentPost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Step 2: generates the one featured image for the article and attaches it
 * to the post via medialibrary, reusing the temp-file/addMedia pattern from
 * GenerateFeaturedImage. Image generation is optional (content.image_enabled)
 * — when it is off, publishing proceeds without a featured image rather than
 * failing the whole article over a disabled setting.
 */
class GenerateWordPressPostImage extends AbstractWordPressPostJob
{
    public const STEP = 2;

    public $timeout = 180;

    protected function run(WordPressContentPost $post): void
    {
        $settings = $this->settings();

        if (! $settings->image_enabled || blank($post->image_alt)) {
            return;
        }

        $company = $post->company;
        $category = $company->categories()->first();
        $industry = $category?->getTranslation('title', $post->locale, true);

        $prompt = WordPressPostImagePrompt::build((string) $post->title, (string) $post->image_alt, $industry);

        $bytes = app(ImageGenerator::class)->generate($prompt);

        $tempPath = sys_get_temp_dir().'/wp-post-image-'.Str::uuid()->toString().'.png';
        file_put_contents($tempPath, $bytes);

        try {
            $media = $post->addMedia($tempPath)
                ->preservingOriginal()
                ->usingFileName('wp-post-'.$post->id.'-featured-image.png')
                ->toMediaCollection('featured_image', 's3');

            $media->setCustomProperty('alt', $post->image_alt);
            $media->save();
        } finally {
            @unlink($tempPath);
        }

        Log::info('WordPress post featured image generated.', ['post_id' => $post->id]);
    }
}
