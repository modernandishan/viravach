<?php

namespace App\Ai\Prompts;

/**
 * Builds the single text prompt sent to the image-generation endpoint (see
 * App\Ai\ImageGenerator) from the already-generated English content payload
 * and the CompanyInputCollector input. Pure string building — no model
 * call, no schema, no HTTP.
 */
class CompanyImagePrompt
{
    /**
     * @param  array<string, mixed>  $englishPayload  the source-locale (en) content payload
     * @param  array<string, mixed>  $input  the CompanyInputCollector payload
     */
    public static function build(array $englishPayload, array $input): string
    {
        $headline = trim((string) ($englishPayload['hero']['headline'] ?? ''));

        $offerings = collect((array) ($englishPayload['offerings'] ?? []))
            ->map(fn ($offering): string => trim((string) ($offering['title'] ?? '')))
            ->filter()
            ->values()
            ->all();

        $categories = array_values(array_filter((array) ($input['categories'] ?? [])));

        $subject = implode('. ', array_filter([
            $headline !== '' ? "Industry / business: {$headline}" : null,
            $offerings !== [] ? 'Products or services: '.implode(', ', $offerings) : null,
            $categories !== [] ? 'Business category: '.implode(', ', $categories) : null,
        ]));

        return 'A professional, photographic, editorial-style image representing the '
            .'following B2B industrial/export business. NOT a logo. NOT an icon. NOT '
            ."an infographic. NOT a collage or mockup. A single realistic photograph.\n"
            ."\n"
            .$subject."\n"
            ."\n"
            ."STRICT REQUIREMENTS — follow every one of these:\n"
            ."- ABSOLUTELY NO TEXT anywhere in the image: no letters, no numbers, no\n"
            ."  words, no watermarks, no signage, no labels on packaging, crates,\n"
            ."  vehicles or equipment, in any language or alphabet.\n"
            ."- Repeat: the image must contain NO TEXT, NO LETTERS, NO NUMBERS, NO\n"
            ."  WATERMARKS and NO SIGNAGE whatsoever, anywhere in the frame.\n"
            ."- No visible human faces — backs, silhouettes, distant figures, or no\n"
            ."  people at all are fine.\n"
            ."- No brand marks, logos, or trademarks of any kind.\n"
            ."- No national, regional or company flags.\n"
            ."- Wide, landscape composition suitable for a website page header.\n"
            ."- Natural lighting, real-world industrial or business setting relevant\n"
            ."  to the industry above, high photographic quality.\n";
    }
}
