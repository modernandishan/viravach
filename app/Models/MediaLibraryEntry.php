<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shared owner row for the admin media library: files uploaded directly
 * from the Filament Media resource attach here (single lazily-created row,
 * see shared()) instead of a real Company/Category, giving each Media row
 * its polymorphic model_type/model_id until it is attached elsewhere.
 * Metadata lives on the Media custom properties, not here.
 */
class MediaLibraryEntry extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** The one shared row all manual library uploads attach to. */
    public static function shared(): self
    {
        return static::query()->firstOrCreate([], []);
    }

    /**
     * Whether a Media row belongs to the shared library owner (i.e. it was
     * manually uploaded through the Media resource's upload action and is
     * safe to delete there). A query, not shared(), so gating a UI action
     * never lazily creates the row as a side effect.
     */
    public static function owns(Media $media): bool
    {
        return $media->model_type === static::class
            && static::query()->whereKey($media->model_id)->exists();
    }

    public function registerMediaCollections(): void
    {
        // Fixed collection list — arbitrary names on one shared model would
        // accumulate without bound.
        $this->addMediaCollection('library_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('library_files');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Queued webp conversion, matching the app-wide media-queue
        // convention (docs/viravach-media-queue-setup.md).
        $this->addMediaConversion('webp')
            ->format('webp')
            ->performOnCollections('library_images')
            ->queued();
    }
}
