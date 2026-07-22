@props(['addresses', 'states', 'citiesByState'])

@foreach ($addresses as $index => $address)
    <div class="card card-bordered mb-5" wire:key="address-row-{{ $index }}">
        <div class="card-header min-h-50px">
            <div class="card-title fs-6 fw-bold">{{ __('companies.address_row_title', ['number' => $index + 1]) }}</div>
            <div class="card-toolbar gap-3">
                <div class="form-check form-check-custom form-check-solid">
                    <input class="form-check-input" type="radio" name="primaryAddress" id="primary-address-{{ $index }}"
                           @checked(! empty($address['is_primary']))
                           wire:click="setPrimaryAddress({{ $index }})" />
                    <label class="form-check-label" for="primary-address-{{ $index }}">
                        {{ __('companies.address_is_primary') }}
                    </label>
                </div>
                @if (count($addresses) > 1)
                    <button type="button" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm"
                            data-bs-toggle="tooltip" title="{{ __('companies.button_remove_address') }}"
                            wire:click="removeAddressRow({{ $index }})">
                        <i class="ki-duotone ki-trash fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                        </i>
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label required">{{ __('companies.field_state') }}</label>
                    <select wire:model.live="addresses.{{ $index }}.state_id"
                            class="form-select form-select-solid @error("addresses.{$index}.state_id") is-invalid @enderror">
                        <option value="">{{ __('companies.field_select_state') }}</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error("addresses.{$index}.state_id")
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label">{{ __('companies.field_city') }}</label>
                    <select wire:model="addresses.{{ $index }}.city_id"
                            class="form-select form-select-solid @error("addresses.{$index}.city_id") is-invalid @enderror">
                        <option value="">{{ __('companies.field_select_city') }}</option>
                        @foreach (($address['state_id'] ? $citiesByState->get((int) $address['state_id'], collect()) : collect()) as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error("addresses.{$index}.city_id")
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label required">{{ __('companies.field_address_type') }}</label>
                    <select wire:model="addresses.{{ $index }}.type"
                            class="form-select form-select-solid @error("addresses.{$index}.type") is-invalid @enderror">
                        @foreach (['office', 'warehouse', 'factory', 'showroom'] as $type)
                            <option value="{{ $type }}">{{ __('companies.address_type_'.$type) }}</option>
                        @endforeach
                    </select>
                    @error("addresses.{$index}.type")
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label">{{ __('companies.field_postal_code') }}</label>
                    <input type="text" dir="ltr" wire:model="addresses.{{ $index }}.postal_code"
                           class="form-control form-control-solid @error("addresses.{$index}.postal_code") is-invalid @enderror" />
                    @error("addresses.{$index}.postal_code")
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 fv-row">
                    <label class="form-label required">{{ __('companies.field_address_line') }}</label>
                    <textarea rows="2" wire:model="addresses.{{ $index }}.address_line"
                              class="form-control form-control-solid @error("addresses.{$index}.address_line") is-invalid @enderror"></textarea>
                    @error("addresses.{$index}.address_line")
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
@endforeach

<button type="button" class="btn btn-light-primary" wire:click="addAddressRow">
    <i class="ki-duotone ki-plus fs-2"></i>
    {{ __('companies.button_add_address') }}
</button>

@error('addresses')
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
