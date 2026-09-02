<?php

namespace App\Services\Chat;

use App\Models\CompanyPublication;
use Illuminate\Support\Str;

/**
 * Builds the company facts block injected into ViraBot's system prompt at the
 * {context} placeholder, on every AI call in a company-scoped conversation.
 *
 * Read from the PUBLISHED snapshot only — never the live draft — in the
 * current app locale (GenerateAiChatReply switches the locale around this
 * call because the queue worker runs on the app default).
 *
 * Two rules govern what goes in:
 *
 *  1. The field list is driven by CompanyPublication's own #[Fillable]
 *     attribute via getFillable(). A column added to the publication in
 *     future is therefore picked up automatically and cannot silently leave
 *     the bot blind — the failure mode is an unpolished label, not a missing
 *     fact. Anything that must NOT be sent has to be named in EXCLUDED,
 *     which is a conscious act.
 *
 *  2. Everything is capped. This block is re-sent with every message
 *     alongside the conversation history, so an uncapped `content` payload
 *     (about.body alone allows 2500 characters) would dominate the window.
 */
class CompanyContextBuilder
{
    /**
     * Never sent to the model.
     *
     * - company_id: internal id, and the link back to the draft.
     * - published_at: internal scheduling.
     * - is_featured: internal merchandising, not a fact about the company.
     * - *_id: resolved to human-readable names further down instead.
     * - slug: emitted as a full URL instead.
     * - latitude/longitude: coordinates are not something a visitor asks a
     *   chat bot in prose, and they invite the model to recite numbers.
     *
     * Not listed here because they are not on the publication at all, and
     * must never be added to it: anything from `users` (owner identity,
     * e-mail, phone) or from `profiles` — the user's personal address,
     * national code, birth date and biography are personal data, entirely
     * separate from the company's business address, and must never be
     * published, rendered publicly, or reach this payload.
     *
     * Review state (review_status, reviewed_at) lives on the draft Company,
     * never on the publication, so it cannot leak through here either.
     *
     * @var array<int, string>
     */
    private const EXCLUDED = [
        'company_id',
        'published_at',
        'is_featured',
        'state_id',
        'country_id',
        'city_id',
        'latitude',
        'longitude',
        'slug',
    ];

    /**
     * Fields rendered by dedicated logic below rather than the generic loop.
     *
     * @var array<int, string>
     */
    private const HANDLED_SEPARATELY = [
        'name',
        'summary',
        'content',
        'address_line',
        'postal_code',
        'is_verified',
        'phones',
        'social_links',
    ];

    /**
     * Curated English labels. A field absent from this map still renders —
     * with a Str::headline() label — which is what keeps a newly added column
     * visible to the bot without anyone remembering to edit this class.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'legal_name' => 'Registered legal name',
        'legal_type' => 'Legal entity type',
        'registration_number' => 'Company registration number',
        'national_id' => 'National company ID',
        'established_at' => 'Established',
        'website' => 'Website',
        'email' => 'Email',
        'employee_range' => 'Company size (employees)',
    ];

    /** Character caps, tuned against the token budget — see the class docs. */
    private const CAP_SUMMARY = 350;

    private const CAP_ABOUT = 400;

    private const CAP_MARKETS = 300;

    private const CAP_OFFERING_BODY = 140;

    private const MAX_OFFERINGS = 3;

    private const MAX_STRENGTHS = 4;

    private const MAX_SPECS = 8;

    private const MAX_FAQ = 4;

    private const CAP_FAQ_ANSWER = 160;

    public function build(int $companyId): string
    {
        $publication = CompanyPublication::query()
            ->active()
            ->where('company_id', $companyId)
            ->with(['categories', 'state', 'country', 'city', 'exportCountries', 'media'])
            ->first();

        if (! $publication) {
            return '';
        }

        $locale = app()->getLocale();

        $lines = [
            'The user is currently viewing the public profile of the following company. Base company-specific answers ONLY on these verified facts from its Viravach profile. If a fact is not listed here, say you do not have that information — never infer or estimate it.',
            '',
            'Company name: '.$publication->name,
        ];

        $lines[] = 'Verified by Viravach: '.($publication->is_verified ? 'yes' : 'no');
        $lines[] = 'Public profile URL: '.route('companies.show', ['slug' => $publication->slug]);

        $lines = array_merge(
            $lines,
            $this->genericFields($publication),
            $this->locationLines($publication),
            $this->contactLines($publication),
            $this->taxonomyLines($publication),
            $this->summaryLines($publication, $locale),
            $this->contentLines($publication, $locale),
            $this->certificateLines($publication),
        );

        $missing = $this->missingFieldLabels($publication);

        if ($missing !== []) {
            $lines[] = '';
            $lines[] = 'The company has NOT provided the following on its profile: '.implode(', ', $missing)
                .'. If asked about any of these, state plainly that the information is not available on their Viravach profile.';
        }

        $fallbackNote = $this->localeFallbackNote($publication, $locale);

        if ($fallbackNote !== null) {
            $lines[] = '';
            $lines[] = $fallbackNote;
        }

        return implode("\n", $lines);
    }

    /**
     * Every fillable column that is neither excluded nor handled by dedicated
     * logic, with empty values omitted rather than sent as null.
     *
     * @return array<int, string>
     */
    private function genericFields(CompanyPublication $publication): array
    {
        $lines = [];

        foreach ($publication->getFillable() as $field) {
            if (in_array($field, self::EXCLUDED, true) || in_array($field, self::HANDLED_SEPARATELY, true)) {
                continue;
            }

            $value = $this->scalarValue($publication->{$field});

            if ($value === null) {
                continue;
            }

            $lines[] = (self::LABELS[$field] ?? Str::headline($field)).': '.$value;
        }

        return $lines;
    }

    /**
     * Renders a value as a short display string, or null when it is empty and
     * should be omitted entirely.
     */
    private function scalarValue(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y');
        }

        if (is_array($value)) {
            return implode(', ', array_filter(array_map('strval', $value)));
        }

        $string = trim((string) $value);

        return $string !== '' ? $string : null;
    }

    /**
     * The business address, composed from the snapshot. This is the company's
     * public business location — never a user's personal profile address,
     * which lives on `profiles` and is deliberately unreachable from here.
     *
     * @return array<int, string>
     */
    private function locationLines(CompanyPublication $publication): array
    {
        $lines = [];

        $parts = array_filter([
            trim((string) $publication->address_line),
            $publication->city?->name,
            $publication->state?->name,
            $publication->country?->name,
        ], fn (?string $part): bool => $part !== null && trim($part) !== '');

        if ($parts !== []) {
            $lines[] = 'Business address: '.implode(', ', $parts);
        } elseif ($publication->state) {
            // No street address published, but the province still locates them.
            $lines[] = 'Location: '.$publication->state->name;
        }

        if (filled($publication->postal_code)) {
            $lines[] = 'Postal code: '.$publication->postal_code;
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function contactLines(CompanyPublication $publication): array
    {
        $lines = [];

        if (filled($publication->phones)) {
            $lines[] = 'Phone numbers: '.implode(', ', (array) $publication->phones);
        }

        if (filled($publication->social_links)) {
            $social = collect((array) $publication->social_links)
                ->filter()
                ->map(fn (string $url, string $platform): string => Str::headline($platform).' ('.$url.')')
                ->implode(', ');

            if ($social !== '') {
                $lines[] = 'Social profiles: '.$social;
            }
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function taxonomyLines(CompanyPublication $publication): array
    {
        $lines = [];

        $categories = $publication->categories->pluck('title')->filter()->implode(', ');

        if ($categories !== '') {
            $lines[] = 'Categories: '.$categories;
        }

        // From the snapshot now, not the draft.
        $exportCountries = $publication->exportCountries->pluck('name')->filter()->implode(', ');

        if ($exportCountries !== '') {
            $lines[] = 'Exports to: '.$exportCountries;
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function summaryLines(CompanyPublication $publication, string $locale): array
    {
        $summary = trim((string) $publication->summary);

        return $summary !== ''
            ? ['Short summary: '.Str::limit($summary, self::CAP_SUMMARY, preserveWords: true)]
            : [];
    }

    /**
     * The AI-generated content payload, section by section, each capped. The
     * sections most useful to a buyer (offerings, specs, FAQ) are included in
     * preference to marketing prose.
     *
     * @return array<int, string>
     */
    private function contentLines(CompanyPublication $publication, string $locale): array
    {
        $content = $publication->contentFor($locale);

        if (! is_array($content)) {
            return [];
        }

        $lines = [];

        $about = trim(strip_tags((string) data_get($content, 'about.body', '')));

        if ($about !== '') {
            $lines[] = 'About: '.Str::limit($about, self::CAP_ABOUT, preserveWords: true);
        }

        $offerings = collect(data_get($content, 'offerings', []))
            ->take(self::MAX_OFFERINGS)
            ->map(fn (array $item): string => trim((string) ($item['title'] ?? '')).': '
                .Str::limit(trim(strip_tags((string) ($item['body'] ?? ''))), self::CAP_OFFERING_BODY, preserveWords: true))
            ->filter(fn (string $line): bool => trim($line, ': ') !== '');

        if ($offerings->isNotEmpty()) {
            $lines[] = 'Products / services offered:';
            foreach ($offerings as $offering) {
                $lines[] = '- '.$offering;
            }
        }

        $strengths = collect(data_get($content, 'strengths', []))
            ->take(self::MAX_STRENGTHS)
            ->map(fn (array $item): string => trim((string) ($item['title'] ?? '')))
            ->filter();

        if ($strengths->isNotEmpty()) {
            $lines[] = 'Key strengths: '.$strengths->implode(', ');
        }

        $marketsBody = trim(strip_tags((string) data_get($content, 'markets.body', '')));

        if ($marketsBody !== '') {
            $lines[] = 'Markets: '.Str::limit($marketsBody, self::CAP_MARKETS, preserveWords: true);
        }

        $specs = collect(data_get($content, 'specs', []))
            ->take(self::MAX_SPECS)
            ->map(fn (array $item): string => trim((string) ($item['label'] ?? '')).': '.trim((string) ($item['value'] ?? '')))
            ->filter(fn (string $line): bool => trim($line, ': ') !== '');

        if ($specs->isNotEmpty()) {
            $lines[] = 'Specifications: '.$specs->implode(' | ');
        }

        $faq = collect(data_get($content, 'faq', []))
            ->take(self::MAX_FAQ)
            ->map(fn (array $item): string => 'Q: '.trim((string) ($item['question'] ?? ''))
                .' A: '.Str::limit(trim(strip_tags((string) ($item['answer'] ?? ''))), self::CAP_FAQ_ANSWER, preserveWords: true))
            ->filter(fn (string $line): bool => ! str_contains($line, 'Q:  A: '));

        if ($faq->isNotEmpty()) {
            $lines[] = 'Published FAQ:';
            foreach ($faq as $entry) {
                $lines[] = '- '.$entry;
            }
        }

        return $lines;
    }

    /**
     * Certificates are a media collection, and "do they have certification X"
     * is exactly the kind of question the system prompt forbids guessing at.
     * Only the count and file names are exposed — never a signed URL.
     *
     * @return array<int, string>
     */
    private function certificateLines(CompanyPublication $publication): array
    {
        $certificates = $publication->getMedia('certificates');

        if ($certificates->isEmpty()) {
            return [];
        }

        $names = $certificates->map(fn ($media): string => (string) $media->name)->filter()->implode(', ');

        return ['Certificates uploaded to the profile ('.$certificates->count().'): '.$names
            .'. You can confirm these documents exist on the profile, but you cannot read their contents — do not describe what they certify.'];
    }

    /**
     * Names the publicly meaningful fields this company left empty, so "I do
     * not have that information" is grounded in the payload rather than
     * inferred from an absence the model has to notice on its own.
     *
     * @return array<int, string>
     */
    private function missingFieldLabels(CompanyPublication $publication): array
    {
        $checks = [
            'business address' => filled(trim((string) $publication->address_line)),
            'website' => filled($publication->website),
            'email address' => filled($publication->email),
            'phone number' => filled($publication->phones),
            'year established' => $publication->established_at !== null,
            'company size' => filled($publication->employee_range),
            'export countries' => $publication->exportCountries->isNotEmpty(),
            'certificates' => $publication->getMedia('certificates')->isNotEmpty(),
        ];

        return array_keys(array_filter($checks, fn (bool $present): bool => ! $present));
    }

    /**
     * Signals when the visitor's locale has no translation and the facts above
     * are therefore in the fallback language. Without this the model sees
     * English facts under an instruction to reply in Persian and has no way to
     * know whether the English is the company's own wording or a gap.
     */
    private function localeFallbackNote(CompanyPublication $publication, string $locale): ?string
    {
        $fallback = (string) config('app.fallback_locale');

        if ($locale === $fallback) {
            return null;
        }

        $translatable = ['name', 'summary', 'address_line'];
        $missing = [];

        foreach ($translatable as $field) {
            $localised = trim((string) $publication->getTranslation($field, $locale, false));
            $fallbackValue = trim((string) $publication->getTranslation($field, $fallback, false));

            if ($localised === '' && $fallbackValue !== '') {
                $missing[] = $field;
            }
        }

        $contentMissing = ! is_array($publication->contentFor($locale))
            || ! isset(((array) $publication->content)[$locale]);

        if ($missing === [] && ! $contentMissing) {
            return null;
        }

        return 'Note: this company has not translated all of its profile into the current language, '
            .'so some facts above appear in '.$fallback.'. Still reply in the language required by your language rule, '
            .'and translate the meaning where you can — but never invent a translated name, address or product name that '
            .'the company did not provide. Quote such values as they appear.';
    }
}
