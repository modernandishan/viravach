<?php
// resources/views/components/subscription-elements/⚡plan-comparison-table.blade.php

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $duration = 'quarterly';

    /** Maps each duration key to the plan slug suffix defined by the seeder. */
    protected array $durationSuffixes = [
        'quarterly' => '-3-months',
        'semiannual' => '-6-months',
        'yearly' => '-1-year',
    ];

    public function setDuration(string $duration): void
    {
        if (array_key_exists($duration, $this->durationSuffixes)) {
            $this->duration = $duration;
            unset($this->visiblePlans, $this->featureRows);
        }
    }

    #[Computed]
    public function allPlans(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with('features')
            ->get();
    }

    /**
     * Free plan + the paid plans matching the selected duration.
     */
    #[Computed]
    public function visiblePlans(): Collection
    {
        $suffix = $this->durationSuffixes[$this->duration];

        return $this->allPlans()
            ->filter(fn (Plan $plan) => $plan->isFree() || str_ends_with($plan->slug, $suffix))
            ->values();
    }

    /**
     * Union of base feature keys across visible plans, keeping seeder order.
     * Feature slugs are prefixed with the plan slug, so the prefix is stripped
     * to obtain a comparable base key.
     *
     * @return BaseCollection<int, array{key: string, name: string}>
     */
    #[Computed]
    public function featureRows(): BaseCollection
    {
        $rows = collect();

        foreach ($this->visiblePlans() as $plan) {
            foreach ($plan->features->sortBy('sort_order') as $feature) {
                $key = $this->baseFeatureKey($plan, $feature->slug);
                if (! $rows->has($key)) {
                    $rows->put($key, ['key' => $key, 'name' => $feature->name]);
                }
            }
        }

        return $rows->values();
    }

    protected function baseFeatureKey(Plan $plan, string $featureSlug): string
    {
        return str_starts_with($featureSlug, $plan->slug.'-')
            ? substr($featureSlug, strlen($plan->slug) + 1)
            : $featureSlug;
    }

    /**
     * Returns the raw feature value for a plan, or null when absent.
     */
    public function featureValue(Plan $plan, string $baseKey): ?string
    {
        $feature = $plan->features->first(
            fn ($f) => $this->baseFeatureKey($plan, $f->slug) === $baseKey
        );

        return $feature?->value;
    }
};
?>

<div class="card" id="kt_pricing">
    <div class="card-body p-lg-17">
        <div class="d-flex flex-column">
            {{-- Heading --}}
            <div class="mb-13 text-center">
                <h2 class="fs-2hx fw-bold mb-5">{{ __('subscriptions.choose_plan_title') }}</h2>
                <div class="text-gray-600 fw-semibold fs-5">
                    {{ __('subscriptions.choose_plan_subtitle') }}
                </div>
            </div>

            {{-- Duration selector (view-only state, no server side-effects) --}}
            <div class="nav-group nav-group-outline mx-auto mb-15">
                @foreach (['quarterly' => __('subscriptions.duration_quarterly'), 'semiannual' => __('subscriptions.duration_semiannual'), 'yearly' => __('subscriptions.duration_yearly')] as $key => $label)
                    <button type="button"
                            wire:click="setDuration('{{ $key }}')"
                            class="btn btn-color-gray-600 btn-active btn-active-secondary px-6 py-3 {{ $duration === $key ? 'active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Plan cards --}}
            <div class="row g-10">
                @foreach ($this->visiblePlans() as $plan)
                    <div class="col-xl-4">
                        <div class="d-flex h-100 align-items-center">
                            <div class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10">
                                {{-- Heading --}}
                                <div class="mb-7 text-center">
                                    <h3 class="text-gray-900 mb-5 fw-bolder">{{ $plan->name }}</h3>
                                    <div class="text-gray-600 fw-semibold mb-5">
                                        {{ $plan->description }}
                                    </div>
                                    <div class="text-center">
                                        @if ($plan->isFree())
                                            <span class="fs-3x fw-bold text-success">{{ __('subscriptions.free_label') }}</span>
                                            <span class="fs-7 fw-semibold opacity-50">/ {{ __('subscriptions.forever_label') }}</span>
                                        @else
                                            <span class="fs-3x fw-bold text-primary">{{ number_format((float) $plan->price) }}</span>
                                            <span class="mb-2 text-primary">{{ __('subscriptions.currency_toman') }}</span>
                                            <span class="fs-7 fw-semibold opacity-50">/ {{ __('subscriptions.duration_'.$duration) }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Feature rows --}}
                                <div class="w-100 mb-10">
                                    @foreach ($this->featureRows() as $row)
                                        @php $value = $this->featureValue($plan, $row['key']); @endphp
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 flex-grow-1 pe-3 {{ $value !== null ? 'text-gray-800' : 'text-gray-600' }}">
                                                {{ $row['name'] }}
                                                @if ($value !== null && $value !== 'true')
                                                    <span class="text-muted fs-7">
                                                        ({{ $value === 'unlimited' ? __('subscriptions.unlimited_label') : $value }})
                                                    </span>
                                                @endif
                                            </span>
                                            @if ($value !== null)
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            @else
                                                <i class="ki-duotone ki-cross-circle fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                {{-- CTA: always navigates to the real subscription page, no in-place purchase --}}
                                <a href="{{ route('subscriptions') }}" class="btn btn-sm btn-primary">
                                    {{ __('subscriptions.select_plan_button') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
