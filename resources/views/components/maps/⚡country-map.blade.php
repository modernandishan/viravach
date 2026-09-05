<?php

use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * The country page's resolved model (NOT named $country: Livewire
     * auto-assigns route params to matching public properties).
     */
    public Country $countryModel;

    /**
     * Optional heading/lead overrides, same convention as ⚡world-globe.
     * Falls back to the globe.country_map_* lang keys.
     */
    public ?string $heading = null;

    public ?string $lead = null;

    /**
     * Suffix of the amCharts geodata global (am5geodata_ + value) for this
     * country, from config/geo_maps.php — or null when the country has no
     * wired-up map (the view then renders a hidden empty section).
     */
    public function geodataKey(): ?string
    {
        return config('geo_maps.countries.'.$this->countryModel->slug);
    }

    /**
     * Provinces of this country with at least one published company, cached
     * per locale with the same #[Computed(persist)] + rememberForever
     * convention as ⚡world-globe. v2: counts query now selects
     * COUNT(*) AS aggregate (v1 poisoned any cache populated before that
     * fix). Dataset shape: {geo_id, slug, name, url, count, count_text}.
     * Bump the suffix to invalidate stale payloads.
     *
     * @return array<int, array{geo_id: string, slug: string, name: string, url: string, count: int, count_text: string}>
     */
    #[Computed(persist: true)]
    public function states(): array
    {
        $locale = app()->getLocale();
        $slug = $this->countryModel->slug;

        return Cache::rememberForever("maps.country-map.{$slug}.v2.{$locale}", function () use ($locale): array {
            // Same join/pattern as ⚡world-globe's countries(): counts come
            // from active published companies located in active states of
            // this country.
            $counts = CompanyPublication::query()
                ->active()
                ->join('states', 'states.id', '=', 'company_publications.state_id')
                ->where('states.is_active', true)
                ->where('states.country_id', $this->countryModel->id)
                ->selectRaw('states.id, COUNT(*) AS aggregate')
                ->groupBy('states.id')
                ->pluck('aggregate', 'states.id');

            return State::query()
                ->active()
                ->where('country_id', $this->countryModel->id)
                ->whereIn('id', $counts->keys())
                ->whereNotNull('geo_id')
                ->get()
                ->sortBy(fn ($state) => $state->getTranslation('name', $locale), SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn ($state): array => [
                    'geo_id' => $state->geo_id,
                    'slug' => $state->slug,
                    'name' => $state->getTranslation('name', $locale),
                    'url' => route('companies.state', [
                        'country' => $this->countryModel->slug,
                        'state' => $state->slug,
                    ]),
                    'count' => (int) ($counts[$state->id] ?? 0),
                    // Ready-to-display tooltip line, built in PHP so the JS
                    // never has to interpolate lang placeholders (amCharts
                    // templates cannot run __() substitutions).
                    'count_text' => __('globe.companies_count', ['count' => number_format($counts[$state->id] ?? 0)], $locale),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Dataset injected into the map, keyed by geodata feature id (IR-xx for
     * iranLow) — the same keyed-dataset convention as ⚡world-globe's
     * countryDataset().
     *
     * @return array<string, array{name: string, count: int, count_text: string, url: string}>
     */
    #[Computed]
    public function stateDataset(): array
    {
        $dataset = [];

        foreach ($this->states as $state) {
            $dataset[$state['geo_id']] = [
                'name' => $state['name'],
                'count' => $state['count'],
                'count_text' => $state['count_text'],
                'url' => $state['url'],
            ];
        }

        return $dataset;
    }
};
?>
 {{-- Always render exactly one element root (Livewire's root-element
      detection chokes on zero roots when only the script block remains).
      Hidden when the country has no wired-up geodata or no provinces with
      published companies — no empty shell is visible. --}}
<section class="vv-globe-section" @if (! $this->geodataKey() || $this->states === []) hidden @endif>
    @if ($this->geodataKey() && $this->states !== [])
        <h2>{{ $heading ?? __('globe.country_map_title') }}</h2>
        <p class="vv-globe-sub">{{ $lead ?? __('globe.country_map_subtitle') }}</p>

        <div class="vv-globe-layout">
            {{-- Server-rendered province list FIRST in DOM order: it is the
                 SEO surface and the no-JS fallback. Real links, not hidden
                 from crawlers. --}}
            <div class="vv-globe-list-wrapper">
                <ul class="vv-globe-list">
                    @foreach ($this->states as $state)
                        <li>
                            <a
                                href="{{ $state['url'] }}"
                                class="vv-globe-link"
                                data-geo="{{ $state['geo_id'] }}"
                                wire:key="country-map-state-{{ $state['geo_id'] }}"
                            >
                                <span class="vv-globe-link-name">{{ $state['name'] }}</span>
                                <span class="vv-globe-link-count">{{ $state['count_text'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- The map. Supporting visual, second in DOM order. Canvas
                 content is drawn by amCharts after hydration, hence
                 wire:ignore; the container keeps an aspect ratio instead of
                 a pixel height so it scales with its column. --}}
            <div class="vv-globe-canvas-wrapper" dir="ltr">
                <div
                    id="country-map-{{ $this->getId() }}"
                    class="vv-globe-canvas"
                    wire:ignore
                ></div>
            </div>
        </div>
    @endif

    {{-- Plain inline script, deliberately NOT a Livewire script block (that
         renders a second root and trips root-element detection). Same
         lifecycle scaffolding as ⚡world-globe. NOTE: never mention
         at-directives in comments here — Blade compiles them even inside JS
         strings and comments, which would corrupt the script. --}}
    @if ($this->geodataKey() && $this->states !== [])
        <script>
        (function () {
            var element = document.getElementById('country-map-{{ $this->getId() }}');

            if (!element || element.dataset.vvCountryMapInitialized) {
                // Guard against double initialization when the component
                // re-enters the DOM through a Livewire morph.
                return;
            }

            element.dataset.vvCountryMapInitialized = '1';

            var GEO_KEY = @json($this->geodataKey());

            // Dataset built by PHP: { "IR-07": { name, count, url }, ... }
            // All display strings (names, counts) come from this dataset —
            // never from amCharts' built-in English geodata names.
            var dataset = @json($this->stateDataset);

            function geodata() {
                return window['am5geodata_' + GEO_KEY];
            }

            function globalsAreReady() {
                return window.am5 && window.am5map && !!geodata() && window.am5themes_Animated;
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
                var geoData = geodata();

                // DESIGN.md tokens: base = --vv-ink-100, accent =
                // --vv-accent-500, hover = --vv-primary-700.
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

                // Static single-country map: pan/zoom by translation, never
                // rotation (geoOrthographic is reserved for the world globe).
                var chart = root.container.children.push(am5map.MapChart.new(root, {
                    panX: 'translateX',
                    panY: 'translateY',
                    projection: am5map.geoMercator(),
                    paddingBottom: 8,
                    paddingTop: 8,
                    paddingLeft: 8,
                    paddingRight: 8
                }));

                // Base series: every province in the geodata, neutral, no
                // interactivity.
                var baseSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
                    geoJSON: geoData
                }));
                baseSeries.mapPolygons.template.setAll({
                    fill: COLOR_BASE_FILL,
                    stroke: COLOR_BASE_STROKE,
                    strokeWidth: 0.5,
                    interactive: false
                });

                // Active series: only the provinces present in the dataset
                // (>= 1 published company).
                var activeSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
                    geoJSON: geoData,
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
                    // stateDataset() — amCharts only substitutes fields, it never
                    // runs __() itself.
                    tooltipText: '[bold]{name}[/]\n{count_text}'
                });
                activeSeries.mapPolygons.template.states.create('hover', {
                    fill: COLOR_HOVER
                });

                activeSeries.data.setAll(Object.keys(dataset).map(function (geoId) {
                    return {
                        id: geoId,
                        name: dataset[geoId].name,
                        count_text: dataset[geoId].count_text,
                        url: dataset[geoId].url
                    };
                }));

                // Click is bound on the ACTIVE series' polygon template (the
                // base series template has interactive: false and no events).
                // Same unconditional preventDefault/stopPropagation as the
                // world globe: without it the click falls through to the
                // browser's default handling of the underlying pointer event.
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

                // --- Framing helpers ----------------------------------------------

                // Overall bounds of the geodata (it contains only this country's
                // provinces). zoomToGeoBounds takes {left,right,top,bottom} —
                // verified against the bundled map.js source.
                function countryBounds() {
                    var left = Infinity, right = -Infinity, top = -Infinity, bottom = Infinity;

                    function collect(geometry) {
                        if (!geometry) {
                            return;
                        }

                        if (geometry.type === 'Point') {
                            visit(geometry.coordinates);
                        } else if (geometry.type === 'Polygon') {
                            geometry.coordinates.forEach(function (ring) {
                                ring.forEach(visit);
                            });
                        } else if (geometry.type === 'MultiPolygon') {
                            geometry.coordinates.forEach(function (polygon) {
                                polygon.forEach(function (ring) {
                                    ring.forEach(visit);
                                });
                            });
                        }
                    }

                    function visit(point) {
                        left = Math.min(left, point[0]);
                        right = Math.max(right, point[0]);
                        top = Math.max(top, point[1]);
                        bottom = Math.min(bottom, point[1]);
                    }

                    geoData.features.forEach(function (feature) {
                        collect(feature.geometry);
                    });

                    return (left === Infinity)
                        ? null
                        : { left: left, right: right, top: top, bottom: bottom };
                }

                // Bounds of ONE province, by geodata feature id.
                function boundsOf(geoId) {
                    var feature = geoData.features.find(function (f) {
                        return f.id === geoId;
                    });

                    if (!feature) {
                        return null;
                    }

                    var left = Infinity, right = -Infinity, top = -Infinity, bottom = Infinity;

                    function visit(point) {
                        left = Math.min(left, point[0]);
                        right = Math.max(right, point[0]);
                        top = Math.max(top, point[1]);
                        bottom = Math.min(bottom, point[1]);
                    }

                    function collect(geometry) {
                        if (!geometry) {
                            return;
                        }

                        if (geometry.type === 'Point') {
                            visit(geometry.coordinates);
                        } else if (geometry.type === 'Polygon') {
                            geometry.coordinates.forEach(function (ring) {
                                ring.forEach(visit);
                            });
                        } else if (geometry.type === 'MultiPolygon') {
                            geometry.coordinates.forEach(function (polygon) {
                                polygon.forEach(function (ring) {
                                    ring.forEach(visit);
                                });
                            });
                        }
                    }

                    collect(feature.geometry);

                    return (left === Infinity)
                        ? null
                        : { left: left, right: right, top: top, bottom: bottom };
                }

                var reduceMotion = window.matchMedia
                    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                function zoomTo(bounds) {
                    if (!bounds) {
                        return;
                    }

                    chart.zoomToGeoBounds(bounds, reduceMotion ? 0 : 900);
                }

                function highlight(geoId) {
                    activeSeries.mapPolygons.each(function (polygon) {
                        var dataItem = polygon.dataItem;
                        var match = dataItem && dataItem.get('id') === geoId;
                        polygon.set('fill', match ? COLOR_HOVER : COLOR_ACCENT);
                    });
                }

                var framed = false;

                // Frame the whole country once its size is known.
                chart.on('boundschanged', function () {
                    root.events.once('frameended', function () {
                        if (!framed) {
                            framed = true;
                            zoomTo(countryBounds());
                        }
                    });
                });

                // --- List interaction ---------------------------------------------

                // Hovering a server-rendered list item zooms the map to that
                // province and highlights it, without navigating. Clicking the
                // link navigates for real (the anchor keeps working for
                // middle-click, no-JS and crawlers); the click handler only
                // also focuses the map when the map is alive.
                element.closest('.vv-globe-section').querySelectorAll('.vv-globe-link[data-geo]').forEach(function (link) {
                    var geoId = link.getAttribute('data-geo');

                    if (!dataset[geoId]) {
                        return;
                    }

                    function focus() {
                        zoomTo(boundsOf(geoId));
                        highlight(geoId);
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
