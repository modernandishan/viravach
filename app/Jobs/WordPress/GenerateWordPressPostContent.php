<?php

namespace App\Jobs\WordPress;

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

        // Trend articles are standalone: no company context is collected or
        // sent, so the model physically cannot bridge the topic to the
        // company's industry (the prompt also forbids it — belt and braces).
        $input = $post->mode === ContentGenerationMode::Industry
            ? app(CompanyInputCollector::class)->collect($company)
            : [];

        $candidates = $this->trendCandidates($post);

        $system = WordPressPostPrompt::system($post->locale);
        $user = WordPressPostPrompt::user(
            $input,
            $post->topic,
            $post->mode,
            $post->locale,
            $candidates,
        );

        $payload = $this->complete($system, $user, $this->settings()->translation_model);

        $errors = WordPressPostSchema::validate($payload);

        if ($errors !== []) {
            Log::warning('WordPress post generation returned an invalid payload.', [
                'post_id' => $post->id,
                'errors' => $errors,
            ]);

            throw new ContentGenerationException('The generated article did not match the required shape: '.implode(' ', $errors));
        }

        $chosenTopic = $this->resolveChosenTopic($post, (string) $payload['chosen_topic'], $candidates);

        if ($chosenTopic === null) {
            Log::warning('WordPress post generation returned an invalid payload.', [
                'post_id' => $post->id,
                'errors' => ['chosen_topic' => 'Not one of the offered candidates or the given topic.'],
                'chosen_topic' => $payload['chosen_topic'],
                'topic' => $post->topic,
                'candidates' => $candidates,
            ]);

            throw new ContentGenerationException(
                'The generated article did not match the required shape: chosen_topic "'
                .$payload['chosen_topic'].'" is not one of the offered candidates or the given topic.',
            );
        }

        $post->forceFill([
            // The prompt lets the model pick its subject from the shortlist on
            // merit, so the topic the row was CREATED with is only a proposal.
            // Recording what the model actually wrote about is what keeps
            // GoogleTrendsService's per-company repeat-avoidance honest — and
            // stops rows like topic "کافه ازمیر بروجن" holding an article about
            // something else entirely.
            'topic' => $chosenTopic,
            'topic_normalized' => WordPressContentPost::normalizeTopic($chosenTopic),
            'title' => $payload['title'],
            'excerpt' => $payload['excerpt'],
            'focus_keyword' => $payload['focus_keyword'],
            'body' => $payload['body'],
            'image_alt' => $payload['image_alt'],
        ])->save();
    }

    /**
     * The subject the model committed to, canonicalised back to the exact
     * string that was offered — or null when it named something that was
     * never on offer, which the caller treats as an invalid payload.
     *
     * Matching is done on WordPressContentPost::normalizeTopic() forms, the
     * same comparison the repeat-avoidance ledger uses: a candidate echoed
     * back with a different letter form or a trailing full stop is the same
     * topic, and must not cost an otherwise good article.
     *
     * In specialised mode, and in trend mode when the feed gave nothing,
     * the only permitted answer is the topic the post was created with —
     * there was no shortlist to choose from.
     *
     * @param  list<string>  $candidates
     */
    protected function resolveChosenTopic(WordPressContentPost $post, string $chosen, array $candidates): ?string
    {
        $offered = $post->mode === ContentGenerationMode::Trending ? $candidates : [];
        $offered[] = $post->topic;

        $normalizedChoice = WordPressContentPost::normalizeTopic($chosen);

        if ($normalizedChoice === '') {
            return null;
        }

        foreach ($offered as $candidate) {
            if (WordPressContentPost::normalizeTopic($candidate) === $normalizedChoice) {
                return $candidate;
            }
        }

        return null;
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
