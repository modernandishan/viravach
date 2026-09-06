<?php

namespace App\Services\Trends;

/**
 * Why a trending topic could not be fetched from Google's public
 * trending-searches feed. Each case maps to its own translated message.
 */
enum TrendFailureReason: string
{
    /** The feed could not be reached, or answered with a non-2xx status. */
    case RequestFailed = 'request_failed';

    /** The feed responded, but its body was not parseable XML. */
    case FeedUnavailable = 'feed_unavailable';

    /** The feed parsed fine but contained no usable titles. */
    case NoCandidates = 'no_candidates';
}
