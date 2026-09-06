<?php

namespace App\Jobs\WordPress;

use App\Ai\ContentGenerator;
use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\Input\CompanyInputCollector;
use App\Ai\Prompts\WordPressPostPrompt;
use App\Ai\Schemas\WordPressPostSchema;
use App\Enums\ContentGenerationMode;
use App\Models\WordPressContentPost;
use App\Services\Trends\GoogleTrendsService;
use Illuminate\Support\Facades\Log;

/**
 * Step 1: writes the article — title, excerpt, body, focus keyword and
 * image alt text — for one WordPress post.
 */
class GenerateWordPressPostContent extends AbstractWordPressPostJob
{
    public const STEP = 1;

    public $timeout = 600;

    protected function run(WordPressContentPost $post): void
    {
        $company = $post->company;
        $input = app(CompanyInputCollector::class)->collect($company);

        $system = WordPressPostPrompt::system($post->locale);
        $user = WordPressPostPrompt::user(
            $input,
            $post->topic,
            $post->mode,
            $this->trendCandidates($post),
        );

        $payload = app(ContentGenerator::class)->complete($system, $user, $this->settings()->translation_model);

        $errors = WordPressPostSchema::validate($payload);

        if ($errors !== []) {
            Log::warning('WordPress post generation returned an invalid payload.', [
                'post_id' => $post->id,
                'errors' => $errors,
            ]);

            throw new ContentGenerationException('The generated article did not match the required shape: '.implode(' ', $errors));
        }

        $post->forceFill([
            'title' => $payload['title'],
            'excerpt' => $payload['excerpt'],
            'focus_keyword' => $payload['focus_keyword'],
            'body' => $payload['body'],
            'image_alt' => $payload['image_alt'],
        ])->save();
    }

    /**
     * A fresh shortlist for the prompt, fetched again rather than reusing
     * whatever WordPressContentGenerationService saw at dispatch time — by
     * the time this job runs the feed may have moved on, and re-checking
     * here also re-applies the used/reused history exactly at generation
     * time. Feed trouble at this point must not fail an otherwise generated
     * article, so any failure here degrades to no candidates: the prompt
     * then falls back to $post->topic alone, same as before this feature.
     *
     * @return list<string>
     */
    protected function trendCandidates(WordPressContentPost $post): array
    {
        if ($post->mode !== ContentGenerationMode::Trending) {
            return [];
        }

        $result = app(GoogleTrendsService::class)->pickTopic($post->company, $post->locale);

        return $result->successful ? $result->candidates : [];
    }
}
