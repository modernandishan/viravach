<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IPPanel API Credentials
    |--------------------------------------------------------------------------
    |
    | Here you can specify your IPPanel API key and optionally override the base URL.
    |
    */

    'api_key' => env('IPPANEL_API_KEY', ''),

    'base_url' => env('IPPANEL_BASE_URL', 'https://edge.ippanel.com/v1/api'),

    /*
    |--------------------------------------------------------------------------
    | OTP Defaults
    |--------------------------------------------------------------------------
    |
    | Default values used by App\Services\Otp\OtpService when sending a
    | one-time password. Every value can be overridden per call, so other
    | parts of the app can send OTPs with a different pattern, sender
    | number, or digit count without touching this file.
    |
    */

    'otp' => [
        'origin_number' => env('IPPANEL_ORIGIN_NUMBER', ''),
        'pattern' => env('IPPANEL_PATTERN', ''),
        'digits' => (int) env('IPPANEL_OTP_DIGITS', 6),

        // Name of the variable defined on the IPPanel pattern that the
        // generated code is sent as (the pattern's "params" key).
        'pattern_param' => env('IPPANEL_OTP_PATTERN_PARAM', 'code'),

        // How long (in seconds) a generated code stays valid.
        'ttl' => (int) env('IPPANEL_OTP_TTL', 120),

        // Minimum time (in seconds) a phone number must wait before it can
        // request another code.
        'resend_after' => (int) env('IPPANEL_OTP_RESEND_AFTER', 90),

        // Maximum number of wrong-code attempts allowed per sent code.
        'max_attempts' => (int) env('IPPANEL_OTP_MAX_ATTEMPTS', 5),
    ],
];
