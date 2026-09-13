<?php

namespace App\Jobs\Middleware;

use App\Jobs\Concerns\HasRequestDeadline;
use Closure;

/**
 * Anchors a job's HTTP deadline at the moment the queue worker starts
 * running it — before any input collection, website scraping or gateway
 * call has spent a second of the budget.
 *
 * Job middleware is the only hook that fires for every execution without
 * touching a single concrete job: the AI chain's jobs each override
 * handle() outright, so there is no shared entry point to put this in.
 *
 * @see HasRequestDeadline
 */
class StartRequestDeadline
{
    public function handle(object $job, Closure $next): mixed
    {
        if (method_exists($job, 'startRequestDeadline')) {
            $job->startRequestDeadline();
        }

        return $next($job);
    }
}
