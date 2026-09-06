<?php

namespace Tests\Feature\WordPress;

use App\Models\Company;
use App\Models\WordPressContentPost;
use App\Services\Trends\GoogleTrendsService;
use App\Services\Trends\TrendFailureReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every request to Google's public trending-searches RSS feed is faked: the
 * suite must never touch the real feed.
 */
class TrendTopicSelectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A realistic sample of the feed's actual shape: <item> entries with a
     * plain <title>, an ht:-namespaced <ht:approx_traffic>, and a <pubDate>.
     *
     * @param  list<string>  $titles
     */
    protected function sampleFeed(array $titles): string
    {
        $items = implode('', array_map(
            fn (string $title): string => '<item>'
                .'<title>'.htmlspecialchars($title, ENT_XML1).'</title>'
                .'<ht:approx_traffic>100+</ht:approx_traffic>'
                .'<description/>'
                .'<link>https://trends.google.com/trending/rss?geo=IR</link>'
                .'<pubDate>Sat, 5 Sep 2026 12:20:00 -0700</pubDate>'
                .'<ht:picture/>'
                .'<ht:picture_source/>'
                .'</item>',
            $titles,
        ));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<rss version="2.0" xmlns:ht="https://trends.google.com/trending/rss">'
            .'<channel><title>Daily Search Trends</title>'.$items.'</channel>'
            .'</rss>';
    }

    /**
     * @param  list<string>  $titles
     */
    protected function fakeFeed(array $titles): void
    {
        Http::fake([
            'trends.google.com/trending/rss*' => Http::response(
                $this->sampleFeed($titles),
                200,
                ['Content-Type' => 'application/xml'],
            ),
        ]);
    }

    public function test_it_picks_a_random_unused_candidate_and_returns_the_others_too(): void
    {
        $this->fakeFeed(['topic one', 'topic two', 'topic three']);

        $company = Company::factory()->create();

        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertTrue($result->successful);
        $this->assertContains($result->topic, ['topic one', 'topic two', 'topic three']);
        $this->assertFalse($result->reused);
        $this->assertSame('IR', $result->geo);
        $this->assertCount(3, $result->candidates);
        $this->assertContains($result->topic, $result->candidates);
    }

    public function test_it_maps_locale_to_the_right_geo(): void
    {
        $this->fakeFeed(['topic one']);

        $company = Company::factory()->create();
        app(GoogleTrendsService::class)->pickTopic($company, 'en');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://trends.google.com/trending/rss?geo=US');
    }

    public function test_it_excludes_topics_already_used_by_this_company(): void
    {
        $this->fakeFeed(['topic one', 'topic two']);

        $company = Company::factory()->create();

        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'topic' => 'topic one',
            'topic_normalized' => WordPressContentPost::normalizeTopic('topic one'),
        ]);

        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertTrue($result->successful);
        $this->assertSame('topic two', $result->topic);
        $this->assertFalse($result->reused);
        $this->assertSame(['topic two'], $result->candidates);
    }

    public function test_it_excludes_topics_used_by_a_different_company(): void
    {
        $this->fakeFeed(['topic one']);

        $other = Company::factory()->create();
        WordPressContentPost::factory()->create([
            'company_id' => $other->id,
            'topic' => 'topic one',
            'topic_normalized' => WordPressContentPost::normalizeTopic('topic one'),
        ]);

        $company = Company::factory()->create();
        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        // Repeat-avoidance is per company: another company's history must
        // not narrow this company's candidates.
        $this->assertTrue($result->successful);
        $this->assertSame('topic one', $result->topic);
    }

    public function test_when_every_candidate_is_used_it_reuses_the_least_recently_used_one(): void
    {
        $this->fakeFeed(['topic one', 'topic two']);

        $company = Company::factory()->create();

        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'topic' => 'topic one',
            'topic_normalized' => WordPressContentPost::normalizeTopic('topic one'),
            'created_at' => now()->subDays(10),
        ]);
        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'topic' => 'topic two',
            'topic_normalized' => WordPressContentPost::normalizeTopic('topic two'),
            'created_at' => now()->subDay(),
        ]);

        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertTrue($result->successful);
        $this->assertTrue($result->reused);
        $this->assertSame('topic one', $result->topic);
        // Even though everything is "used", the full set is still offered
        // to the content-generation prompt as candidates.
        $this->assertCount(2, $result->candidates);
    }

    public function test_normalization_treats_near_duplicates_as_the_same_topic(): void
    {
        $this->fakeFeed(['  Topic ONE!! ']);

        $company = Company::factory()->create();
        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'topic' => 'topic one',
            'topic_normalized' => WordPressContentPost::normalizeTopic('topic one'),
        ]);

        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertTrue($result->successful);
        $this->assertTrue($result->reused);
    }

    public function test_the_candidate_list_is_capped_to_a_handful(): void
    {
        $this->fakeFeed(array_map(fn (int $i): string => "topic {$i}", range(1, 12)));

        $company = Company::factory()->create();
        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertTrue($result->successful);
        $this->assertLessThanOrEqual(8, count($result->candidates));
    }

    public function test_a_network_failure_returns_a_typed_reason_without_throwing(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $company = Company::factory()->create();
        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertFalse($result->successful);
        $this->assertSame(TrendFailureReason::RequestFailed, $result->reason);
    }

    public function test_an_error_status_returns_the_request_failed_reason(): void
    {
        Http::fake(['trends.google.com/*' => Http::response('', 503)]);

        $company = Company::factory()->create();
        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertFalse($result->successful);
        $this->assertSame(TrendFailureReason::RequestFailed, $result->reason);
    }

    public function test_malformed_xml_returns_the_feed_unavailable_reason(): void
    {
        Http::fake(['trends.google.com/*' => Http::response('<not-even <closed', 200)]);

        $company = Company::factory()->create();
        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertFalse($result->successful);
        $this->assertSame(TrendFailureReason::FeedUnavailable, $result->reason);
    }

    public function test_a_response_with_no_channel_returns_the_feed_unavailable_reason(): void
    {
        Http::fake(['trends.google.com/*' => Http::response('<?xml version="1.0"?><not-rss/>', 200)]);

        $company = Company::factory()->create();
        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertFalse($result->successful);
        $this->assertSame(TrendFailureReason::FeedUnavailable, $result->reason);
    }

    public function test_zero_usable_titles_returns_the_no_candidates_reason(): void
    {
        $this->fakeFeed([]);

        $company = Company::factory()->create();
        $result = app(GoogleTrendsService::class)->pickTopic($company, 'fa');

        $this->assertFalse($result->successful);
        $this->assertSame(TrendFailureReason::NoCandidates, $result->reason);
    }

    public function test_an_unmapped_locale_falls_back_to_the_us_geo(): void
    {
        $this->fakeFeed(['topic one']);

        $company = Company::factory()->create();
        app(GoogleTrendsService::class)->pickTopic($company, 'xx');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://trends.google.com/trending/rss?geo=US');
    }
}
