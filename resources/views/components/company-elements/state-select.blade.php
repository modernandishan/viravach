@props(['states'])

<div class="fv-row">
    <label class="form-label required">{{ __('companies.field_state') }}</label>
    <select wire:model="stateId" class="form-select form-select-lg form-select-solid @error('stateId') is-invalid @enderror">
        <option value="">{{ __('companies.field_select_state') }}</option>
        @foreach ($states as $state)
            <option value="{{ $state->id }}">{{ $state->name }}</option>
        @endforeach
    </select>
    @error('stateId')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
