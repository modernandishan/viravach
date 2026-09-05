<?php

use App\Models\CompanyPublication;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * Optional heading/lead overrides so a placement can use its own section
     * copy (the homepage must not repeat the /countries page's wording).
     * Falls back to the globe.* lang keys.
     */
    public ?string $heading = null;

    public ?string $lead = null;

    /**
     * Countries with at least one published company, cached per locale with
     * the same #[Computed(persist)] + Cache::rememberForever convention as
     * the homepage/footer sections (home.filters.*, home.sections.*,
     * footer.top_*). v2: URLs now point at the /countries/{country}
     * hierarchy. v3: dataset gains a ready-to-display tooltip string.
     * Bump the suffix to invalidate stale payloads.
     *
     * The cache key is locale-only — deliberately NOT placement-dependent —
     * so the homepage and the countries index share one cached dataset.
     *
     * @return array<int, array{iso2: string, slug: string, name: string, url: string, count: int}>
     */
    #[Computed(persist: true)]
    public function countries(): array
    {
        $locale = app()->getLocale();

        return Cache::rememberForever("maps.world-globe.countries.v3.{$locale}", function () use ($locale): array {
            // Published companies are located via their state; the state
            // carries the country. Only active countries are listed.
            $counts = CompanyPublication::query()
                ->active()
                ->join('states', 'states.id', '=', 'company_publications.state_id')
                ->where('states.is_active', true)
                ->selectRaw('states.country_id, COUNT(*) AS aggregate')
                ->groupBy('states.country_id')
                ->pluck('aggregate', 'states.country_id');

            return Country::query()
                ->active()
                ->whereIn('id', $counts->keys())
                ->get()
                // Names are translated per locale; order the list by the
                // translated name so it reads naturally in every language.
                ->sortBy(fn (Country $country) => $country->getTranslation('name', $locale), SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn (Country $country): array => [
                    'iso2' => $country->iso2,
                    'slug' => $country->slug,
                    'name' => $country->getTranslation('name', $locale),
                    'url' => $this->countryUrl($country->slug),
                    'count' => (int) ($counts[$country->id] ?? 0),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Localized URL of a country's directory page (/countries/{country}).
     */
    protected function countryUrl(string $slug): string
    {
        return route('companies.country', ['country' => $slug]);
    }

    /**
     * Dataset injected into the globe, keyed by ISO alpha-2 (the id used by
     * amCharts' worldLow geodata). Longitude/latitude come from the Country
     * row and serve as the rotation target when a country is focused from
     * the list.
     *
     * @return array<string, array{name: string, count: int, url: string, lon: float, lat: float}>
     */
    #[Computed]
    public function countryDataset(): array
    {
        $iso2s = collect($this->countries)->pluck('iso2')->all();

        $geo = Country::query()
            ->whereIn('iso2', $iso2s)
            ->get(['iso2', 'latitude', 'longitude'])
            ->keyBy('iso2');

        $dataset = [];

        foreach ($this->countries as $country) {
            $record = $geo->get($country['iso2']);

            $dataset[$country['iso2']] = [
                'name' => $country['name'],
                'count' => $country['count'],
                // Ready-to-display tooltip line, built in PHP so the JS never
                // has to interpolate lang placeholders (amCharts templates
                // cannot run __() substitutions).
                'count_text' => __('globe.companies_count', ['count' => number_format($country['count'])]),
                'url' => $country['url'],
                'lon' => (float) ($record?->longitude ?? 0),
                'lat' => (float) ($record?->latitude ?? 0),
            ];
        }

        return $dataset;
    }
};
?>

{{-- Always render exactly one element root (Livewire's root-element
     detection chokes on zero roots when only the script block remains). When there
     is no country data the section is hidden — no empty shell is visible. --}}
<section class="vv-globe-section" @if ($this->countries === []) hidden @endif>
    @if ($this->countries !== [])
        <h2>{{ $heading ?? __('globe.title') }}</h2>
        <p class="vv-globe-sub">{{ $lead ?? __('globe.subtitle') }}</p>

        <div class="vv-globe-layout">
            {{-- Server-rendered country list FIRST in DOM order: it is the
                 SEO surface and the no-JS fallback. Real links, not hidden
                 from crawlers. --}}
            <div class="vv-globe-list-wrapper">
                <ul class="vv-globe-list">
                    @foreach ($this->countries as $country)
                        <li>
                            <a
                                href="{{ $country['url'] }}"
                                class="vv-globe-link"
                                data-iso="{{ $country['iso2'] }}"
                                wire:key="globe-country-{{ $country['iso2'] }}"
                            >
                                <span class="vv-globe-link-name">{{ $country['name'] }}</span>
                                <span class="vv-globe-link-count">{{ __('globe.companies_count', ['count' => number_format($country['count'])]) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- The globe. Supporting visual, second in DOM order. Canvas
                 content is drawn by amCharts after hydration, hence
                 wire:ignore; the container keeps an aspect ratio instead of
                 a pixel height so it scales with its column. --}}
            <div class="vv-globe-canvas-wrapper" dir="ltr">
                <div
                    id="world-globe-{{ $this->getId() }}"
                    class="vv-globe-canvas"
                    wire:ignore
                ></div>
            </div>
        </div>
    @endif

    {{-- Plain inline script, deliberately NOT a Livewire script block: that
         directive renders a second root <script wire:snapshot=...> element,
         which trips Livewire's debug root-element detection and is evaluated
         through Alpine. This script is self-contained and runs after the
         amCharts bundle (emitted by the layout before it). NOTE: never
         mention at-directives in comments here — Blade compiles them even
         inside JS strings and comments, which would corrupt the script. --}}
    @if ($this->countries !== [])
        <script>
        (function () {
            var element = document.getElementById('world-globe-{{ $this->getId() }}');

            if (!element || element.dataset.vvGlobeInitialized) {
                // Guard against double initialization when the component
                // re-enters the DOM through a Livewire morph.
                return;
            }

            element.dataset.vvGlobeInitialized = '1';

            // Dataset built by PHP: { "IR": { name, count, url, lon, lat }, ... }
            // All display strings (names, counts, tooltip label) come from this
            // dataset — never from amCharts' built-in English geodata names.
            var dataset = @json($this->countryDataset);

            function globalsAreReady() {
                return window.am5 && window.am5map && window.am5geodata_worldLow && window.am5themes_Animated;
            }

            // Fail silent: the server-rendered <a> list stays usable; the
            // empty canvas box collapses so no dead white square remains.
            function collapseCanvas() {
                var wrapper = element.closest('.vv-globe-canvas-wrapper');

                if (wrapper) {
                    wrapper.classList.add('vv-globe-canvas-wrapper-empty');
                }
            }

            // The amCharts bundle is emitted at page level by the landing
            // layout, before this script. Initialization must still survive
            // being placed anywhere — including components that enter the
            // DOM after load (Livewire morph / SPA navigation) — so it defers
            // until the globals exist instead of assuming they do.
            function whenGlobalsReady(callback) {
                if (globalsAreReady()) {
                    am5.ready(callback);

                    return;
                }

                // Scripts load with the page: if the page is still loading they
                // can still arrive. After window load there is no second chance
                // for them, so we collapse rather than poll or re-inject.
                if (document.readyState !== 'complete') {
                    window.addEventListener('load', function () {
                        if (globalsAreReady()) {
                            am5.ready(callback);
                        } else {
                            collapseCanvas();
                        }
                    }, { once: true });
                } else {
                    collapseCanvas();
                }
            }

            whenGlobalsReady(function () {
                var elementId = element.id;

                // DESIGN.md tokens: base = --vv-ink-100, accent =
                // --vv-accent-500, hover = --vv-primary-700. NOTE: this am5
                // build has no MapChart.backgroundSeries API (verified against
                // the bundled source), so the sphere reads through the
                // graticule only.
                var COLOR_BASE_FILL = am5.color(0xE9EEF3);
                var COLOR_BASE_STROKE = am5.color(0xFFFFFF);
                var COLOR_ACCENT = am5.color(0xD97440);
                var COLOR_HOVER = am5.color(0x0F4C81);

                var root = am5.Root.new(elementId);

                if (root._logo) {
                    root._logo.dispose();
                }

                root.setThemes([
                    am5themes_Animated.new(root)
                ]);

                var chart = root.container.children.push(am5map.MapChart.new(root, {
                    panX: 'rotateX',
                    panY: 'rotateY',
                    projection: am5map.geoOrthographic(),
                    // Initial view centered on the Middle East (Tehran:
                    // rotationX = -longitude, rotationY = -latitude, the same
                    // convention as the rotateTo() helper below and as
                    // maps/⚡iran-export). Auto-rotation then continues from
                    // here.
                    rotationX: -51.4215,
                    rotationY: -35.6944,
                    paddingBottom: 8,
                    paddingTop: 8,
                    paddingLeft: 8,
                    paddingRight: 8
                }));

                var graticuleSeries = chart.series.push(am5map.GraticuleSeries.new(root, {}));
                graticuleSeries.mapLines.template.setAll({
                    stroke: COLOR_BASE_FILL,
                    strokeOpacity: 0.5
                });

                // Base series: every country, neutral, no interactivity.
                var baseSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
                    geoJSON: am5geodata_worldLow
                }));
                baseSeries.mapPolygons.template.setAll({
                    fill: COLOR_BASE_FILL,
                    stroke: COLOR_BASE_STROKE,
                    strokeWidth: 0.5,
                    interactive: false
                });

                // Active series: only the countries present in the dataset.
                var activeSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
                    geoJSON: am5geodata_worldLow,
                    include: Object.keys(dataset)
                }));
                activeSeries.mapPolygons.template.setAll({
                    fill: COLOR_ACCENT,
                    stroke: COLOR_BASE_STROKE,
                    strokeWidth: 0.5,
                    cursorOverStyle: 'pointer',
                    interactive: true,
                    // {name} is a plain localized string (no further translation
                    // happens here) and {count_text} is the fully-formed, already
                    // translated "N companies" string built in PHP by
                    // countryDataset() — amCharts only substitutes fields, it never
                    // runs __() itself.
                    tooltipText: '[bold]{name}[/]\n{count_text}'
                });
                activeSeries.mapPolygons.template.states.create('hover', {
                    fill: COLOR_HOVER
                });

                activeSeries.data.setAll(Object.keys(dataset).map(function (iso) {
                    return {
                        id: iso,
                        name: dataset[iso].name,
                        count_text: dataset[iso].count_text,
                        url: dataset[iso].url
                    };
                }));

                // Click is bound on the ACTIVE series' polygon template (the
                // base series template has interactive: false and no events).
                // The URL comes from the data item injected above, so only
                // countries present in the dataset are clickable.
                //
                // preventDefault/stopPropagation run unconditionally, before
                // the url check: without a real navigation, the click was
                // falling through to the browser's default handling of the
                // underlying pointer event, which triggered a "download SVG"
                // action instead of doing nothing. Guarding the navigation
                // itself on `url` (rather than skipping the whole handler)
                // keeps that default suppressed even in the edge case where
                // a data item somehow has no url.
                activeSeries.mapPolygons.template.events.on('click', function (ev) {
                    if (ev.originalEvent) {
                        ev.originalEvent.preventDefault();
                        ev.originalEvent.stopPropagation();
                    }

                    var url = ev.target.dataItem && ev.target.dataItem.get('url');

                    if (!url) {
                        return;
                    }

                    window.location.href = url;
                });

                // --- Rotation helpers ---------------------------------------------

                // Average of the polygon's geo coordinates (the polygon centroid).
                // amCharts' worldLow ids are ISO alpha-2, matching the dataset keys.
                function centroidOf(iso) {
                    var feature = am5geodata_worldLow.features.find(function (f) {
                        return f.id === iso;
                    });

                    if (!feature) {
                        return null;
                    }

                    var sumLon = 0;
                    var sumLat = 0;
                    var total = 0;

                    function collect(geometry) {
                        if (!geometry) {
                            return;
                        }

                        if (geometry.type === 'Point') {
                            sumLon += geometry.coordinates[0];
                            sumLat += geometry.coordinates[1];
                            total++;
                        } else if (geometry.type === 'Polygon') {
                            geometry.coordinates.forEach(function (ring) {
                                ring.forEach(function (point) {
                                    sumLon += point[0];
                                    sumLat += point[1];
                                    total++;
                                });
                            });
                        } else if (geometry.type === 'MultiPolygon') {
                            geometry.coordinates.forEach(function (polygon) {
                                collect({ type: 'Polygon', coordinates: polygon });
                            });
                        }
                    }

                    collect(feature.geometry);

                    return total > 0
                        ? { lon: sumLon / total, lat: sumLat / total }
                        : null;
                }

                function rotateTo(iso) {
                    var target = centroidOf(iso) || {
                        lon: dataset[iso].lon,
                        lat: dataset[iso].lat
                    };

                    chart.animate({
                        key: 'rotationX',
                        to: -target.lon,
                        duration: 900,
                        easing: am5.ease.out(am5.ease.cubic)
                    });
                    chart.animate({
                        key: 'rotationY',
                        to: Math.max(-90, Math.min(90, -target.lat)),
                        duration: 900,
                        easing: am5.ease.out(am5.ease.cubic)
                    });
                }

                function highlight(iso) {
                    activeSeries.mapPolygons.each(function (polygon) {
                        var dataItem = polygon.dataItem;
                        var match = dataItem && dataItem.get('id') === iso;
                        polygon.set('fill', match ? COLOR_HOVER : COLOR_ACCENT);
                    });
                }

                // --- Auto-rotation ------------------------------------------------

                // Slow idle rotation; stops permanently on any pointer interaction
                // with the globe (drag, wheel, touch).
                var autoRotating = true;
                var ROTATION_SPEED = 0.08;

                chart.on('boundschanged', function () {
                    root.events.once('frameended', function () {
                        if (autoRotating) {
                            chart.set('rotationX', chart.get('rotationX') + ROTATION_SPEED);
                        }
                    });
                });

                ['pointerdown', 'wheel', 'touchstart'].forEach(function (event) {
                    root.dom.addEventListener(event, function () {
                        autoRotating = false;
                    }, { passive: true });
                });

                // --- List interaction ---------------------------------------------

                // Hovering/clicking a server-rendered list item rotates the globe
                // to that country and highlights it, without navigating.
                element.closest('.vv-globe-section').querySelectorAll('.vv-globe-link[data-iso]').forEach(function (link) {
                    var iso = link.getAttribute('data-iso');

                    if (!dataset[iso]) {
                        return;
                    }

                    function focus() {
                        autoRotating = false;
                        rotateTo(iso);
                        highlight(iso);
                    }

                    link.addEventListener('mouseenter', focus);
                    link.addEventListener('click', focus);
                });

                chart.appear(1000, 100);

                // --- Teardown -----------------------------------------------------

                var disposed = false;

                function disposeRoot() {
                    if (!disposed) {
                        disposed = true;
                        root.dispose();
                    }
                }

                // Dispose the chart root when this component leaves the DOM
                // (Livewire morph) or on SPA-style navigation, to prevent leaks.
                // The inline script runs before Livewire's own script tag, so hook
                // registration waits for Livewire to be ready.
                document.addEventListener('alpine:navigating', disposeRoot, { once: true });

                document.addEventListener('livewire:init', function () {
                    Livewire.hook('morph.removed', function ({ el }) {
                        if (el.querySelector && el.querySelector('#' + elementId)) {
                            disposeRoot();
                        }
                    });
                }, { once: true });
            });
        })();
        </script>
    @endif
</section>
