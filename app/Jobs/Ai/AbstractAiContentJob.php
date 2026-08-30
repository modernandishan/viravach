<?php

namespace App\Jobs\Ai;

use App\Ai\ContentGenerator;
use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\Prompts\CompanyContentPrompt;
use App\Enums\CompanyContentStatus;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Models\SeoKeywordReservation;
use App\Services\Ai\ContentGenerationService;
use App\Settings\ContentSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Shared skeleton for the AI content generation chain. Each concrete job
 * owns one numbered step; retries are handled by RESUMING from the row's
 * `step` column, not by re-running a failed job, so $tries = 1 everywhere.
 * Every job skips its work entirely when the row's step is already at or
 * past its own — that is what makes a re-dispatched chain an idempotent
 * resume instead of a second run.
 */
abstract class AbstractAiContentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const STEP = 0;

    /**
     * Unique-job window: while a run is live, a second dispatch for the
     * same company is dropped instead of racing the chain.
     */
    public $uniqueFor = 900;

    public $tries = 1;

    /**
     * Wall-clock cap per job; concrete jobs set their own.
     */
    public $timeout = 900;

    public function __construct(
        public readonly int $companyId,
    ) {}

    public function uniqueId(): string
    {
        return 'ai-content-'.$this->companyId;
    }

    // Not final: FinalizeContent overrides the step body to mark the row
    // ready and release the lock.
    public function handle(): void
    {
        $content = $this->contentRow();

        // Idempotent resume: a later step (or an earlier run that already
        // completed this step) makes this dispatch a no-op.
        if ($content->step >= static::STEP) {
            return;
        }

        $this->advanceStep($content);

        // STUB — overridden by the concrete jobs.
    }

    final public function failed(Throwable $exception): void
    {
        $content = CompanyContent::query()
            ->where('company_id', $this->companyId)
            ->first();

        if ($content !== null) {
            app(ContentGenerationService::class)
                ->markFailed($content, Str::limit($exception->getMessage(), 500));
        }
    }

    protected function contentRow(): CompanyContent
    {
        return CompanyContent::query()->where('company_id', $this->companyId)->firstOrFail();
    }

    protected function company(): Company
    {
        return Company::query()->findOrFail($this->companyId);
    }

    protected function settings(): ContentSettings
    {
        return app(ContentSettings::class);
    }

    /**
     * Advance the step pointer and flip the row into the generating state.
     */
    protected function advanceStep(CompanyContent $content): void
    {
        $content->forceFill([
            'status' => CompanyContentStatus::Generating,
            'step' => static::STEP,
        ])->save();
    }

    /**
     * ai_payload is read-modify-written per step; the unique job lock and
     * the single-chain design mean no two writers race on one row.
     */
    protected function mergePayload(CompanyContent $content, string $locale, array $payload): void
    {
        $aiPayload = $content->ai_payload ?? [];
        $aiPayload[$locale] = $payload;

        $content->forceFill(['ai_payload' => $aiPayload])->save();
    }

    protected function mergeSeoPayload(CompanyContent $content, string $locale, array $block): void
    {
        $aiPayload = $content->ai_payload ?? [];
        $aiPayload['seo'][$locale] = $block;

        $content->forceFill(['ai_payload' => $aiPayload])->save();
    }

    /**
     * One gateway call, one validation, one correction retry, then a hard
     * failure with the collected errors — never a partial payload.
     *
     * @param  callable(array): array<string, string>  $validate
     * @return array<string, mixed>
     */
    protected function completeValidated(string $system, string $user, ?string $model, callable $validate): array
    {
        $generator = app(ContentGenerator::class);

        $payload = $generator->complete($system, $user, $model);
        $errors = $validate($payload);

        if ($errors !== []) {
            $correction = $user."\n\n"
                ."CORRECTION REQUIRED — your previous JSON was rejected by the validator:\n"
                .implode("\n", array_map(
                    fn (string $field, string $message): string => "- {$field}: {$message}",
                    array_keys($errors),
                    $errors,
                ))
                ."\nReturn the complete corrected JSON object under the same contract.";

            $payload = $generator->complete($system, $correction, $model);
            $errors = $validate($payload);

            if ($errors !== []) {
                throw new ContentGenerationException(
                    'Generated payload failed validation: '.implode('; ', array_map(
                        fn (string $field, string $message): string => "{$field}: {$message}",
                        array_keys($errors),
                        $errors,
                    )),
                );
            }
        }

        return $payload;
    }

    /**
     * Reserve one of the SEO block's keyword candidates, walking the list
     * in order. The (locale, keyword) unique index is the arbiter — the
     * insert is attempted and a unique-violation means the candidate is
     * taken; there is deliberately NO check-then-insert, which would race.
     * When every candidate is taken, the first candidate gets a qualifier
     * from the company's primary category. A keyword collision never fails
     * the chain: if even the qualified form is taken, the original
     * focus_keyword stays.
     *
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    protected function reserveSeoKeyword(Company $company, string $locale, array $seo): array
    {
        $candidates = array_values((array) ($seo['keyword_candidates'] ?? []));

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '' && $this->reserveKeyword($company, $locale, $candidate)) {
                $seo['focus_keyword'] = $candidate;

                return $seo;
            }
        }

        if ($candidates !== [] && ($primary = $company->categories()->first()) !== null) {
            $qualifier = Str::words(
                (string) ($primary->getTranslation('title', $locale, false)
                    ?: $primary->getTranslation('title', CompanyContentPrompt::SOURCE_LOCALE, false)),
                3,
                '',
            );

            $qualified = trim(trim((string) $candidates[0]).' '.$qualifier);

            if ($qualified !== trim((string) $candidates[0])
                && $this->reserveKeyword($company, $locale, $qualified)) {
                $seo['focus_keyword'] = $qualified;
            }
        }

        return $seo;
    }

    protected function reserveKeyword(Company $company, string $locale, string $keyword): bool
    {
        try {
            SeoKeywordReservation::create([
                'locale' => $locale,
                'keyword' => $keyword,
                'seoable_type' => $company->getMorphClass(),
                'seoable_id' => $company->getKey(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
