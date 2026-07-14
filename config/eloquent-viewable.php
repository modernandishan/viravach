<?php

return [

    'models' => [

        'view' => [

            'table_name' => 'views',
            'connection' => env('DB_CONNECTION', 'mysql'),

        ],

    ],

    'cache' => [

        'key' => 'cyrildewit.eloquent-viewable.cache',
        'store' => env('CACHE_DRIVER', 'file'),

    ],

    'cooldown' => [

        'key' => 'cyrildewit.eloquent-viewable.cooldowns',

    ],

    'ignore_bots' => true,

    'honor_dnt' => false,

    'visitor_cookie_key' => 'eloquent_viewable',

    'ignored_ip_addresses' => [

        // '127.0.0.1',

    ],

];
