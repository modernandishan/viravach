<?php

use App\Ai\Schemas\CompanyContentSchema;
use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use Livewire\Component;

/**
 * Lets a company owner edit their own AI-generated content payload from the
 * public dashboard — the same companies.content JSON the Filament admin
 * editor (CompanyContentSection) edits, validated with the same
 * CompanyContentSchema::validate(). Each locale tab is seeded with the
 * FULL original payload for that locale (including the schema version and
 * markets.countries, neither of which has an input here), so a leaf edit
 * never loses a sibling key — no separate "merge over the original" step
 * is needed, the working copy already IS the merge.
 */
new class extends Component
{
    public Company $company;

    /** @var array<string, array<string, mixed>> */
    public array $content = [];

    public bool $hasContent = false;

    /**
     * Locale codes the user actually edited this session — only these are
     * validated and written back; every other locale's stored payload is
     * left completely untouched.
     *
     * @var array<string, bool>
     */
    public array $touchedLocales = [];

    public bool $justSaved = false;

    /**
     * Monotonic source for repeater rows' __rowId (see withRowIds()) — a
     * plain counter rather than a uuid so it round-trips deterministically
     * through Livewire's own state serialization.
     */
    public int $nextRowId = 1;

    private const REPEATER_KEYS = ['offerings', 'strengths', 'specs', 'faq'];

    public function mount(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $this->company = $company;

        $stored = is_array($company->content) ? $company->content : [];
        $this->hasContent = $stored !== [];

        if (! $this->hasContent) {
            return;
        }

        foreach (array_keys($this->locales()) as $code) {
            $payload = $stored[$code] ?? [];

            foreach (self::REPEATER_KEYS as $key) {
                if (isset($payload[$key]) && is_array($payload[$key])) {
                    $payload[$key] = $this->withRowIds($payload[$key]);
                }
            }

            $this->content[$code] = $payload;
        }
    }

    /**
     * @return array<string, array{name: string, script: string, native: string, regional: string}>
     */
    public function locales(): array
    {
        return (array) config('laravellocalization.supportedLocales');
    }

    /**
     * The Arab/Hebr/Mong/Tfng/Thaa scripts are the same set
     * LaravelLocalization::getCurrentLocaleDirection() checks — mirrored
     * here because that helper only ever reports the CURRENT app locale's
     * direction, and every tab here needs its OWN locale's direction
     * regardless of which locale the page itself is rendered in.
     */
    public function directionFor(string $code): string
    {
        $script = (string) data_get($this->locales(), "{$code}.script");

        return in_array($script, ['Arab', 'Hebr', 'Mong', 'Tfng', 'Thaa'], true) ? 'rtl' : 'ltr';
    }

    /**
     * Which tab opens by default: the current UI locale, falling back to
     * the site's fallback locale, and only then the first locale in
     * locales() — never a hardcoded 'en'. The tab STRIP order itself is
     * untouched (still config('laravellocalization.supportedLocales')
     * order); only the initially active tab is picked here.
     */
    public function defaultLocale(): string
    {
        $codes = array_keys($this->locales());

        if (in_array(app()->getLocale(), $codes, true)) {
            return app()->getLocale();
        }

        $fallback = (string) config('app.fallback_locale');

        if (in_array($fallback, $codes, true)) {
            return $fallback;
        }

        return $codes[0] ?? '';
    }

    /**
     * Ordered render blocks for one locale tab — 'fields' (plain leaf
     * inputs) or 'repeater' (add/remove rows) — in the exact order
     * CompanyContentSchema payloads are structured. A single source of
     * truth for both the PHP save-time logic (which never reads this) and
     * the Blade template's generic per-block renderer.
     *
     * @return list<array<string, mixed>>
     */
    public function blocks(): array
    {
        return [
            ['type' => 'fields', 'heading' => null, 'fields' => [
                'hero.headline' => ['label' => 'content_field_hero_headline', 'type' => 'text', 'max' => 'hero.fields.headline'],
                'hero.subheadline' => ['label' => 'content_field_hero_subheadline', 'type' => 'text', 'max' => 'hero.fields.subheadline'],
                'hero.image_alt' => ['label' => 'content_field_hero_image_alt', 'type' => 'text', 'max' => 'hero.fields.image_alt'],
            ]],
            ['type' => 'fields', 'heading' => 'profile_about', 'fields' => [
                'about.heading' => ['label' => 'content_field_heading', 'type' => 'text', 'max' => 'about.fields.heading'],
                'about.body' => ['label' => 'content_field_body', 'type' => 'textarea', 'rows' => 12, 'max' => 'about.fields.body'],
            ]],
            ['type' => 'repeater', 'key' => 'offerings', 'heading' => 'content_offerings', 'fields' => [
                'title' => ['label' => 'content_field_title', 'type' => 'text', 'max' => 'offerings.item.title'],
                'body' => ['label' => 'content_field_body', 'type' => 'textarea', 'rows' => 4, 'max' => 'offerings.item.body'],
            ]],
            ['type' => 'repeater', 'key' => 'strengths', 'heading' => 'content_strengths', 'fields' => [
                'title' => ['label' => 'content_field_title', 'type' => 'text', 'max' => 'strengths.item.title'],
                'body' => ['label' => 'content_field_body', 'type' => 'textarea', 'rows' => 4, 'max' => 'strengths.item.body'],
            ]],
            ['type' => 'fields', 'heading' => 'content_markets', 'readonlyCountries' => true, 'fields' => [
                'markets.heading' => ['label' => 'content_field_heading', 'type' => 'text', 'max' => 'markets.fields.heading'],
                'markets.body' => ['label' => 'content_field_body', 'type' => 'textarea', 'rows' => 6, 'max' => 'markets.fields.body'],
            ]],
            ['type' => 'repeater', 'key' => 'specs', 'heading' => 'content_specs', 'fields' => [
                'label' => ['label' => 'content_field_label', 'type' => 'text', 'max' => 'specs.item.label'],
                'value' => ['label' => 'content_field_value', 'type' => 'text', 'max' => 'specs.item.value'],
            ]],
            ['type' => 'repeater', 'key' => 'faq', 'heading' => 'content_faq', 'fields' => [
                'q' => ['label' => 'content_field_question', 'type' => 'text', 'max' => 'faq.item.q'],
                'a' => ['label' => 'content_field_answer', 'type' => 'textarea', 'rows' => 4, 'max' => 'faq.item.a'],
            ]],
            ['type' => 'fields', 'heading' => 'content_cta', 'fields' => [
                'cta.heading' => ['label' => 'content_field_heading', 'type' => 'text', 'max' => 'cta.fields.heading'],
                'cta.body' => ['label' => 'content_field_body', 'type' => 'textarea', 'rows' => 4, 'max' => 'cta.fields.body'],
            ]],
        ];
    }

    /**
     * حداکثر طول فیلد مستقیماً از تعریف اسکیما خوانده می‌شود تا محدودیت‌ها
     * تک‌منبعی بمانند — همان قرارداد CompanyContentSection::max().
     */
    public function maxLength(string $path): int
    {
        return (int) data_get(CompanyContentSchema::definition(), "{$path}.max");
    }

    public function itemMin(string $key): int
    {
        return (int) data_get(CompanyContentSchema::definition(), "{$key}.min");
    }

    public function itemMax(string $key): int
    {
        return (int) data_get(CompanyContentSchema::definition(), "{$key}.max");
    }

    /**
     * The single source of truth both the input's displayed value AND the
     * character counter read from — they bind to the same $code/$path
     * pair Livewire's own wire:model uses, so they can never disagree.
     * Native wire:model-bound inputs never get a server-rendered value
     * (Livewire fills them client-side from the wire:snapshot JSON only),
     * so every field also needs this explicit value in the markup.
     */
    public function fieldValue(string $code, string $path): string
    {
        return (string) data_get($this->content[$code] ?? [], $path, '');
    }

    public function charCount(string $code, string $path): int
    {
        return mb_strlen($this->fieldValue($code, $path));
    }

    /**
     * @return array<string, string>
     */
    private function emptyItem(string $key): array
    {
        return match ($key) {
            'offerings', 'strengths' => ['title' => '', 'body' => ''],
            'specs' => ['label' => '', 'value' => ''],
            'faq' => ['q' => '', 'a' => ''],
            default => [],
        };
    }

    /**
     * Tags each row with a stable, never-reused __rowId so repeater rows
     * can carry a wire:key that survives add/remove — the array index
     * alone is not stable, since removeItem() reindexes the array and
     * would otherwise make Livewire's DOM morph reuse a row's old node
     * (and its stale value) for whatever row now sits at that index.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function withRowIds(array $items): array
    {
        return array_map(function (array $item): array {
            $item['__rowId'] = $this->nextRowId++;

            return $item;
        }, array_values($items));
    }

    /**
     * __rowId exists only in the working copy, for wire:key — it is not
     * part of CompanyContentSchema and must never reach validate() or the
     * saved payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function stripRowIds(array $payload): array
    {
        foreach (self::REPEATER_KEYS as $key) {
            if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                continue;
            }

            $payload[$key] = array_values(array_map(
                fn (array $item): array => array_diff_key($item, ['__rowId' => true]),
                $payload[$key],
            ));
        }

        return $payload;
    }

    public function addItem(string $locale, string $key): void
    {
        $items = $this->content[$locale][$key] ?? [];

        if (count($items) >= $this->itemMax($key)) {
            return;
        }

        $newItem = $this->emptyItem($key);
        $newItem['__rowId'] = $this->nextRowId++;
        $items[] = $newItem;
        $this->content[$locale][$key] = $items;
        $this->markTouched($locale);
    }

    public function removeItem(string $locale, string $key, int $index): void
    {
        $items = $this->content[$locale][$key] ?? [];

        if (count($items) <= $this->itemMin($key)) {
            return;
        }

        unset($items[$index]);
        $this->content[$locale][$key] = array_values($items);
        $this->markTouched($locale);
    }

    /**
     * Livewire's own lifecycle hook: fires for every property write that
     * comes from a wire:model sync, so every plain text/textarea edit is
     * caught here without wiring an individual handler per field.
     * addItem()/removeItem() above mark their locale directly, since a
     * PHP-side array mutation inside an action method does not go through
     * this hook.
     */
    public function updated(string $name): void
    {
        if (! str_starts_with($name, 'content.')) {
            return;
        }

        [, $locale] = explode('.', $name, 3);

        $this->markTouched($locale);
    }

    private function markTouched(string $locale): void
    {
        $this->touchedLocales[$locale] = true;
        $this->justSaved = false;
    }

    public function save(): void
    {
        abort_unless($this->company->user_id === auth()->id(), 403);

        if ($this->company->contentRecord?->status?->isProcessing() ?? false) {
            abort(409, __('companies.content_editor_locked'));
        }

        if (! $this->hasContent || $this->touchedLocales === []) {
            return;
        }

        $this->resetErrorBag();

        $original = is_array($this->company->content) ? $this->company->content : [];
        $merged = $original;
        $errors = [];

        foreach (array_keys($this->touchedLocales) as $code) {
            if (! array_key_exists($code, $this->locales())) {
                continue;
            }

            $candidate = $this->stripRowIds($this->content[$code] ?? []);

            foreach (CompanyContentSchema::validate($candidate) as $field => $message) {
                $errors["content.{$code}.{$field}"] = $message;
            }

            $merged[$code] = $candidate;
        }

        if ($errors !== []) {
            foreach ($errors as $field => $message) {
                $this->addError($field, $message);
            }

            return;
        }

        $this->company->forceFill(['content' => $merged]);

        // Mirrors EditCompany::mutateFormDataBeforeSave(): only reset
        // review status when the content actually changed in value, not
        // merely because a field was interacted with.
        if ($merged !== $original) {
            $this->company->forceFill([
                'review_status' => CompanyReviewStatus::PendingReview,
                'reviewed_at' => null,
            ]);
        }

        $this->company->save();
        $this->company = $this->company->fresh();

        $this->touchedLocales = [];
        $this->justSaved = true;
    }
};
?>

@if ($hasContent)
    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>{{ __('companies.content_editor_title') }}</h2>
            </div>
        </div>
        <div class="card-body border-top p-9">
            <div class="text-muted fw-semibold fs-6 mb-5">{{ __('companies.content_editor_hint') }}</div>

            @if ($justSaved)
                <div class="alert alert-success">{{ __('companies.content_editor_saved') }}</div>
            @endif

            @if ($this->company->contentRecord?->status?->isProcessing())
                <div class="alert alert-warning">{{ __('companies.content_editor_locked') }}</div>
            @endif

            @php $defaultLocale = $this->defaultLocale(); @endphp
            <form wire:submit.prevent="save">
                <fieldset {{ $this->company->contentRecord?->status?->isProcessing() ? 'disabled' : '' }}>
                    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                        @foreach ($this->locales() as $code => $localeProps)
                            <li class="nav-item">
                                <a class="nav-link {{ $code === $defaultLocale ? 'active' : '' }}" data-bs-toggle="tab" href="#kt_content_editor_{{ $code }}">
                                    {{ $localeProps['native'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach ($this->locales() as $code => $localeProps)
                            <div class="tab-pane fade {{ $code === $defaultLocale ? 'show active' : '' }}" id="kt_content_editor_{{ $code }}" dir="{{ $this->directionFor($code) }}">
                                @foreach ($this->blocks() as $block)
                                    @if ($block['heading'] !== null)
                                        <h4 class="mt-8 mb-4">{{ __('companies.'.$block['heading']) }}</h4>
                                    @endif

                                    @if ($block['type'] === 'fields')
                                        @foreach ($block['fields'] as $fieldKey => $fieldMeta)
                                            @php
                                                $wireModel = "content.{$code}.{$fieldKey}";
                                                $max = $this->maxLength($fieldMeta['max']);
                                            @endphp
                                            <div class="fv-row mb-5">
                                                <label class="form-label">{{ __('companies.'.$fieldMeta['label']) }}</label>
                                                @if ($fieldMeta['type'] === 'textarea')
                                                    <textarea
                                                        wire:model.live.debounce.300ms="{{ $wireModel }}"
                                                        rows="{{ $fieldMeta['rows'] ?? 4 }}"
                                                        maxlength="{{ $max }}"
                                                        class="form-control form-control-solid @error($wireModel) is-invalid @enderror"
                                                    >{{ $this->fieldValue($code, $fieldKey) }}</textarea>
                                                @else
                                                    <input type="text"
                                                        wire:model.live.debounce.300ms="{{ $wireModel }}"
                                                        value="{{ $this->fieldValue($code, $fieldKey) }}"
                                                        maxlength="{{ $max }}"
                                                        class="form-control form-control-solid @error($wireModel) is-invalid @enderror" />
                                                @endif
                                                <div class="form-text text-end">{{ $this->charCount($code, $fieldKey) }} / {{ $max }}</div>
                                                @error($wireModel)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endforeach

                                        @if ($block['readonlyCountries'] ?? false)
                                            @php $countries = data_get($content[$code] ?? [], 'markets.countries', []); @endphp
                                            @if (! empty($countries))
                                                <div class="fv-row mb-5">
                                                    <label class="form-label">{{ __('companies.profile_export_countries') }}</label>
                                                    <div class="form-control form-control-solid" style="cursor: default;">
                                                        {{ implode('، ', $countries) }}
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    @else
                                        @php $items = $content[$code][$block['key']] ?? []; @endphp
                                        @foreach ($items as $i => $item)
                                            <div class="border rounded p-4 mb-3" wire:key="{{ $block['key'] }}-{{ $code }}-{{ $item['__rowId'] }}">
                                                @foreach ($block['fields'] as $subKey => $fieldMeta)
                                                    @php
                                                        $itemPath = "{$block['key']}.{$i}.{$subKey}";
                                                        $wireModel = "content.{$code}.{$itemPath}";
                                                        $max = $this->maxLength($fieldMeta['max']);
                                                    @endphp
                                                    <div class="fv-row mb-3">
                                                        <label class="form-label">{{ __('companies.'.$fieldMeta['label']) }}</label>
                                                        @if ($fieldMeta['type'] === 'textarea')
                                                            <textarea
                                                                wire:model.live.debounce.300ms="{{ $wireModel }}"
                                                                rows="{{ $fieldMeta['rows'] ?? 4 }}"
                                                                maxlength="{{ $max }}"
                                                                class="form-control form-control-solid @error($wireModel) is-invalid @enderror"
                                                            >{{ $this->fieldValue($code, $itemPath) }}</textarea>
                                                        @else
                                                            <input type="text"
                                                                wire:model.live.debounce.300ms="{{ $wireModel }}"
                                                                value="{{ $this->fieldValue($code, $itemPath) }}"
                                                                maxlength="{{ $max }}"
                                                                class="form-control form-control-solid @error($wireModel) is-invalid @enderror" />
                                                        @endif
                                                        <div class="form-text text-end">{{ $this->charCount($code, $itemPath) }} / {{ $max }}</div>
                                                        @error($wireModel)
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                @endforeach
                                                <button type="button" class="btn btn-sm btn-light-danger"
                                                        wire:click="removeItem('{{ $code }}', '{{ $block['key'] }}', {{ $i }})"
                                                        @if (count($items) <= $this->itemMin($block['key'])) disabled @endif>
                                                    {{ __('companies.content_remove_item') }}
                                                </button>
                                            </div>
                                        @endforeach
                                        <button type="button" class="btn btn-sm btn-light-primary mb-8"
                                                wire:click="addItem('{{ $code }}', '{{ $block['key'] }}')"
                                                @if (count($items) >= $this->itemMax($block['key'])) disabled @endif>
                                            {{ __('companies.content_add_item') }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end mt-5">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            {{ __('companies.button_update') }}
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
@endif
