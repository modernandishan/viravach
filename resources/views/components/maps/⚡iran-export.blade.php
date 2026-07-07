<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="col-xl-9" dir="ltr">
    <!--begin::Tiles Widget 4-->
    <div class="card card-xl-stretch">
        <!--begin::Body-->
        <div class="card-body p-0" wire:ignore>
            <div id="iran-export-map-{{ $this->getId() }}" style="width: 100%; height: 500px;"></div>
        </div>
        <!--end::Body-->
    </div>
    <!--end::Tiles Widget 4-->
</div>

@script
<script>
    am5.ready(function () {
        var elementId = 'iran-export-map-{{ $this->getId() }}';

        var root = am5.Root.new(elementId);

        // Remove amCharts logo/watermark
        if (root._logo) {
            root._logo.dispose();
        }

        root.setThemes([
            am5themes_Animated.new(root)
        ]);

        var chart = root.container.children.push(am5map.MapChart.new(root, {
            panX: "rotateX",
            panY: "rotateY",
            projection: am5map.geoOrthographic(),
            rotationX: -51.4215,
            rotationY: -35.6944
        }));

        var polygonSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
            geoJSON: am5geodata_worldLow
        }));

        var graticuleSeries = chart.series.push(am5map.GraticuleSeries.new(root, {}));
        graticuleSeries.mapLines.template.setAll({
            stroke: root.interfaceColors.get("alternativeBackground"),
            strokeOpacity: 0.08
        });

        var lineSeries = chart.series.push(am5map.MapLineSeries.new(root, {}));
        lineSeries.mapLines.template.setAll({
            stroke: root.interfaceColors.get("alternativeBackground"),
            strokeOpacity: 0.6
        });

        var citySeries = chart.series.push(am5map.MapPointSeries.new(root, {}));

        citySeries.bullets.push(function () {
            var circle = am5.Circle.new(root, {
                radius: 5,
                tooltipText: "{title}",
                tooltipY: 0,
                fill: am5.color(0xffba00),
                stroke: root.interfaceColors.get("background"),
                strokeWidth: 2
            });

            return am5.Bullet.new(root, {
                sprite: circle
            });
        });

        var arrowSeries = chart.series.push(am5map.MapPointSeries.new(root, {}));

        arrowSeries.bullets.push(function () {
            var arrow = am5.Graphics.new(root, {
                fill: am5.color(0x000000),
                stroke: am5.color(0x000000),
                draw: function (display) {
                    display.moveTo(0, -3);
                    display.lineTo(8, 0);
                    display.lineTo(0, 3);
                    display.lineTo(0, -3);
                }
            });

            return am5.Bullet.new(root, {
                sprite: arrow
            });
        });

        var cities = [
            { id: "tehran", title: "Tehran", geometry: { type: "Point", coordinates: [51.4215, 35.6944] } },
            { id: "beijing", title: "Beijing", geometry: { type: "Point", coordinates: [116.4074, 39.9042] } },
            { id: "ankara", title: "Ankara", geometry: { type: "Point", coordinates: [32.8597, 39.9334] } },
            { id: "moscow", title: "Moscow", geometry: { type: "Point", coordinates: [37.6176, 55.7558] } },
            { id: "baghdad", title: "Baghdad", geometry: { type: "Point", coordinates: [44.3661, 33.3152] } },
            { id: "islamabad", title: "Islamabad", geometry: { type: "Point", coordinates: [73.0479, 33.6844] } },
            { id: "kabul", title: "Kabul", geometry: { type: "Point", coordinates: [69.2075, 34.5553] } }
        ];

        citySeries.data.setAll(cities);

        var destinations = ["beijing", "ankara", "moscow", "baghdad", "islamabad", "kabul"];

        // Tehran coordinates (origin)
        var originLongitude = 51.4215;
        var originLatitude = 35.6944;

        am5.array.each(destinations, function (did) {
            var destinationDataItem = citySeries.getDataItemById(did);
            var lineDataItem = lineSeries.pushDataItem({
                geometry: {
                    type: "LineString",
                    coordinates: [
                        [originLongitude, originLatitude],
                        [destinationDataItem.get("longitude"), destinationDataItem.get("latitude")]
                    ]
                }
            });

            arrowSeries.pushDataItem({
                lineDataItem: lineDataItem,
                positionOnLine: 0.5,
                autoRotate: true
            });
        });

        polygonSeries.events.on("datavalidated", function () {
            chart.set("rotationX", -originLongitude);
            chart.set("rotationY", -originLatitude);
            chart.set("zoomLevel", 1.1);
        });

        chart.appear(1000, 100);

        // Dispose the chart if this Livewire component gets removed from the DOM
        Livewire.hook('morph.removed', ({ el }) => {
            if (el.querySelector && el.querySelector('#' + elementId)) {
                root.dispose();
            }
        });
    });
</script>
@endscript
