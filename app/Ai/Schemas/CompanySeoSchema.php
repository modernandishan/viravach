<?php

namespace App\Ai\Schemas;

use Illuminate\Support\Facades\Validator;

/**
 * Single source of truth for the per-locale company SEO payload, mirroring
 * CompanyContentSchema: definition() drives validation (rules/validate)
 * and the plain-text prompt spec (promptSpec).
 */
class CompanySeoSchema
{
    /**
     * Structural definition with limits.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definition(): array
    {
        return [
            'meta_title' => ['type' => 'string', 'min' => 30, 'max' => 60],
            'meta_description' => ['type' => 'string', 'min' => 120, 'max' => 160],
            'focus_keyword' => ['type' => 'string', 'min' => 2, 'max' => 60],
            'keyword_candidates' => ['type' => 'strings', 'min' => 3, 'max' => 5, 'item_min' => 2, 'item_max' => 60],
            'meta_keywords' => ['type' => 'strings', 'min' => 3, 'max' => 5, 'item_min' => 2, 'item_max' => 60],
        ];
    }

    /**
     * Laravel validation rules for ONE locale's payload.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'meta_title' => ['required', 'string', 'min:30', 'max:60'],
            'meta_description' => ['required', 'string', 'min:120', 'max:160'],
            'focus_keyword' => ['required', 'string', 'min:2', 'max:60'],
            'keyword_candidates' => ['required', 'array', 'min:3', 'max:5'],
            'keyword_candidates.*' => ['required', 'string', 'min:2', 'max:60'],
            'meta_keywords' => ['required', 'array', 'min:3', 'max:5'],
            'meta_keywords.*' => ['required', 'string', 'min:2', 'max:60'],
        ];
    }

    /**
     * Plain-text spec of the schema, generated from definition(), to be
     * embedded in the model prompt later.
     */
    public static function promptSpec(): string
    {
        $lines = [];

        foreach (self::definition() as $key => $spec) {
            if ($spec['type'] === 'strings') {
                $lines[] = sprintf('%s: array of %d-%d items, each: string, %d-%d chars', $key, $spec['min'], $spec['max'], $spec['item_min'], $spec['item_max']);
            } else {
                $lines[] = sprintf('%s: string, %d-%d chars', $key, $spec['min'], $spec['max']);
            }
        }

        $lines[] = 'All string values must be plain text without HTML tags.';

        return implode("\n", $lines);
    }

    /**
     * Best-effort salvage mirroring CompanyContentSchema::repair(): strings
     * over their max are truncated on a word boundary (preferring a sentence
     * end within the last 20% of the allowance); under-length values are
     * left alone for the model to fix.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function repair(array $payload): array
    {
        foreach (self::definition() as $key => $spec) {
            $value = $payload[$key] ?? null;

            if ($spec['type'] === 'strings' && is_array($value)) {
                foreach (array_values($value) as $index => $item) {
                    if (is_string($item) && mb_strlen($item) > $spec['item_max']) {
                        $payload[$key][$index] = self::truncate($item, (int) $spec['item_max']);
                    }
                }
            } elseif (is_string($value) && mb_strlen($value) > $spec['max']) {
                $payload[$key] = self::truncate($value, (int) $spec['max']);
            }
        }

        return $payload;
    }

    private static function truncate(string $text, int $max): string
    {
        $cut = mb_substr($text, 0, $max);

        $windowStart = (int) floor($max * 0.8);
        $window = mb_substr($cut, $windowStart);

        $lastSentence = -1;

        foreach (['.', '!', '?', '؟', '۔', '。', '…'] as $punctuation) {
            $pos = mb_strrpos($window, $punctuation);

            if ($pos !== false) {
                $lastSentence = max($lastSentence, $pos);
            }
        }

        if ($lastSentence >= 0) {
            return rtrim(mb_substr($cut, 0, $windowStart + $lastSentence + 1));
        }

        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > 0) {
            return rtrim(mb_substr($cut, 0, $space));
        }

        return $cut;
    }

    /**
     * Validate ONE locale's payload: the Laravel rules plus the same extras
     * as CompanyContentSchema — no HTML tags inside any string, and a
     * strict shape (no keys outside the definition).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, string> field => error, empty when valid
     */
    public static function validate(array $payload): array
    {
        $errors = [];

        foreach (array_keys($payload) as $key) {
            if (! array_key_exists($key, self::definition())) {
                $errors[(string) $key] = 'Unknown field: not part of the schema.';
            }
        }

        $validator = Validator::make($payload, self::rules());

        foreach ($validator->errors()->messages() as $field => $messages) {
            $errors[$field] ??= (string) $messages[0];
        }

        foreach (self::definition() as $key => $spec) {
            $value = $payload[$key] ?? null;

            if ($spec['type'] === 'strings' && is_array($value)) {
                foreach (array_values($value) as $index => $item) {
                    if (is_string($item) && strip_tags($item) !== $item) {
                        $errors[$key.'.'.$index] ??= 'HTML tags are not allowed.';
                    }
                }
            } elseif (is_string($value) && strip_tags($value) !== $value) {
                $errors[$key] ??= 'HTML tags are not allowed.';
            }
        }

        return $errors;
    }
}
