<?php

use App\Livewire\Concerns\AggregatesCompanyViews;
use App\Models\CompanyPublication;
use App\Support\DashboardWidgetCache;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Widget 1 — daily views across the user's published company pages over the
 * last 30 days.
 *
 * Cost: two queries (publication ids, then one grouped count over `views`).
 * The `views` table is indexed on (viewable_type, viewable_id) by
 * $table->morphs('viewable'); `viewed_at` is not indexed, so the date filter
 * scans within the already-narrow per-user slice.
 *
 * Cached with a TTL rather than event invalidation: views are written by
 * RecordsPageView on every public page hit, so there is no chokepoint to
 * forget on. See DashboardWidgetCache::rememberViews().
 */
new class extends Component
{
    use AggregatesCompanyViews;

    private const WINDOW_DAYS = 30;

    /**
     * Zero-filled daily series, keyed Y-m-d. dailyViewCountsForPublications()
     * already fills the gaps, so the chart never has to invent points.
     *
     * @return array<int, array{date: string, value: int}>
     */
    #[Computed]
    public function series(): array
    {
        $userId = (int) auth()->id();

        return DashboardWidgetCache::rememberViews(
            DashboardWidgetCache::VIEWS_CHART,
            $userId,
            function () use ($userId): array {
                $publicationIds = $this->publishedPublicationIds($userId);

                if ($publicationIds->isEmpty()) {
                    return [];
                }

                return $this->dailyViewCountsForPublications(
                    $publicationIds,
                    now()->subDays(self::WINDOW_DAYS),
                )
                    ->map(fn (int $value, string $day): array => ['date' => $day, 'value' => $value])
                    ->values()
                    ->all();
            },
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function publishedPublicationIds(int $userId): Collection
    {
        return CompanyPublication::query()
            ->active()
            ->whereIn('company_id', fn ($query) => $query
                ->select('id')
                ->from('companies')
                ->where('user_id', $userId))
            ->pluck('id');
    }

    /**
     * True when the window has at least one recorded view. An all-zero
     * series is not worth a chart — it renders the empty state instead.
     */
    public function hasViews(): bool
    {
        return collect($this->series)->sum('value') > 0;
    }
};
?>

<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <div class="card-title">
            <h2 class="fs-4">{{ __('dashboard.views_chart_title') }}</h2>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('company-views') }}" class="btn btn-sm btn-light">
                {{ __('dashboard.view_all_companies') }}
            </a>
        </div>
    </div>
    <div class="card-body pt-0">
        @if ($this->hasViews())
            {{-- wire:ignore: amCharts owns this subtree. Livewire must not
                 morph a canvas the chart library is drawing into. --}}
            <div
                id="vv-dash-views-{{ $this->getId() }}"
                class="vv-dash-chart"
                wire:ignore
            ></div>
        @else
            <div class="vv-dash-widget-empty">
                <i class="ki-duotone ki-chart-line-down vv-dash-widget-empty-icon">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <p class="vv-dash-widget-empty-text">{{ __('dashboard.empty_no_views') }}</p>
            </div>
        @endif
    </div>
</div>

@if ($this->hasViews())
    {{-- One plain inline script, deliberately not a Livewire script block:
         that directive renders a second root element and is evaluated through
         Alpine. Mirrors ⚡world-globe's proven initialisation shape. NOTE:
         never mention at-directives in comments here — Blade compiles them
         even inside JS strings. --}}
    <script>
    (function () {
        var element = document.getElementById('vv-dash-views-{{ $this->getId() }}');

        if (!element || element.dataset.vvChartInitialized) {
            return;
        }

        element.dataset.vvChartInitialized = '1';

        var series = @json($this->series);

        function globalsAreReady() {
            return window.am5 && window.am5xy && window.am5themes_Animated;
        }

        // Fail silent: collapse the box rather than leave a dead empty frame.
        function collapse() {
            element.classList.add('vv-dash-chart-empty');
        }

        function whenGlobalsReady(callback) {
            if (globalsAreReady()) {
                am5.ready(callback);

                return;
            }

            if (document.readyState !== 'complete') {
                window.addEventListener('load', function () {
                    if (globalsAreReady()) {
                        am5.ready(callback);
                    } else {
                        collapse();
                    }
                }, { once: true });
            } else {
                collapse();
            }
        }

        whenGlobalsReady(function () {
            var elementId = element.id;
            var root = am5.Root.new(elementId);

            if (root._logo) {
                root._logo.dispose();
            }

            root.setThemes([am5themes_Animated.new(root)]);

            var chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: 'none',
                wheelY: 'none',
                paddingLeft: 0,
                paddingRight: 0
            }));

            var xAxis = chart.xAxes.push(am5xy.DateAxis.new(root, {
                baseInterval: { timeUnit: 'day', count: 1 },
                renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 48 })
            }));

            var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                min: 0,
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            var lineSeries = chart.series.push(am5xy.LineSeries.new(root, {
                name: {{ \Illuminate\Support\Js::from(__('dashboard.views_chart_series_label')) }},
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: 'value',
                valueXField: 'date',
                // DESIGN.md §2: --vv-primary-700 is the brand blue.
                stroke: am5.color(0x0F4C81),
                fill: am5.color(0x0F4C81),
                tooltip: am5.Tooltip.new(root, { labelText: '{valueY}' })
            }));

            lineSeries.fills.template.setAll({ fillOpacity: 0.12, visible: true });
            lineSeries.strokes.template.setAll({ strokeWidth: 2 });

            lineSeries.data.setAll(series.map(function (point) {
                return { date: new Date(point.date + 'T00:00:00').getTime(), value: point.value };
            }));

            lineSeries.appear(600);
            chart.appear(600, 100);

            // --- Teardown -------------------------------------------------
            var disposed = false;

            function disposeRoot() {
                if (!disposed) {
                    disposed = true;
                    root.dispose();
                }
            }

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
