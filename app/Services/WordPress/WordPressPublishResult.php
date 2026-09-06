<?php

namespace App\Services\WordPress;

/**
 * The outcome of publishing one article: the identifiers WordPress handed
 * back, or the reason it did not get that far. Expected failures are values,
 * not exceptions — the same convention as WordPressConnectionResult.
 */
final class WordPressPublishResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?WordPressPublishFailureReason $reason = null,
        public readonly ?int $postId = null,
        public readonly ?string $postUrl = null,
        public readonly ?int $mediaId = null,
        /**
         * Whether the SEO plugin's meta fields actually stuck. Null when it
         * could not be determined (no plugin selected, or the post could not
         * be read back).
         */
        public readonly ?bool $seoMetaWritable = null,
    ) {}

    public static function published(
        int $postId,
        ?string $postUrl,
        ?int $mediaId,
        ?bool $seoMetaWritable,
    ): self {
        return new self(
            successful: true,
            postId: $postId,
            postUrl: $postUrl,
            mediaId: $mediaId,
            seoMetaWritable: $seoMetaWritable,
        );
    }

    public static function failed(WordPressPublishFailureReason $reason): self
    {
        return new self(successful: false, reason: $reason);
    }
}
