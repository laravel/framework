<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Intrusion Detection System (IDS) Settings
    |--------------------------------------------------------------------------
    |
    | These settings are used to configure Laravel's IDS system.
    |
    */
    'ids' => [
        // Threshold score for detecting a threat
        'threshold' => env('IDS_THRESHOLD', 5),

        // Active sensors
        'sensors' => [
            Illuminate\Security\Sensors\SqlInjectionSensor::class,
            Illuminate\Security\Sensors\XssSensor::class,
            Illuminate\Security\Sensors\RateLimitingSensor::class,
        ],
        
        // Paths to exclude from analysis
        'except' => [
            'horizon/*',
            'telescope/*',
            'debugbar/*',
            'nova/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced CSRF Protection Settings
    |--------------------------------------------------------------------------
    |
    | These settings are used to configure Laravel's advanced CSRF protection.
    |
    */
    'csrf' => [
        // Whether to enable Same-Site protection
        'same_site_protection' => true,

        // SameSite cookie attribute value
        'same_site' => 'lax', // none, lax, strict

        // Whether the CSRF cookie should be secure
        'secure' => env('SESSION_SECURE_COOKIE', false),

        // Paths to exclude from CSRF verification
        'except' => [
            'api/webhook/*',
            'payment/callback/*',
        ],

        // Enable Double Submit Cookie verification
        'double_submit_cookie' => true,

        // CSRF token expiration time in minutes
        'expiration' => 120,
    ],
]; 