<?php

namespace App\Support;

/**
 * Fixed set of social platforms editable on the dashboard company forms.
 * Storage keeps the same `social_links` JSON shape (platform key => full
 * URL); for prefixed platforms the form only edits the suffix after the
 * fixed URL prefix.
 */
class CompanySocialPlatforms
{
    /**
     * Platform key => fixed URL prefix, in display order. A null prefix
     * means the field takes a full URL as-is.
     *
     * @var array<string, string|null>
     */
    public const PLATFORMS = [
        'telegram' => 'https://t.me/',
        'whatsapp' => 'https://wa.me/',
        'instagram' => 'https://instagram.com/',
        'website1' => null,
        'website2' => null,
        'website3' => null,
        'youtube' => 'https://youtube.com/@',
        'x' => 'https://x.com/',
    ];

    /**
     * @return array<string, string>
     */
    public static function emptyState(): array
    {
        return array_fill_keys(array_keys(self::PLATFORMS), '');
    }

    /**
     * Map a stored `social_links` payload to form state: prefixed platforms
     * get their prefix stripped so re-saving does not duplicate it; values
     * not matching the prefix (legacy/odd data) are shown as-is so nothing
     * is silently lost. Keys no longer offered (e.g. linkedin) are dropped.
     *
     * @param  array<string, string>|null  $storedLinks
     * @return array<string, string>
     */
    public static function toFormState(?array $storedLinks): array
    {
        $state = self::emptyState();
        $storedLinks ??= [];

        // The previous form stored a single extra URL under `website`.
        if (isset($storedLinks['website']) && ! isset($storedLinks['website1'])) {
            $storedLinks['website1'] = $storedLinks['website'];
        }

        foreach ($storedLinks as $platform => $url) {
            if (! array_key_exists($platform, $state)) {
                continue;
            }

            $prefix = self::PLATFORMS[$platform];

            $state[$platform] = ($prefix !== null && str_starts_with((string) $url, $prefix))
                ? substr((string) $url, strlen($prefix))
                : (string) $url;
        }

        return $state;
    }

    /**
     * Build the stored `social_links` payload from form state. Prefixed
     * platforms get prefix.suffix — unless the user pasted a full link
     * (starts with "http"), which is stored as-is to avoid doubling the
     * prefix.
     *
     * @param  array<string, string|null>  $formState
     * @return array<string, string>|null
     */
    public static function toStoredLinks(array $formState): ?array
    {
        $stored = [];

        foreach (self::PLATFORMS as $platform => $prefix) {
            $value = trim((string) ($formState[$platform] ?? ''));

            if ($value === '') {
                continue;
            }

            $stored[$platform] = ($prefix !== null && ! str_starts_with($value, 'http'))
                ? $prefix.$value
                : $value;
        }

        return $stored ?: null;
    }

    /**
     * Lang key for the platform's label, e.g. `field_social_website_1` for
     * `website1`.
     */
    public static function labelKey(string $platform): string
    {
        return 'field_social_'.preg_replace('/(\d+)$/', '_$1', $platform);
    }
}
