<?php

namespace App\Support;

use finfo;

/**
 * Resolves the real MIME type of generated image bytes, and the file
 * extension that goes with it.
 *
 * The image gateway does not promise a format: the same model that used to
 * answer with PNG now answers with WebP, so nothing downstream may assume
 * one. Storing WebP bytes under a `.png` name (and later uploading them to
 * WordPress as `image/png`) makes WordPress reject or mis-serve the
 * attachment, so both the medialibrary file name and the publish request
 * are derived from the bytes themselves.
 *
 * Only the three formats the pipeline actually supports are recognised —
 * png, jpeg and webp. Anything else falls back to PNG, which is what the
 * pipeline assumed before this class existed.
 */
class ImageMimeType
{
    public const FALLBACK = 'image/png';

    /**
     * Supported MIME type => file extension.
     *
     * @var array<string, string>
     */
    public const SUPPORTED = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    /**
     * The MIME type of the given image bytes. finfo answers first (it reads
     * the same magic numbers `file(1)` does); the hand-rolled signature
     * check below is the fallback for a build without ext-fileinfo.
     */
    public static function detect(string $bytes): string
    {
        if (class_exists(finfo::class)) {
            $detected = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

            if (is_string($detected) && isset(self::SUPPORTED[$detected])) {
                return $detected;
            }
        }

        return self::detectFromSignature($bytes) ?? self::FALLBACK;
    }

    /**
     * Normalises anything already claiming to be a MIME type (a stored
     * `media.mime_type`, a response header) to one this pipeline supports.
     */
    public static function normalize(?string $mimeType): string
    {
        $mimeType = mb_strtolower(trim((string) $mimeType));

        return isset(self::SUPPORTED[$mimeType]) ? $mimeType : self::FALLBACK;
    }

    /**
     * The extension for a MIME type — always one of png/jpg/webp.
     */
    public static function extensionFor(?string $mimeType): string
    {
        return self::SUPPORTED[self::normalize($mimeType)];
    }

    /**
     * The extension for raw image bytes, without a stored MIME type to
     * consult.
     */
    public static function extensionForBytes(string $bytes): string
    {
        return self::extensionFor(self::detect($bytes));
    }

    private static function detectFromSignature(string $bytes): ?string
    {
        if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }
}
