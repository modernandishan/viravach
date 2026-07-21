@props([])

<div class="fv-row mb-7">
    <label class="form-label">{{ __('companies.field_website') }}</label>
    <input type="text" wire:model="website" class="form-control form-control-lg form-control-solid @error('website') is-invalid @enderror" />
    @error('website')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="fv-row mb-7">
    <label class="form-label">{{ __('companies.field_email') }}</label>
    <input type="email" wire:model="email" class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror" />
    @error('email')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="fv-row mb-7">
    <label class="form-label">{{ __('companies.field_phones') }}</label>
    <input type="text" wire:model="phones" class="form-control form-control-lg form-control-solid @error('phones') is-invalid @enderror" />
    <div class="form-text">{{ __('companies.field_phones_hint') }}</div>
    @error('phones')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="row mb-0">
    <div class="col-md-6 fv-row mb-7">
        <label class="form-label">{{ __('companies.field_social_instagram') }}</label>
        <input type="text" wire:model="socialInstagram" class="form-control form-control-lg form-control-solid @error('socialInstagram') is-invalid @enderror" />
        @error('socialInstagram')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 fv-row mb-7">
        <label class="form-label">{{ __('companies.field_social_telegram') }}</label>
        <input type="text" wire:model="socialTelegram" class="form-control form-control-lg form-control-solid @error('socialTelegram') is-invalid @enderror" />
        @error('socialTelegram')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 fv-row">
        <label class="form-label">{{ __('companies.field_social_linkedin') }}</label>
        <input type="text" wire:model="socialLinkedin" class="form-control form-control-lg form-control-solid @error('socialLinkedin') is-invalid @enderror" />
        @error('socialLinkedin')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 fv-row">
        <label class="form-label">{{ __('companies.field_social_website') }}</label>
        <input type="text" wire:model="socialWebsite" class="form-control form-control-lg form-control-solid @error('socialWebsite') is-invalid @enderror" />
        @error('socialWebsite')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>
