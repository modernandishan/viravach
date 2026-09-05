<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Country choropleth maps (maps/⚡country-map)
    |--------------------------------------------------------------------------
    | Maps a country's slug to the suffix of the amCharts geodata global
    | variable that draws it (am5geodata_ + value). The geodata script must
    | be included by layouts/landing.blade.php next to worldLow.js, and the
    | country's states must carry their geodata feature ids in
    | states.geo_id (see the add_geo_id_to_states_table migration and
    | StateSeeder).
    |
    | A new country map needs exactly: one line here, its geodata script in
    | the landing layout, and populated geo_id values. No component change —
    | ⚡country-map and ⚡country.blade.php are generic over this list.
    |
    | ids were read from the shipped geodata files, never assumed from
    | external numbering references.
    */

    'countries' => [
        'iran' => 'iranLow',
    ],

];
