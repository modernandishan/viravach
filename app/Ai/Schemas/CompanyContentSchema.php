<?php

namespace App\Ai\Schemas;

use Illuminate\Support\Facades\Validator;

/**
 * Single source of truth for the per-locale company content payload: the
 * same definition() drives validation (rules/validate) and the plain-text
 * spec embedded into the generation prompt (promptSpec).
 */
class CompanyContentSchema
{
    public const VERSION = 1;

    /**
     * Structural definition with limits. One entry per field:
     *  - scalar leaf:  ['type' => 'string'|'integer', 'min' => .., 'max' => ..]
     *  - object:       ['type' => 'object', 'fields' => [...]]
     *  - object array: ['type' => 'array', 'min' => .., 'max' => .., 'item' => [...]]
     *  - ISO2 codes:   ['type' => 'countries', 'min' => .., 'max' => ..]
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definition(): array
    {
        return [
            'v' => ['type' => 'integer', 'const' => self::VERSION],
            'hero' => [
                'type' => 'object',
                'fields' => [
                    'headline' => ['type' => 'string', 'min' => 20, 'max' => 70],
                    'subheadline' => ['type' => 'string', 'min' => 40, 'max' => 160],
                    'image_alt' => ['type' => 'string', 'min' => 20, 'max' => 125],
                ],
            ],
            'about' => [
                'type' => 'object',
                'fields' => [
                    'heading' => ['type' => 'string', 'min' => 10, 'max' => 70],
                    'body' => ['type' => 'string', 'min' => 800, 'max' => 2500],
                ],
            ],
            'offerings' => [
                'type' => 'array', 'min' => 3, 'max' => 8,
                'item' => [
                    'title' => ['type' => 'string', 'min' => 5, 'max' => 80],
                    'body' => ['type' => 'string', 'min' => 200, 'max' => 800],
                ],
            ],
            'strengths' => [
                'type' => 'array', 'min' => 3, 'max' => 6,
                'item' => [
                    'title' => ['type' => 'string', 'min' => 5, 'max' => 80],
                    'body' => ['type' => 'string', 'min' => 100, 'max' => 400],
                ],
            ],
            'markets' => [
                'type' => 'object',
                'fields' => [
                    'heading' => ['type' => 'string', 'min' => 10, 'max' => 70],
                    'body' => ['type' => 'string', 'min' => 200, 'max' => 1000],
                    'countries' => ['type' => 'countries', 'min' => 0, 'max' => 15],
                ],
            ],
            'specs' => [
                'type' => 'array', 'min' => 0, 'max' => 12,
                'item' => [
                    'label' => ['type' => 'string', 'min' => 2, 'max' => 60],
                    'value' => ['type' => 'string', 'min' => 1, 'max' => 120],
                ],
            ],
            'faq' => [
                'type' => 'array', 'min' => 4, 'max' => 8,
                'item' => [
                    'q' => ['type' => 'string', 'min' => 10, 'max' => 160],
                    'a' => ['type' => 'string', 'min' => 100, 'max' => 600],
                ],
            ],
            'cta' => [
                'type' => 'object',
                'fields' => [
                    'heading' => ['type' => 'string', 'min' => 10, 'max' => 70],
                    'body' => ['type' => 'string', 'min' => 50, 'max' => 300],
                ],
            ],
        ];
    }

    /**
     * Laravel validation rules for ONE locale's payload. Size limits mirror
     * definition(); the no-HTML and strict-shape extras are enforced
     * separately in validate().
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'v' => ['required', 'integer', 'in:'.self::VERSION],

            'hero' => ['required', 'array'],
            'hero.headline' => ['required', 'string', 'min:20', 'max:70'],
            'hero.subheadline' => ['required', 'string', 'min:40', 'max:160'],
            'hero.image_alt' => ['required', 'string', 'min:20', 'max:125'],

            'about' => ['required', 'array'],
            'about.heading' => ['required', 'string', 'min:10', 'max:70'],
            'about.body' => ['required', 'string', 'min:800', 'max:2500'],

            'offerings' => ['required', 'array', 'min:3', 'max:8'],
            'offerings.*.title' => ['required', 'string', 'min:5', 'max:80'],
            'offerings.*.body' => ['required', 'string', 'min:200', 'max:800'],

            'strengths' => ['required', 'array', 'min:3', 'max:6'],
            'strengths.*.title' => ['required', 'string', 'min:5', 'max:80'],
            'strengths.*.body' => ['required', 'string', 'min:100', 'max:400'],

            'markets' => ['required', 'array'],
            'markets.heading' => ['required', 'string', 'min:10', 'max:70'],
            'markets.body' => ['required', 'string', 'min:200', 'max:1000'],
            'markets.countries' => ['nullable', 'array', 'max:15'],
            'markets.countries.*' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],

            'specs' => ['nullable', 'array', 'max:12'],
            'specs.*.label' => ['required', 'string', 'min:2', 'max:60'],
            'specs.*.value' => ['required', 'string', 'min:1', 'max:120'],

            'faq' => ['required', 'array', 'min:4', 'max:8'],
            'faq.*.q' => ['required', 'string', 'min:10', 'max:160'],
            'faq.*.a' => ['required', 'string', 'min:100', 'max:600'],

            'cta' => ['required', 'array'],
            'cta.heading' => ['required', 'string', 'min:10', 'max:70'],
            'cta.body' => ['required', 'string', 'min:50', 'max:300'],
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
            self::appendSpecLines((string) $key, $spec, $lines);
        }

        $lines[] = 'All string values must be plain text without HTML tags.';

        return implode("\n", $lines);
    }

    /**
     * Validate ONE locale's payload: the Laravel rules plus two extras the
     * AI output must always satisfy — no HTML tags inside any string, and
     * a strict shape (no keys outside the definition, at any depth).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, string> field => error, empty when valid
     */
    public static function validate(array $payload): array
    {
        $errors = [];

        foreach (self::extraKeys($payload, self::definition()) as $path) {
            $errors[$path] = 'Unknown field: not part of the schema.';
        }

        $validator = Validator::make($payload, self::rules());

        foreach ($validator->errors()->messages() as $field => $messages) {
            $errors[$field] ??= (string) $messages[0];
        }

        foreach (self::htmlViolations($payload, self::definition()) as $path) {
            $errors[$path] ??= 'HTML tags are not allowed.';
        }

        return $errors;
    }

    /**
     * Dot-paths of payload keys that do not exist in the definition, at any
     * depth (objects and array items).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    private static function extraKeys(array $payload, array $definition, string $prefix = ''): array
    {
        $extra = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (! array_key_exists($key, $definition)) {
                $extra[] = $path;

                continue;
            }

            $spec = $definition[$key];
            $type = $spec['type'] ?? null;

            if ($type === 'object' && is_array($value)) {
                $extra = [...$extra, ...self::extraKeys($value, $spec['fields'], $path)];
            } elseif ($type === 'array' && is_array($value) && isset($spec['item'])) {
                foreach (array_values($value) as $index => $item) {
                    if (is_array($item)) {
                        $extra = [...$extra, ...self::extraKeys($item, $spec['item'], $path.'.'.$index)];
                    }
                }
            }
        }

        return $extra;
    }

    /**
     * Dot-paths of string values that contain HTML tags, resolved against
     * the definition so only schema strings are inspected.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    private static function htmlViolations(array $payload, array $definition, string $prefix = ''): array
    {
        $violations = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $spec = $definition[$key] ?? null;

            if (! is_array($spec)) {
                continue; // unknown key; already reported by extraKeys()
            }

            $type = $spec['type'] ?? null;

            if ($type === 'object' && is_array($value)) {
                $violations = [...$violations, ...self::htmlViolations($value, $spec['fields'], $path)];
            } elseif ($type === 'array' && is_array($value) && isset($spec['item'])) {
                foreach (array_values($value) as $index => $item) {
                    if (is_array($item)) {
                        $violations = [...$violations, ...self::htmlViolations($item, $spec['item'], $path.'.'.$index)];
                    }
                }
            } elseif ($type === 'countries' && is_array($value)) {
                foreach (array_values($value) as $index => $code) {
                    if (is_string($code) && strip_tags($code) !== $code) {
                        $violations[] = $path.'.'.$index;
                    }
                }
            } elseif ($type === 'string' && is_string($value) && strip_tags($value) !== $value) {
                $violations[] = $path;
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  list<string>  $lines
     */
    private static function appendSpecLines(string $path, array $spec, array &$lines): void
    {
        $type = $spec['type'] ?? null;

        if ($type === 'object') {
            foreach ($spec['fields'] as $key => $field) {
                self::appendSpecLines($path.'.'.$key, $field, $lines);
            }

            return;
        }

        if ($type === 'array') {
            $fields = implode('; ', array_map(
                fn (string $key, array $field): string => sprintf('%s: %s, %d-%d chars', $key, $field['type'], $field['min'], $field['max']),
                array_keys($spec['item']),
                $spec['item'],
            ));

            $lines[] = sprintf('%s: array of %d-%d items, each: {%s}', $path, $spec['min'], $spec['max'], $fields);

            return;
        }

        if ($type === 'countries') {
            $lines[] = sprintf('%s: array of %d-%d ISO 3166-1 alpha-2 country codes (2 uppercase letters)', $path, $spec['min'], $spec['max']);

            return;
        }

        if ($type === 'integer') {
            $lines[] = sprintf('%s: integer, must be %d', $path, $spec['const']);

            return;
        }

        $lines[] = sprintf('%s: string, %d-%d chars', $path, $spec['min'], $spec['max']);
    }
}
