<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DataForSEO (Google Trends)
    |--------------------------------------------------------------------------
    |
    | Google publishes no official public Trends API, so trending topics for
    | the WordPress content generator come from DataForSEO's Google Trends
    | Explore endpoint (HTTP Basic Auth with the account's API credentials).
    |
    | trends_locations maps a site locale to a Google geo-target criteria ID.
    | A null/absent entry is valid — DataForSEO then returns global results
    | for that language, which is the fallback for a market its Google Trends
    | location list does not cover. Verify the codes against
    | /v3/keywords_data/google_trends/locations before trusting them.
    |
    */

    'dataforseo' => [
        'login' => env('DATAFORSEO_LOGIN'),
        'password' => env('DATAFORSEO_PASSWORD'),
        'base_url' => env('DATAFORSEO_BASE_URL', 'https://api.dataforseo.com/v3'),
        'trends_locations' => [
            'fa' => env('DATAFORSEO_LOCATION_FA', 2364),
            'en' => env('DATAFORSEO_LOCATION_EN', 2840),
            'tr' => env('DATAFORSEO_LOCATION_TR', 2792),
            'ru' => env('DATAFORSEO_LOCATION_RU', 2643),
            'ar' => env('DATAFORSEO_LOCATION_AR', 2784),
        ],
    ],

    'libretranslate' => [
        'url' => env('LIBRETRANSLATE_URL', 'https://translate.hktp.ir'),
        'key' => env('LIBRETRANSLATE_API_KEY'),
        'basic_user' => env('LIBRETRANSLATE_BASIC_USER'),
        'basic_pass' => env('LIBRETRANSLATE_BASIC_PASS'),
    ],

];
