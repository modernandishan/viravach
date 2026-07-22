@props(['logo' => null, 'existingLogoUrl' => null])

<div class="fv-row mb-0">
    <label class="form-label">{{ __('companies.field_logo') }}</label>
    <div class="d-flex align-items-center gap-5">
        <div class="symbol symbol-75px bg-light">
            @if ($logo)
                <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('companies.field_logo') }}" class="p-2">
            @elseif ($existingLogoUrl)
                <img src="{{ $existingLogoUrl }}" alt="{{ __('companies.field_logo') }}" class="p-2">
            @endif
        </div>
        <div class="flex-grow-1">
            <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp"
                   class="form-control form-control-solid @error('logo') is-invalid @enderror" />
            <div class="form-text">{{ __('companies.field_logo_hint') }}</div>
            @error('logo')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
