<?php

namespace App\Jobs\Ai;

use App\Ai\ImageGenerator;
use App\Ai\Input\CompanyInputCollector;
use App\Ai\Prompts\CompanyContentPrompt;
use App\Ai\Prompts\CompanyImagePrompt;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Settings\ContentSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Generates and attaches a featured image from the already-generated
 * English content payload, then fills its translatable metadata for every
 * supported locale from the already-localized payloads — no extra model
 * calls. Dispatched by FinalizeContent AFTER the content row is marked
 * ready, on the same 'ai-content' queue, but deliberately NOT part of the
 * Bus::chain: this job is not a step in the resumable content pipeline
 * (it does not extend AbstractAiContentJob, has no STEP, never touches
 * content->step), and its failure must never change the content row's
 * ready status or the generated content itself — only failure_reason gets
 * an informational note.
 */
class GenerateFeaturedImage implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 180;

    public $uniqueFor = 180;

    public function __construct(
        public readonly int $companyId,
    ) {}

    public function uniqueId(): string
    {
        return 'ai-content-featured-image-'.$this->companyId;
    }

    public function handle(): void
    {
        $settings = app(ContentSettings::class);

        if (! $settings->image_enabled) {
            return;
        }

        $company = Company::query()->find($this->companyId);

        if ($company === null || $company->hasMedia('featured_image')) {
            return;
        }

        $englishPayload = $company->content[CompanyContentPrompt::SOURCE_LOCALE] ?? null;

        if (! is_array($englishPayload)) {
            return;
        }

        $input = app(CompanyInputCollector::class)->collect($company);
        $prompt = CompanyImagePrompt::build($englishPayload, $input);

        $bytes = app(ImageGenerator::class)->generate($prompt);

        $tempPath = $this->writeTempFile($bytes);

        try {
            $media = $company->addMedia($tempPath)
                ->preservingOriginal()
                ->usingFileName("{$company->slug}-featured-image.png")
                ->toMediaCollection('featured_image', 's3');

            $this->setLocalizedMetadata($media, $company);
        } finally {
            @unlink($tempPath);
        }

        Log::info('Featured image generated and attached.', [
            'company_id' => $this->companyId,
        ]);
    }

    /**
     * Never fail the content row for this — the ready status and the
     * generated content are untouched. Only an informational note lands on
     * failure_reason so the admin can see a featured image is missing and
     * why, without the row looking failed.
     */
    public function failed(Throwable $exception): void
    {
        Log::warning('Featured image generation failed.', [
            'company_id' => $this->companyId,
            'reason' => $exception->getMessage(),
        ]);

        DB::transaction(function () use ($exception): void {
            $content = CompanyContent::query()
                ->where('company_id', $this->companyId)
                ->lockForUpdate()
                ->first();

            if ($content === null) {
                return;
            }

            $note = 'Featured image generation failed: '.Str::limit($exception->getMessage(), 300);
            $existing = (string) $content->failure_reason;

            $content->forceFill([
                'failure_reason' => filled($existing) ? $existing.' | '.$note : $note,
            ])->save();
        });
    }

    private function writeTempFile(string $bytes): string
    {
        $path = sys_get_temp_dir().'/featured-image-'.Str::uuid()->toString().'.png';

        file_put_contents($path, $bytes);

        return $path;
    }

    /**
     * Every supported locale's own already-generated payload feeds its own
     * metadata — a locale missing from content (e.g. its localization
     * failed) falls back the same way Company::contentFor() already does
     * everywhere else on the public site, rather than being left empty.
     *
     * Custom-property keys (title/alt/caption/description) match exactly
     * what the admin Media resource already reads and writes — see
     * App\Filament\Resources\Media\Tables\MediaTable::$translatableProperties.
     */
    private function setLocalizedMetadata(Media $media, Company $company): void
    {
        $title = [];
        $alt = [];
        $caption = [];
        $description = [];

        foreach (array_keys((array) config('laravellocalization.supportedLocales')) as $locale) {
            $payload = $company->contentFor($locale);

            if ($payload === null) {
                continue;
            }

            $title[$locale] = (string) ($payload['hero']['headline'] ?? '');
            $alt[$locale] = (string) ($payload['hero']['image_alt'] ?? '');
            $caption[$locale] = (string) ($payload['hero']['subheadline'] ?? '');
            $description[$locale] = self::firstParagraph((string) ($payload['about']['body'] ?? ''));
        }

        $media->setCustomProperty('title', $title);
        $media->setCustomProperty('alt', $alt);
        $media->setCustomProperty('caption', $caption);
        $media->setCustomProperty('description', $description);
        $media->save();
    }

    private static function firstParagraph(string $body): string
    {
        $paragraphs = preg_split('/\n{2,}/u', trim($body)) ?: [$body];

        return trim((string) ($paragraphs[0] ?? ''));
    }
}
