<?php

namespace App\Support;

use RuntimeException;

/**
 * Reads a video file's playback duration in seconds without loading the
 * file into memory — getID3 parses only the container metadata (mp4/mov's
 * mvhd atom, webm/mkv's EBML segment info), so a 256MB upload costs the
 * same as a 5MB one. Resolved through the container so tests can bind a
 * fake instead of feeding real video fixtures.
 */
class VideoMetadata
{
    /** The intro video's maximum allowed duration. */
    public const MAX_SECONDS = 600.0;

    /**
     * @return float|null the duration in seconds, or null when the file's
     *                    metadata cannot be parsed (corrupt/unsupported).
     */
    public function durationSeconds(string $path): ?float
    {
        if (! class_exists(\getID3::class)) {
            throw new RuntimeException('james-heinrich/getid3 is not installed.');
        }

        $analyzer = new \getID3;

        $info = $analyzer->analyze($path);

        $seconds = $info['playtime_seconds'] ?? null;

        return is_numeric($seconds) ? (float) $seconds : null;
    }
}
