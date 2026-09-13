<?php

namespace Tests\Feature\WordPress;

use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\Schemas\WordPressPostSchema;
use App\Enums\ContentGenerationMode;
use App\Jobs\WordPress\GenerateWordPressPostContent;
use App\Models\Company;
use App\Models\WordPressContentPost;
use App\Settings\ContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The trend prompt deliberately lets the model pick its subject from the
 * shortlist on merit, so the topic the row was created with is only a
 * proposal. Production rows proved the row never learned what was actually
 * picked: topic "کافه ازمیر بروجن" held an article about "بیانیه", and
 * topic "فیلم کامیون کامبوزیا پرتوی" held one about Lionel Messi — which
 * also fed GoogleTrendsService's per-company repeat-avoidance a history of
 * topics nobody wrote about.
 */
class WordPressChosenTopicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ContentSettings::class)->fill([
            'enabled' => true,
            'base_url' => 'https://ai.example/api',
            'api_key' => 'secret-key',
            'max_retries' => 0,
            'timeout' => 60,
        ])->save();
    }

    /**
     * @param  list<string>  $titles
     */
    private function fakeTrendsFeed(array $titles): void
    {
        $items = implode('', array_map(
            fn (string $title): string => '<item><title>'.$title.'</title></item>',
            $titles,
        ));

        Http::fake([
            'trends.google.com/*' => Http::response(
                '<?xml version="1.0"?><rss version="2.0"><channel>'.$items.'</channel></rss>',
            ),
            'https://ai.example/api/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode($this->articlePayload)]]],
            ]),
        ]);
    }

    /** @var array<string, string> */
    private array $articlePayload = [];

    /**
     * @return array<string, string>
     */
    private function article(string $chosenTopic): array
    {
        return [
            'chosen_topic' => $chosenTopic,
            'title' => 'A Perfectly Reasonable Article Title About The Subject',
            'excerpt' => str_repeat('A meta description that reads as a sentence. ', 3),
            'focus_keyword' => 'the subject',
            'body' => '<p>'.str_repeat('Body sentence about the subject. ', 40).'</p>',
            'image_alt' => 'A photograph illustrating the subject of this article',
        ];
    }

    private function queuedPost(ContentGenerationMode $mode, string $topic): WordPressContentPost
    {
        return WordPressContentPost::factory()->queued()->create([
            'company_id' => Company::factory(),
            'locale' => 'fa',
            'mode' => $mode,
            'topic' => $topic,
            'topic_normalized' => WordPressContentPost::normalizeTopic($topic),
        ]);
    }

    private function runJob(WordPressContentPost $post): void
    {
        (new GenerateWordPressPostContent($post->id))->handle();
    }

    public function test_the_topic_the_model_actually_chose_is_written_back_to_the_row(): void
    {
        $post = $this->queuedPost(ContentGenerationMode::Trending, 'کافه ازمیر بروجن');

        $this->articlePayload = $this->article('لیونل مسی');
        $this->fakeTrendsFeed(['کافه ازمیر بروجن', 'لیونل مسی', 'بازار خودرو']);

        $this->runJob($post);

        $post->refresh();

        $this->assertSame('لیونل مسی', $post->topic);
        $this->assertSame(WordPressContentPost::normalizeTopic('لیونل مسی'), $post->topic_normalized);
        $this->assertSame('A Perfectly Reasonable Article Title About The Subject', $post->title);
    }

    public function test_a_chosen_topic_that_was_never_offered_fails_the_article(): void
    {
        $post = $this->queuedPost(ContentGenerationMode::Trending, 'کافه ازمیر بروجن');

        $this->articlePayload = $this->article('بیانیه');
        $this->fakeTrendsFeed(['کافه ازمیر بروجن', 'لیونل مسی', 'بازار خودرو']);

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessageMatches('/chosen_topic/');

        try {
            $this->runJob($post);
        } finally {
            // The row keeps the topic it was created with — a rejected
            // article must never rewrite the ledger.
            $this->assertSame('کافه ازمیر بروجن', $post->refresh()->topic);
        }
    }

    public function test_specialised_mode_only_accepts_the_topic_it_was_given(): void
    {
        $post = $this->queuedPost(ContentGenerationMode::Industry, 'How to choose an industrial pump');

        $this->articlePayload = $this->article('Lionel Messi');
        $this->fakeTrendsFeed([]);

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessageMatches('/chosen_topic/');

        $this->runJob($post);
    }

    public function test_specialised_mode_passes_when_the_model_echoes_the_topic_back(): void
    {
        $post = $this->queuedPost(ContentGenerationMode::Industry, 'How to choose an industrial pump');

        $this->articlePayload = $this->article('How to choose an industrial pump');
        $this->fakeTrendsFeed([]);

        $this->runJob($post);

        $post->refresh();

        $this->assertSame('How to choose an industrial pump', $post->topic);
        $this->assertSame(
            WordPressContentPost::normalizeTopic('How to choose an industrial pump'),
            $post->topic_normalized,
        );
    }

    public function test_the_schema_requires_the_chosen_topic_field(): void
    {
        $payload = $this->article('anything');
        unset($payload['chosen_topic']);

        $this->assertNotEmpty(WordPressPostSchema::validate($payload));
        $this->assertStringContainsString('chosen_topic', WordPressPostSchema::promptSpec());
    }
}
