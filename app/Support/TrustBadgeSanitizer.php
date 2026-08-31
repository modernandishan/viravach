<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * The escape hatch for rendering trust-badge HTML (Enamad, and any future
 * badge) stored as a raw string in settings. Only <a> and <img> tags, and
 * only the listed attributes on them, survive; everything else — including
 * <script> and its contents — is dropped entirely rather than unwrapped,
 * since nothing else is expected inside a trust-badge snippet.
 */
class TrustBadgeSanitizer
{
    /** @var array<int, string> */
    private const ALLOWED_TAGS = ['a', 'img'];

    /** @var array<int, string> */
    private const ALLOWED_ATTRIBUTES = ['href', 'src', 'alt', 'id', 'class', 'style', 'referrerpolicy'];

    public static function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;

        $previousSetting = libxml_use_internal_errors(true);
        // The XML prologue forces UTF-8 decoding: DOMDocument otherwise
        // assumes ISO-8859-1 for a fragment with no declared encoding,
        // which mangles Persian/Arabic text (e.g. inside an alt attribute).
        $document->loadHTML(
            '<?xml encoding="UTF-8"?><div>'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        $container = $document->getElementsByTagName('div')->item(0);

        if (! $container instanceof DOMElement) {
            return '';
        }

        self::clean($container);

        $result = '';
        foreach (iterator_to_array($container->childNodes) as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private static function clean(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if ($child instanceof DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement || ! in_array(strtolower($child->tagName), self::ALLOWED_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $name = strtolower($attribute->name);

                if (! in_array($name, self::ALLOWED_ATTRIBUTES, true) || ! self::isSafeAttributeValue($name, $attribute->value)) {
                    $child->removeAttribute($attribute->name);
                }
            }

            self::clean($child);
        }
    }

    /**
     * Blocks javascript:/vbscript: URIs on href, and data: URIs on href
     * (unnecessary and risky for a link target; still allowed on img src).
     */
    private static function isSafeAttributeValue(string $name, string $value): bool
    {
        if (! in_array($name, ['href', 'src'], true)) {
            return true;
        }

        $value = trim($value);

        if (preg_match('/^\s*(javascript|vbscript):/i', $value)) {
            return false;
        }

        return $name !== 'href' || ! str_starts_with(strtolower($value), 'data:');
    }
}
