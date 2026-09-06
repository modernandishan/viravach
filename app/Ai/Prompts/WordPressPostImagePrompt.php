<?php

namespace App\Ai\Prompts;

/**
 * The featured-image prompt for a generated article. Pure string builder,
 * sibling to CompanyImagePrompt.
 *
 * The alt text is NOT written here — the article generation already produced
 * an SEO alt containing the focus keyword, and it is that text which gets
 * published. This prompt only has to describe the picture.
 */
class WordPressPostImagePrompt
{
    public static function build(string $topic, string $imageAlt, ?string $industry): string
    {
        $context = $industry !== null && $industry !== ''
            ? "The company works in: {$industry}.\n"
            : '';

        return "A photorealistic, professional editorial header image for a B2B\n"
            ."article titled \"{$topic}\".\n"
            .$context
            ."The image should show: {$imageAlt}\n"
            ."\n"
            ."Style: clean commercial photography, natural lighting, shallow\n"
            ."depth of field, realistic industrial or business setting.\n"
            ."No text, no logos, no watermarks, no readable signage, no\n"
            .'collages, no distorted hands or faces.';
    }
}
