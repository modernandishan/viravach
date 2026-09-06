<?php

namespace App\Jobs\WordPress;

use App\Enums\WordPressPostStatus;
use App\Models\WordPressContentPost;
use App\Settings\ContentSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Shared skeleton for the WordPress article chain, following the same
 * contract as AbstractAiContentJob: each concrete job owns one numbered
 * step, $tries = 1, and recovery is a RESUME from the row's `step` column
 * rather than a re-run of a failed job.
 *
 * It deliberately does not extend AbstractAiContentJob. That class is bound
 * to CompanyContent — one row per company, forever — and keys its unique
 * lock on the company id, so sharing it would make a monthly article
 * collide both with itself and with the one-time profile pipeline. This
 * chain is keyed on the post id instead.
 */
abstract class AbstractWordPressPostJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const STEP = 0;

    public $uniqueFor = 900;

    public $tries = 1;

    public $timeout = 600;

    public function __construct(
        public readonly int $postId,
    ) {}

    public function uniqueId(): string
    {
        return 'wp-post-'.$this->postId;
    }

    public function handle(): void
    {
        $post = $this->postRow();

        // Idempotent resume: a step already reached is a no-op, so a
        // re-dispatched chain continues instead of starting over.
        if ($post->step >= static::STEP) {
            return;
        }

        $this->advanceStep($post);

        $this->run($post);
    }

    abstract protected function run(WordPressContentPost $post): void;

    public function failed(Throwable $exception): void
    {
        $post = WordPressContentPost::query()->find($this->postId);

        $post?->forceFill([
            'status' => WordPressPostStatus::Failed,
            'failure_reason' => Str::limit($exception->getMessage(), 500),
        ])->save();
    }

    /**
     * Records a expected, non-exceptional failure — a wrong credential, an
     * unreachable site — and stops the chain without raising.
     */
    protected function fail(WordPressContentPost $post, string $reason): void
    {
        $post->forceFill([
            'status' => WordPressPostStatus::Failed,
            'failure_reason' => Str::limit($reason, 500),
        ])->save();

        $this->job?->delete();
    }

    protected function postRow(): WordPressContentPost
    {
        return WordPressContentPost::query()->findOrFail($this->postId);
    }

    protected function settings(): ContentSettings
    {
        return app(ContentSettings::class);
    }

    protected function advanceStep(WordPressContentPost $post): void
    {
        $post->forceFill([
            'status' => WordPressPostStatus::Generating,
            'step' => static::STEP,
        ])->save();
    }
}
