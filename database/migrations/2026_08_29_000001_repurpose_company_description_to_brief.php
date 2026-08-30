<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repurpose companies.description: the translatable Tiptap HTML column
     * becomes the user's own single-language plain-text brief (brief +
     * brief_locale). The old HTML is backfilled into brief from the
     * fallback-locale translation (or the first non-empty one), then the
     * description column is dropped. Public pages keep reading the
     * company_publications snapshot, which keeps its own description column.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('brief')->nullable();
            $table->string('brief_locale', 5)->nullable();
        });

        $fallbackLocale = (string) config('app.fallback_locale');

        Company::withTrashed()
            ->whereNotNull('description')
            ->chunkById(100, function ($companies) use ($fallbackLocale): void {
                foreach ($companies as $company) {
                    // Read the raw JSON attribute: the column still exists
                    // during this migration even though the model no longer
                    // declares it translatable.
                    $translations = json_decode((string) $company->getRawOriginal('description'), true) ?? [];

                    $locale = null;
                    $brief = null;

                    foreach ($translations as $candidateLocale => $candidateHtml) {
                        $text = is_string($candidateHtml)
                            ? trim(html_entity_decode(strip_tags($candidateHtml)))
                            : '';

                        if ($text === '') {
                            continue;
                        }

                        if ($locale === null || $candidateLocale === $fallbackLocale) {
                            $locale = (string) $candidateLocale;
                            $brief = $text;
                        }

                        if ($locale === $fallbackLocale) {
                            break;
                        }
                    }

                    $company->forceFill([
                        'brief' => $brief,
                        'brief_locale' => $locale,
                    ])->saveQuietly();
                }
            });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    /**
     * Re-add the old column shape (empty); brief/brief_locale are kept.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('description')->nullable();
        });
    }
};
