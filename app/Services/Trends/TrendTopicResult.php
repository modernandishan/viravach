<?php

namespace App\Services\Trends;

/**
 * The outcome of fetching trending topics for a company. Expected failures
 * are values rather than exceptions, matching the WordPress connection
 * services.
 */
final class TrendTopicResult
{
    /**
     * @param  list<string>  $candidates
     */
    private function __construct(
        public readonly bool $successful,
        public readonly ?TrendFailureReason $reason = null,
        public readonly ?string $topic = null,
        public readonly ?string $normalizedTopic = null,
        /** The Google Trends geo code the feed was fetched for, e.g. "IR". */
        public readonly ?string $geo = null,
        /**
         * True when every candidate had already been used by this company
         * and the least-recently-used one was reused rather than failing.
         */
        public readonly bool $reused = false,
        /**
         * A handful of surviving candidate titles (title only) offered
         * alongside $topic, so the content-generation prompt can pick the
         * one with the best genuine connection to the company itself,
         * rather than trusting this class's own single, arbitrary pick.
         */
        public readonly array $candidates = [],
    ) {}

    /**
     * @param  list<string>  $candidates
     */
    public static function picked(
        string $topic,
        string $normalizedTopic,
        string $geo,
        bool $reused = false,
        array $candidates = [],
    ): self {
        return new self(
            successful: true,
            topic: $topic,
            normalizedTopic: $normalizedTopic,
            geo: $geo,
            reused: $reused,
            candidates: $candidates,
        );
    }

    public static function failed(TrendFailureReason $reason): self
    {
        return new self(successful: false, reason: $reason);
    }
}
