<?php

namespace App\Services\WordPress;

use App\Enums\ContentGenerationMode;
use App\Enums\WordPressConnectionStatus;
use App\Enums\WordPressPostStatus;
use App\Jobs\WordPress\GenerateWordPressPostContent;
use App\Jobs\WordPress\GenerateWordPressPostImage;
use App\Jobs\WordPress\PublishWordPressPost;
use App\Models\Company;
use App\Models\WordPressContentPost;
use App\Services\Trends\GoogleTrendsService;
use App\Services\Trends\TrendFailureReason;
use App\Settings\ContentSettings;
use App\Support\WordPressContentQuota;
use Illuminate\Support\Facades\Bus;

/**
 * The single entry point for requesting one monthly WordPress article — the
 * WordPress-pipeline equivalent of ContentGenerationService::request(), but
 * for a table that grows every month instead of a row that exists once.
 *
 * Every gate here is re-checked even though the dashboard page checks them
 * too: a stale page or a direct wire:click must not be able to skip past a
 * global kill-switch, a spent quota, or an unproven connection.
 */
class WordPressContentGenerationService
{
    public function __construct(private readonly GoogleTrendsService $trends) {}

    public function request(Company $company, string $locale, ContentGenerationMode $mode): WordPressContentGenerationResult
    {
        if (! app(ContentSettings::class)->enabled) {
            return WordPressContentGenerationResult::failed(WordPressContentGenerationFailureReason::GenerationDisabled);
        }

        if ($company->wp_connection_status !== WordPressConnectionStatus::Connected) {
            return WordPressContentGenerationResult::failed(WordPressContentGenerationFailureReason::NotConnected);
        }

        if (! WordPressContentQuota::for($company)->canGenerate()) {
            return WordPressContentGenerationResult::failed(WordPressContentGenerationFailureReason::QuotaExhausted);
        }

        if ($this->hasRunInProgress($company)) {
            return WordPressContentGenerationResult::failed(WordPressContentGenerationFailureReason::AlreadyRunning);
        }

        $topic = $this->pickTopic($company, $locale, $mode);

        if ($topic === null) {
            return WordPressContentGenerationResult::failed(WordPressContentGenerationFailureReason::NoTopicAvailable);
        }

        $post = WordPressContentPost::query()->create([
            'company_id' => $company->id,
            'locale' => $locale,
            'mode' => $mode,
            'status' => WordPressPostStatus::Queued,
            'topic' => $topic->topic,
            'topic_normalized' => $topic->normalizedTopic,
        ]);

        Bus::chain([
            new GenerateWordPressPostContent($post->id),
            new GenerateWordPressPostImage($post->id),
            new PublishWordPressPost($post->id),
        ])->onQueue('ai-content')->dispatch();

        return WordPressContentGenerationResult::queued($post);
    }

    protected function hasRunInProgress(Company $company): bool
    {
        return WordPressContentPost::query()
            ->where('company_id', $company->id)
            ->whereIn('status', [WordPressPostStatus::Queued, WordPressPostStatus::Generating])
            ->exists();
    }

    /**
     * @return object{topic: string, normalizedTopic: string}|null
     */
    protected function pickTopic(Company $company, string $locale, ContentGenerationMode $mode): ?object
    {
        if ($mode === ContentGenerationMode::Industry) {
            $topic = $this->specializedTopic($company, $locale);

            return $topic === null ? null : (object) [
                'topic' => $topic,
                'normalizedTopic' => WordPressContentPost::normalizeTopic($topic),
            ];
        }

        $result = $this->trends->pickTopic($company, $locale);

        if (! $result->successful) {
            return null;
        }

        return (object) [
            'topic' => $result->topic,
            'normalizedTopic' => $result->normalizedTopic,
        ];
    }

    /**
     * Specialized mode's "topic" is the company's own field: its title
     * combined with its primary category, so the prompt has something
     * concrete to write about beyond "our company".
     */
    protected function specializedTopic(Company $company, string $locale): ?string
    {
        $name = trim((string) $company->getTranslation('name', $locale, true));
        $category = $company->categories()->first()?->getTranslation('title', $locale, true);
        $category = trim((string) $category);

        $topic = trim(implode(' — ', array_filter([$name, $category], fn (string $part): bool => $part !== '')));

        return $topic !== '' ? $topic : null;
    }

    public static function guardMessage(TrendFailureReason $reason): string
    {
        return __('wordpress_content.trend_failed_'.$reason->value);
    }
}
