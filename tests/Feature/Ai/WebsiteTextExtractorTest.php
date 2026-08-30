<?php

namespace Tests\Feature\Ai;

use App\Ai\Input\WebsiteTextExtractor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteTextExtractorTest extends TestCase
{
    private const PAGE_HTML = <<<'HTML'
        <!doctype html>
        <html>
        <head>
            <title>Acme Industrial</title>
            <style>body { color: red; } .banner { background: url(bg.png); }</style>
            <script>var analytics = {"track": true}; window.onload = () => alert('hi');</script>
        </head>
        <body>
            <header>
                <nav><a href="/menu">Menu item that must not survive extraction</a></nav>
            </header>
            <main>
                <h1>Welcome to Acme Industrial</h1>
                <p>We manufacture industrial insulation panels for oil and gas clients across the region.</p>
                <p>Our factory in Test Province exports to more than a dozen countries every single year.</p>
                <p>Every panel is pressure tested before it leaves the warehouse.</p>
            </main>
            <footer>Copyright footer that must not survive extraction either</footer>
            <noscript>Enable JavaScript noscript fallback text</noscript>
            <svg><title>svg decoration title</title></svg>
        </body>
        </html>
        HTML;

    public function test_a_normal_html_page_yields_cleaned_text(): void
    {
        Http::fake([
            '*' => Http::response(self::PAGE_HTML, 200, ['Content-Type' => 'text/html; charset=UTF-8']),
        ]);

        $text = (new WebsiteTextExtractor)->extract('http://acme.example');

        $this->assertNotNull($text);
        $this->assertStringContainsString('Welcome to Acme Industrial', $text);
        $this->assertStringContainsString('We manufacture industrial insulation panels', $text);

        // Boilerplate blocks and their inline code/styles are gone.
        $this->assertStringNotContainsString('analytics', $text);
        $this->assertStringNotContainsString('background', $text);
        $this->assertStringNotContainsString('Menu item', $text);
        $this->assertStringNotContainsString('Copyright footer', $text);
        $this->assertStringNotContainsString('noscript', $text);
        $this->assertStringNotContainsString('svg', $text);
        $this->assertStringNotContainsString('<', $text);

        // Paragraph structure survives as single newlines, no blank lines.
        $this->assertStringContainsString("Welcome to Acme Industrial\n", $text);
        $this->assertStringNotContainsString("\n\n", $text);
    }

    public function test_a_null_or_malformed_url_returns_null_without_any_http_call(): void
    {
        Http::fake();

        $extractor = new WebsiteTextExtractor;

        $this->assertNull($extractor->extract(null));
        $this->assertNull($extractor->extract('   '));
        $this->assertNull($extractor->extract('not-a-url'));
        $this->assertNull($extractor->extract('ftp://acme.example/page'));
        $this->assertNull($extractor->extract('http:///no-host'));

        Http::assertNothingSent();
    }

    public function test_a_500_response_returns_null_without_throwing(): void
    {
        Http::fake([
            '*' => Http::response('Server Error', 500),
        ]);

        $this->assertNull((new WebsiteTextExtractor)->extract('https://acme.example'));
    }

    public function test_a_connection_exception_returns_null_without_throwing(): void
    {
        Http::fake([
            '*' => fn () => throw new ConnectionException('cURL error 7: Failed to connect'),
        ]);

        $this->assertNull((new WebsiteTextExtractor)->extract('https://down.example'));
    }

    public function test_a_non_html_content_type_returns_null_without_throwing(): void
    {
        Http::fake([
            '*' => Http::response('%PDF-1.4 binary', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->assertNull((new WebsiteTextExtractor)->extract('https://acme.example/brochure.pdf'));
    }

    public function test_a_page_whose_visible_text_is_under_200_chars_returns_null(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html><body><p>Under construction. Coming soon.</p></body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $this->assertNull((new WebsiteTextExtractor)->extract('https://acme.example'));
    }

    public function test_a_very_long_page_is_truncated_to_at_most_6000_characters(): void
    {
        $paragraph = '<p>'.str_repeat('Acme manufactures industrial insulation panels for export. ', 120).'</p>';
        Http::fake([
            '*' => Http::response(
                '<html><body>'.$paragraph.str_repeat($paragraph, 10).'</body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $text = (new WebsiteTextExtractor)->extract('https://acme.example');

        $this->assertNotNull($text);
        $this->assertLessThanOrEqual(6000, mb_strlen($text));

        // The cut lands on a word boundary: the last word must be a
        // COMPLETE word from the page's vocabulary, never a fragment.
        $lastWord = (string) str($text)->afterLast(' ');
        $this->assertContains($lastWord, ['Acme', 'manufactures', 'industrial', 'insulation', 'panels', 'for', 'export.']);
    }

    public function test_the_second_call_for_the_same_url_does_not_hit_the_network(): void
    {
        Http::fake([
            '*' => Http::response(self::PAGE_HTML, 200, ['Content-Type' => 'text/html']),
        ]);

        $extractor = new WebsiteTextExtractor;

        $first = $extractor->extract('https://acme.example');
        $second = $extractor->extract('https://acme.example');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }
}
