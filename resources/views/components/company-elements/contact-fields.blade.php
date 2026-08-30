@props([])

<div class="fv-row mb-7">
    <label class="form-label">{{ __('companies.field_website') }}</label>
    <div class="input-group input-group-lg input-group-solid" dir="ltr">
        <span class="input-group-text">https://</span>
        <input type="text" wire:model="website" class="form-control form-control-solid @error('website') is-invalid @enderror" />
    </div>
    <div class="form-text">{{ __('companies.field_website_hint') }}</div>
    @error('website')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="fv-row mb-7">
    <label class="form-label">{{ __('companies.field_email') }}</label>
    <input type="email" dir="ltr" wire:model="email" class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror" />
    @error('email')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="fv-row mb-7">
    <label class="form-label">{{ __('companies.field_phones') }}</label>
    {{-- Tagify rewrites the input's DOM, so keep it inside wire:ignore and
         sync tag values back to the Livewire `phones` array ourselves. The
         Alpine init() re-runs whenever the wrapper (re)enters the DOM (e.g.
         wizard step navigation), re-seeding from the current state. --}}
    <div wire:ignore
         x-data="{
             init() {
                 const tagify = new Tagify(this.$refs.phonesInput);
                 tagify.addTags(this.$wire.phones ?? []);
                 tagify.on('change', () => {
                     this.$wire.set('phones', tagify.value.map(tag => tag.value), false);
                 });
             }
         }">
        <input type="text" x-ref="phonesInput" dir="ltr"
               class="form-control form-control-lg form-control-solid @error('phones') is-invalid @enderror @error('phones.*') is-invalid @enderror" />
    </div>
    <div class="form-text">{{ __('companies.field_phones_hint') }}</div>
    @error('phones')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('phones.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="row mb-0">
    @foreach (\App\Support\CompanySocialPlatforms::PLATFORMS as $platform => $prefix)
        <div class="col-12 fv-row mb-7">
            <label class="form-label">{{ __('companies.'.\App\Support\CompanySocialPlatforms::labelKey($platform)) }}</label>
            @if ($prefix !== null)
                <div class="input-group input-group-lg input-group-solid" dir="ltr">
                    <span class="input-group-text" dir="ltr">{{ $prefix }}</span>
                    <input type="text" dir="ltr" wire:model="socialLinks.{{ $platform }}"
                           @if (trans()->has('companies.field_social_'.$platform.'_placeholder')) placeholder="{{ __('companies.field_social_'.$platform.'_placeholder') }}" @endif
                           class="form-control form-control-solid @error('socialLinks.'.$platform) is-invalid @enderror" />
                </div>
            @else
                <input type="url" dir="ltr" wire:model="socialLinks.{{ $platform }}"
                       placeholder="https://example.com"
                       class="form-control form-control-lg form-control-solid @error('socialLinks.'.$platform) is-invalid @enderror" />
            @endif
            @error('socialLinks.'.$platform)
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    @endforeach
</div>
