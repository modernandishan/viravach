<?php

use App\Livewire\Concerns\ListsCompanies;
use App\Models\State;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    use ListsCompanies;

    public State $state;

    public function mount(string $slug): void
    {
        $this->state = State::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->recordPageView($this->state);

        SEOTools::setTitle($this->state->seoTitle());

        if ($description = $this->state->seoDescription()) {
            SEOTools::setDescription($description);
        }

        SEOTools::setCanonical(url()->current());
    }

    protected function filterCompanies(Builder $query): Builder
    {
        return $query->where('state_id', $this->state->id);
    }

    public function render()
    {
        return $this->view()->title($this->state->name.' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">

        <!--begin::State header card-->
        <div class="card mb-6 mb-xl-9">
            <div class="card-body">
                <h1 class="fs-2 fw-bold text-gray-900 mb-0">{{ $state->name }}</h1>
            </div>
        </div>
        <!--end::State header card-->

        <!--begin::Companies grid-->
        <div class="row g-4">
            @forelse ($this->companies as $company)
                <div class="col-12 col-sm-6 col-lg-4" wire:key="company-{{ $company->id }}">
                    <livewire:company-elements.company-card :company="$company" />
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center text-muted py-20">{{ __('companies.no_companies_found') }}</div>
                </div>
            @endforelse
        </div>
        <!--end::Companies grid-->

        <div class="d-flex flex-stack flex-wrap pt-10">
            {{ $this->companies->links('livewire::bootstrap') }}
        </div>

    </div>
</div>
