<?php

namespace App\Services\WordPress;

use App\Models\WordPressContentPost;

/**
 * The outcome of requesting one monthly article: either the queued post
 * row, or why nothing was queued. Expected failures are values, not
 * exceptions, matching every other WordPress-facing result in this app.
 */
final class WordPressContentGenerationResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?WordPressContentGenerationFailureReason $reason = null,
        public readonly ?WordPressContentPost $post = null,
    ) {}

    public static function queued(WordPressContentPost $post): self
    {
        return new self(successful: true, post: $post);
    }

    public static function failed(WordPressContentGenerationFailureReason $reason): self
    {
        return new self(successful: false, reason: $reason);
    }
}
