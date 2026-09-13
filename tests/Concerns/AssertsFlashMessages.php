<?php

namespace Tests\Concerns;

use Livewire\Features\SupportTesting\Testable;
use PHPUnit\Framework\Assert;

/**
 * Assertions for messages flashed through `partials.flash-alerts`.
 *
 * WHY THIS EXISTS
 * ---------------
 * Flashed messages are no longer rendered as visible alert markup. The
 * partial collects them into a SweetAlert2 toast payload carried in an
 * `x-init` attribute on a `display: none` div, so:
 *
 *   * `assertSeeText()` can never see them — Livewire strips tags (and with
 *     them every attribute) before matching;
 *   * `assertSessionHas()` can never see them either — the partial *consumes*
 *     each key (`session()->forget()`) as it reads it, so by the time a test
 *     inspects the session the value is already gone. Only the key's
 *     membership in `_flash.new` / `_flash.old` survives the render.
 *
 * So the message itself is read back out of the payload the partial emitted,
 * JSON-decoded rather than string-matched. Decoding is the point: `Js::from()`
 * applies `JSON_HEX_APOS` / `JSON_HEX_QUOT`, so an apostrophe in a message
 * ("This plan's amount…") renders as `'` and would never match a literal
 * comparison against the translation string.
 */
trait AssertsFlashMessages
{
    /**
     * Assert a component flashed $expectedMessage under $sessionKey, and that
     * the partial turned it into a toast of $expectedIcon.
     *
     * @param  'success'|'error'|'warning'|'info'|null  $expectedIcon  null skips the icon check
     */
    protected function assertFlashMessage(
        Testable $component,
        string $sessionKey,
        string $expectedMessage,
        ?string $expectedIcon = null,
    ): void {
        $flashedKeys = array_merge(
            (array) session()->get('_flash.old', []),
            (array) session()->get('_flash.new', []),
        );

        Assert::assertContains(
            $sessionKey,
            $flashedKeys,
            "Failed asserting that [{$sessionKey}] was flashed. Flashed keys: ".
            ($flashedKeys === [] ? '(none)' : implode(', ', $flashedKeys)).'.',
        );

        $messages = $this->flashedToastMessages($component);

        Assert::assertContains(
            $expectedMessage,
            array_column($messages, 'title'),
            "Failed asserting that a flash toast carried [{$expectedMessage}]. Toasted: ".
            ($messages === [] ? '(none)' : implode(' | ', array_column($messages, 'title'))).'.',
        );

        if ($expectedIcon !== null) {
            $icons = array_column(
                array_filter($messages, fn (array $message): bool => $message['title'] === $expectedMessage),
                'icon',
            );

            Assert::assertContains(
                $expectedIcon,
                $icons,
                "Failed asserting that [{$expectedMessage}] toasted as [{$expectedIcon}]; got [".implode(', ', $icons).'].',
            );
        }
    }

    /**
     * Every toast `partials.flash-alerts` emitted in the component's last
     * render, decoded from the `JSON.parse('…')` payload it builds with
     * `Js::from()`.
     *
     * @return array<int, array{icon: string, title: string}>
     */
    protected function flashedToastMessages(Testable $component): array
    {
        preg_match_all(
            "/viravachFlashQueue \?\?= \[\]\)\.push\(\.\.\.JSON\.parse\('(.*?)'\)\)/",
            $component->html(),
            $matches,
        );

        $messages = [];

        foreach ($matches[1] as $payload) {
            // Two passes, because the captured text is a *JavaScript string
            // literal*, not JSON: Js::from()'s JSON_HEX_QUOT turns the quotes
            // that delimit the JSON's own strings into \u0022, which only the
            // JS string parser resolves. Wrapping the payload in quotes and
            // decoding it as a JSON string performs that same unescaping (it
            // is safe to wrap: every literal quote is hex-escaped, so the
            // payload cannot terminate the wrapper early). The result is the
            // JSON the browser would hand to JSON.parse().
            $json = json_decode('"'.$payload.'"');

            Assert::assertIsString($json, 'The flash-alerts payload was not a decodable JS string literal.');

            $decoded = json_decode($json, true);

            Assert::assertIsArray($decoded, 'The flash-alerts payload was not decodable JSON.');

            $messages = array_merge($messages, $decoded);
        }

        return $messages;
    }
}
