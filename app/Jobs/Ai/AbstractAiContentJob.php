<?php

namespace App\Jobs\Ai;

use App\Ai\ContentGenerator;
use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\Prompts\CompanyContentPrompt;
use App\Enums\CompanyContentStatus;
use App\Events\Ai\ContentGenerationProgressed;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    // Not final: LocalizeContentLocale overrides this to record its own
    // locale's failure instead of failing the whole row — one locale
    // failing must not abort its siblings.
    public function failed(Throwable $exception): void
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
     * Every supported locale except the source one — the fixed list step 4
     * fans out over and step 5 (FinalizeContent) checks for completeness
     * before it writes. Single source of truth so both steps can never
     * disagree on what "every locale" means.
     *
     * @return list<string>
     */
    protected static function targetLocales(): array
    {
        return array_values(array_filter(
            array_keys((array) config('laravellocalization.supportedLocales')),
            fn (string $locale): bool => $locale !== CompanyContentPrompt::SOURCE_LOCALE,
        ));
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

        // $afterCommit makes the broadcast job wait for any surrounding
        // transaction to commit before it goes out.
        ContentGenerationProgressed::dispatch($content);
    }

    /**
     * ai_payload is read-modify-written per step. Steps 1-3 have exactly one
     * writer so the lock below is a no-op in practice, but step 4 fans out
     * to four concurrent LocalizeContentLocale jobs writing the same row —
     * every writer goes through the same lock-safe path rather than a
     * second mechanism reserved for the concurrent case.
     */
    protected function mergePayload(CompanyContent $content, string $locale, array $payload): void
    {
        $this->lockedPayloadUpdate($content->id, function (array $aiPayload) use ($locale, $payload): array {
            $aiPayload[$locale] = $payload;

            return $aiPayload;
        });
    }

    protected function mergeSeoPayload(CompanyContent $content, string $locale, array $block): void
    {
        $this->lockedPayloadUpdate($content->id, function (array $aiPayload) use ($locale, $block): array {
            $aiPayload['seo'][$locale] = $block;

            return $aiPayload;
        });
    }

    /**
     * Read-modify-write ai_payload under a row lock. Without it, two
     * concurrent LocalizeContentLocale jobs (one per locale) could each
     * read the same base payload, add their own locale key in memory, and
     * the second save() would overwrite the first job's key entirely —
     * silently dropping that locale's content. Wrapping the read in
     * lockForUpdate() inside a transaction makes Postgres block the second
     * writer's SELECT until the first transaction commits, so every writer
     * always merges on top of the latest committed payload instead of a
     * copy captured before its own work began.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutate
     */
    protected function lockedPayloadUpdate(int $contentId, callable $mutate): void
    {
        DB::transaction(function () use ($contentId, $mutate): void {
            $content = CompanyContent::query()->whereKey($contentId)->lockForUpdate()->firstOrFail();

            $content->forceFill(['ai_payload' => $mutate($content->ai_payload ?? [])])->save();
        });
    }

    /**
     * Append one locale's failure note onto failure_reason under the same
     * row lock as lockedPayloadUpdate(), so two locales failing at the same
     * time cannot overwrite each other's note — losing one would hide a
     * missing language from the admin instead of merely losing a race that
     * a later regeneration could paper over.
     */
    protected function recordLocaleFailure(int $contentId, string $locale, string $reason): void
    {
        DB::transaction(function () use ($contentId, $locale, $reason): void {
            $content = CompanyContent::query()->whereKey($contentId)->lockForUpdate()->firstOrFail();

            $prefix = 'Partial localization — failed locale(s): ';
            $note = "{$locale}: {$reason}";
            $existing = (string) $content->failure_reason;

            $content->forceFill([
                'failure_reason' => Str::startsWith($existing, $prefix)
                    ? $existing.' | '.$note
                    : $prefix.$note,
            ])->save();
        });
    }

    /**
     * One gateway call, then validate → repair → (one correction retry) →
     * throw. A payload that only violates max-length limits is salvaged via
     * repair() instead of throwing away a completed generation; anything the
     * repair cannot fix (missing fields, under-length text, shape errors)
     * gets the single correction retry, and only a second hard failure
     * throws — never a partial payload.
     *
     * @param  callable(array): array<string, string>  $validate
     * @param  callable(array): array<string, mixed>  $repair
     * @return array<string, mixed>
     */
    protected function completeValidated(string $system, string $user, ?string $model, callable $validate, callable $repair): array
    {
        $generator = app(ContentGenerator::class);

        $lastErrors = [];

        $payload = $this->settle($generator->complete($system, $user, $model), $validate, $repair, $lastErrors);

        if ($payload === null) {
            $correction = $user."\n\n"
                ."CORRECTION REQUIRED — your previous JSON was rejected by the validator:\n"
                .implode("\n", array_map(
                    fn (string $field, string $message): string => "- {$field}: {$message}",
                    array_keys($lastErrors),
                    $lastErrors,
                ))
                ."\nReturn the complete corrected JSON object under the same contract.";

            $payload = $this->settle($generator->complete($system, $correction, $model), $validate, $repair, $lastErrors);
        }

        if ($payload === null) {
            throw new ContentGenerationException(
                'Generated payload failed validation after repair and one correction retry.',
            );
        }

        return $payload;
    }

    /**
     * Validate, then attempt the schema repair. Returns the (possibly
     * repaired) payload once it validates, or null when the failure is not
     * repairable — the correction retry's cue. Rescues are logged with the
     * rescued field paths so we can see which limits the model struggles
     * with.
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array): array<string, string>  $validate
     * @param  callable(array): array<string, mixed>  $repair
     * @param  array<string, string>  $lastErrors  filled with the remaining validation errors on null
     * @return array<string, mixed>|null
     */
    private function settle(array $payload, callable $validate, callable $repair, array &$lastErrors): ?array
    {
        $errors = $validate($payload);

        if ($errors === []) {
            return $payload;
        }

        $repaired = $repair($payload);
        $remaining = $validate($repaired);

        if ($remaining === []) {
            $rescued = array_keys(array_diff_key($errors, $remaining));

            Log::info('Content generation: schema repair rescued the payload.', [
                'company_id' => $this->companyId,
                'step' => static::STEP,
                'rescued_fields' => $rescued,
            ]);

            return $repaired;
        }

        $lastErrors = $remaining;

        return null;
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
