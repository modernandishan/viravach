<?php

use App\Livewire\Concerns\RecordsPageView;
use App\Models\Country;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    use RecordsPageView;

    // Deliberately NOT named $country: Livewire auto-assigns route params to
    // matching public properties and would apply implicit model binding on a
    // typed model prop (resolving via the default id key instead of the
    // slug). Resolution is a manual lookup by active slug, like the state
    // page.
    public Country $countryModel;

    public function mount(string $country): void
    {
        $this->countryModel = Country::query()
            ->where('slug', $country)
            ->where('is_active', true)
            ->firstOrFail();

        $this->recordPageView($this->countryModel);

        // Pushes the country's SeoMeta (title/description/canonical, OG,
        // Twitter, JSON-LD) into SEOTools. Falls back to a translated
        // name + published-company-count description when no SeoMeta row
        // exists (see Country::getSeoFallbackDescription()), and to
        // noindex,follow when the country has no published companies at all
        // (see Country::isThinPage()) — the page itself no longer 404s on
        // that: it's a stable taxonomy URL linked from navigation, the
        // footer, and the globe, and 404ing it would break internal
        // linking. Do not replace with direct SEOTools::set*() calls, see
        // HasSeo::applySeoTags()'s own comment on why those shortcuts leak
        // into OG/Twitter/JSON-LD even without a SeoMeta row.
        $this->countryModel->applySeoTags();
    }

    public function render()
    {
        return $this->view()->title($this->countryModel->name.' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">

        {{-- The toolbar (⚡after-menu) renders the breadcrumb for this route;
             it chains through companies.countries. --}}
        <div class="card mb-6 mb-xl-9">
            <div class="card-body">
                <h1 class="fs-2 fw-bold text-gray-900 mb-0">{{ $countryModel->name }}</h1>
            </div>
        </div>

        {{-- PLACEHOLDER (phase 3): country map. --}}
        <div class="card mb-6 mb-xl-9">
            <div class="card-body">
                <div class="text-center text-muted py-20"><!-- country map --></div>
            </div>
        </div>

        {{-- Company listing, scoped to this country. The component owns its
             own filters, sorting and pagination and knows nothing about this
             route. --}}
        <livewire:company-elements.company-list :country-id="$countryModel->id" />

    </div>
</div>
