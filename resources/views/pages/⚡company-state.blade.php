<?php

use App\Livewire\Concerns\RecordsPageView;
use App\Models\State;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    // ListsCompanies is gone: the listing (with its filters, sorting and
    // crawlable pagination) now lives in ⚡company-list. RecordsPageView was
    // reached through that trait, so it is used directly here.
    use RecordsPageView;

    public State $stateModel;

    public function mount(string $country, string $state): void
    {
        // State slugs are globally unique, but the URL is country-scoped: a
        // state slug resolving under a mismatching (or inactive) country
        // segment must 404, never render under the wrong parent.
        $this->stateModel = State::query()
            ->where('slug', $state)
            ->where('is_active', true)
            ->whereHas('country', fn ($query) => $query
                ->where('slug', $country)
                ->where('is_active', true))
            ->firstOrFail();

        $this->recordPageView($this->stateModel);

        // Pushes the state's SeoMeta (title/description/canonical, OG,
        // Twitter, JSON-LD) into SEOTools, falling back to the translated
        // name/company-count description when no SeoMeta row exists (see
        // State::getSeoFallbackDescription()). Same call as
        // ⚡country.blade.php — do not replace with direct SEOTools::set*()
        // calls, see HasSeo::applySeoTags()'s own comment on why those
        // shortcuts leak into OG/Twitter/JSON-LD even without a SeoMeta row.
        $this->stateModel->applySeoTags();
    }

    public function render()
    {
        return $this->view()->title($this->stateModel->name.' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">

        <!--begin::State header card-->
        <div class="card mb-6 mb-xl-9">
            <div class="card-body">
                <h1 class="fs-2 fw-bold text-gray-900 mb-0">{{ $stateModel->name }}</h1>
            </div>
        </div>
        <!--end::State header card-->

        {{-- Company listing, scoped to this state. The state filter hides
             itself under a state scope — the scope already pins it. --}}
        <livewire:company-elements.company-list :state-id="$stateModel->id" />

    </div>
</div>
