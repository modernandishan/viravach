<?php

namespace App\Models;

use App\Enums\ContentGenerationMode;
use App\Enums\WordPressPostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One AI-generated article published to a company's own WordPress site.
 *
 * Doubles as the trend-repeat ledger: `topic_normalized` is what the trend
 * picker filters candidate topics against, so a company never gets the same
 * trending query twice while unused ones remain.
 */
#[Fillable([
    'company_id',
    'locale',
    'mode',
    'status',
    'step',
    'topic',
    'topic_normalized',
    'title',
    'excerpt',
    'body',
    'focus_keyword',
    'image_alt',
    'wp_post_id',
    'wp_media_id',
    'wp_post_url',
    'failure_reason',
    'published_at',
])]
class WordPressContentPost extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    // Eloquent's table-name guesser snake_cases per capital letter, giving
    // word_press_content_posts — but the migration (and every WordPress-
    // prefixed column elsewhere in the app) spells it as one word.
    protected $table = 'wordpress_content_posts';

    protected function casts(): array
    {
        return [
            'mode' => ContentGenerationMode::class,
            'status' => WordPressPostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
    }

    /**
     * The comparison form of a topic: two trending queries that differ only
     * in case, spacing, Arabic/Persian letter forms or trailing punctuation
     * are the same article as far as repeat-avoidance is concerned.
     */
    public static function normalizeTopic(string $topic): string
    {
        $topic = str_replace(
            ['ي', 'ك', 'ۀ', 'ة', 'أ', 'إ', 'آ', '‌'],
            ['ی', 'ک', 'ه', 'ه', 'ا', 'ا', 'ا', ' '],
            $topic,
        );

        $topic = preg_replace('/[\p{P}\p{S}]+/u', ' ', $topic) ?? $topic;
        $topic = preg_replace('/\s+/u', ' ', $topic) ?? $topic;

        return Str::lower(trim($topic));
    }
}
