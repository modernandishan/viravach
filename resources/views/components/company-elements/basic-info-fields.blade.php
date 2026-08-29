@props(['singleLocale' => false])

@if ($singleLocale)
    <div class="fv-row mb-7">
        <label class="form-label required">{{ __('companies.field_name') }}</label>
        <input type="text" wire:model="name"
               class="form-control form-control-lg form-control-solid @error('name') is-invalid @enderror" />
        @error('name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="fv-row mb-0">
        <label class="form-label">{{ __('companies.field_description') }}</label>
        <x-tiptap-editor wire:model="description" :placeholder="__('companies.field_description')" />
        <div class="form-text">{{ __('companies.field_description_hint') }}</div>
    </div>
@else
    @php
        $locales = config('laravellocalization.supportedLocales');
        $orderedLocales = collect(['fa' => $locales['fa']])->merge($locales);
    @endphp

    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
        @foreach ($orderedLocales as $code => $localeProps)
            <li class="nav-item">
                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" href="#kt_company_basic_info_{{ $code }}">
                    {{ $localeProps['native'] }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($orderedLocales as $code => $localeProps)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="kt_company_basic_info_{{ $code }}">
                <div class="fv-row mb-7">
                    <label class="form-label {{ $code === config('app.fallback_locale') ? 'required' : '' }}">
                        {{ __('companies.field_name') }} ({{ $localeProps['native'] }})
                    </label>
                    <input type="text" wire:model="name.{{ $code }}"
                           class="form-control form-control-lg form-control-solid @error('name.'.$code) is-invalid @enderror" />
                    @error('name.'.$code)
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="fv-row mb-0">
                    <label class="form-label">{{ __('companies.field_description') }} ({{ $localeProps['native'] }})</label>

                    <x-tiptap-editor class="tiptap" wire:model="description.{{ $code }}" :placeholder="__('companies.field_description')" />
                    <div class="form-text">{{ __('companies.field_description_hint') }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endif
