<?php

namespace App\Console\Commands;

use App\Enums\CompanyContentStatus;
use App\Models\CompanyContent;
use App\Services\Ai\ContentGenerationService;
use Illuminate\Console\Command;

class SweepStuckContentGenerations extends Command
{
    protected $signature = 'app:sweep-stuck-content-generations';

    protected $description = 'Mark AI content generations stuck in queued/generating as failed and release their locks';

    public function handle(ContentGenerationService $service): int
    {
        // A live run holds the lock for the duration of one chain step; any
        // lock older than this means the worker died mid-run.
        $staleBefore = now()->subMinutes(15);

        $stuck = CompanyContent::query()
            ->whereIn('status', [CompanyContentStatus::Queued, CompanyContentStatus::Generating])
            ->where('locked_at', '<', $staleBefore)
            ->get();

        foreach ($stuck as $content) {
            $service->markFailed($content, 'Generation stalled: no worker progress for over 15 minutes. Re-request to resume.');
        }

        if ($stuck->isNotEmpty()) {
            $this->info("Swept {$stuck->count()} stuck AI content generation(s).");
        }

        return self::SUCCESS;
    }
}
