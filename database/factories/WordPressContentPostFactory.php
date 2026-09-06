<?php

namespace Database\Factories;

use App\Enums\ContentGenerationMode;
use App\Enums\WordPressPostStatus;
use App\Models\Company;
use App\Models\WordPressContentPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordPressContentPost>
 */
class WordPressContentPostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topic = fake()->unique()->sentence(3);

        return [
            'company_id' => Company::factory(),
            'locale' => 'fa',
            'mode' => ContentGenerationMode::Industry,
            'status' => WordPressPostStatus::Published,
            'step' => 3,
            'topic' => $topic,
            'topic_normalized' => WordPressContentPost::normalizeTopic($topic),
            'title' => fake()->sentence(6),
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'focus_keyword' => fake()->word(),
            'image_alt' => fake()->sentence(4),
            'wp_post_id' => fake()->unique()->numberBetween(1, 9999),
            'wp_media_id' => fake()->unique()->numberBetween(1, 9999),
            'wp_post_url' => fake()->url(),
            'published_at' => now(),
        ];
    }

    public function queued(): static
    {
        return $this->state(fn (): array => [
            'status' => WordPressPostStatus::Queued,
            'step' => 0,
            'title' => null,
            'body' => null,
            'wp_post_id' => null,
            'wp_media_id' => null,
            'wp_post_url' => null,
            'published_at' => null,
        ]);
    }

    public function failed(string $reason = 'something went wrong'): static
    {
        return $this->state(fn (): array => [
            'status' => WordPressPostStatus::Failed,
            'failure_reason' => $reason,
            'wp_post_id' => null,
            'wp_post_url' => null,
            'published_at' => null,
        ]);
    }
}
