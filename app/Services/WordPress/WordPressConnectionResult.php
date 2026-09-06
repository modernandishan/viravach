<?php

namespace App\Services\WordPress;

/**
 * The outcome of a single connection test: either a confirmed identity plus
 * the site's most recent posts, or a reason the test did not get that far.
 *
 * Expected failures (wrong credentials, not a WordPress site, an unreachable
 * host) are values, not exceptions — the settings page renders every one of
 * them as a normal message.
 */
final class WordPressConnectionResult
{
    /**
     * @param  list<array{title: string, link: string, date: string|null}>  $posts
     */
    private function __construct(
        public readonly bool $successful,
        public readonly ?WordPressConnectionFailureReason $reason = null,
        public readonly ?string $siteName = null,
        public readonly ?string $siteDescription = null,
        public readonly ?string $userName = null,
        public readonly ?string $userLogin = null,
        public readonly array $posts = [],
    ) {}

    /**
     * @param  list<array{title: string, link: string, date: string|null}>  $posts
     */
    public static function connected(
        ?string $siteName,
        ?string $siteDescription,
        ?string $userName,
        ?string $userLogin,
        array $posts,
    ): self {
        return new self(
            successful: true,
            siteName: $siteName,
            siteDescription: $siteDescription,
            userName: $userName,
            userLogin: $userLogin,
            posts: $posts,
        );
    }

    public static function failed(WordPressConnectionFailureReason $reason): self
    {
        return new self(successful: false, reason: $reason);
    }
}
