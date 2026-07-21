@props(['logo' => null, 'gallery' => [], 'existingLogoUrl' => null, 'existingGalleryUrls' => []])

<div class="fv-row mb-10">
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

<div class="fv-row mb-0">
    <label class="form-label">{{ __('companies.field_gallery') }}</label>
    <input type="file" wire:model="gallery" multiple accept="image/png,image/jpeg,image/webp"
           class="form-control form-control-solid @error('gallery.*') is-invalid @enderror" />
    <div class="form-text">{{ __('companies.field_gallery_hint') }}</div>
    @error('gallery.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    <div class="d-flex flex-wrap gap-3 mt-3">
        @foreach ($gallery as $image)
            <div class="symbol symbol-75px bg-light">
                <img src="{{ $image->temporaryUrl() }}" alt="" class="p-2">
            </div>
        @endforeach
        @foreach ($existingGalleryUrls as $url)
            <div class="symbol symbol-75px bg-light">
                <img src="{{ $url }}" alt="" class="p-2">
            </div>
        @endforeach
    </div>
</div>
