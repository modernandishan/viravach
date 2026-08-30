<?php

namespace App\Console\Commands;

use App\Jobs\Ai\FinalizeContent;
use App\Models\Company;
use Illuminate\Console\Command;

/**
 * One-off manual tool: rebuild machine-made slugs from the AI-generated
 * English content. Deliberately NOT scheduled.
 */
class RegenerateCompanySlugs extends Command
{
    protected $signature = 'app:regenerate-company-slugs {--dry-run}';

    protected $description = 'Regenerate company slugs from generated English content (never-published companies only)';

    public function handle(): int
    {
        $candidates = Company::query()
            ->whereDoesntHave('publication')
            ->get()
            ->filter(fn (Company $company): bool => FinalizeContent::slugShouldBeRegenerated($company)
                && FinalizeContent::deriveContentSlugBase($company) !== null);

        $rows = [];

        foreach ($candidates as $company) {
            $newSlug = $company->regenerateSlugFromBase(
                (string) FinalizeContent::deriveContentSlugBase($company),
            );

            if ($newSlug === $company->slug) {
                continue;
            }

            $rows[] = [
                'id' => $company->id,
                'old' => $company->slug,
                'new' => $newSlug,
            ];

            if (! $this->option('dry-run')) {
                $company->forceFill(['slug' => $newSlug])->save();
            }
        }

        $this->table(['id', 'old slug', 'new slug'], $rows);

        $this->info(count($rows).' slug(s) '.($this->option('dry-run')
            ? 'would be regenerated (dry run — nothing written).'
            : 'regenerated.'));

        return self::SUCCESS;
    }
}
