<?php

namespace App\Services\WordPress;

/**
 * Why a monthly WordPress generation request was refused before anything
 * was even queued. Each case has its own translated, actionable message.
 */
enum WordPressContentGenerationFailureReason: string
{
    /** The global AI content kill-switch (ContentSettings::$enabled) is off. */
    case GenerationDisabled = 'generation_disabled';

    /** The company's WordPress connection has not been proven to work. */
    case NotConnected = 'not_connected';

    /** This month's plan allowance is spent. */
    case QuotaExhausted = 'quota_exhausted';

    /** A generation for this company is already queued or running. */
    case AlreadyRunning = 'already_running';

    /** Trend mode could not find a topic to write about. */
    case NoTopicAvailable = 'no_topic_available';
}
