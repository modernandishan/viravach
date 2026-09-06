<?php

namespace App\Ai\Schemas;

use Illuminate\Support\Facades\Validator;

/**
 * The field contract for one generated WordPress article. Single source of
 * truth: the prompt embeds promptSpec() verbatim, and the job validates the
 * model's answer with rules(), so the two can never drift apart.
 */
class WordPressPostSchema
{
    /**
     * The spec handed to the model, written as an annotated JSON skeleton
     * because models follow a shape far more reliably than a prose list.
     */
    public static function promptSpec(): string
    {
        return <<<'SPEC'
{
  "title": "string, 45-70 characters, contains the focus keyword once",
  "excerpt": "string, 120-160 characters, a meta description that reads as a sentence",
  "focus_keyword": "string, 2-5 words, the single term this article targets",
  "body": "string, valid HTML, 700-1200 words, using only <h2>, <h3>, <p>, <ul>, <li>, <strong> tags",
  "image_alt": "string, 8-16 words, describes the featured image and contains the focus keyword"
}
SPEC;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:200'],
            'excerpt' => ['required', 'string', 'min:40', 'max:400'],
            'focus_keyword' => ['required', 'string', 'min:2', 'max:120'],
            'body' => ['required', 'string', 'min:400'],
            'image_alt' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string> Human-readable problems, empty when the payload is good.
     */
    public static function validate(array $payload): array
    {
        return Validator::make($payload, self::rules())->errors()->all();
    }
}
