<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Concurrency Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default concurrency driver that will be used for
    | tasks. You may change this value to any of the supported drivers.
    |
    | Supported drivers: "sync", "process", "fork", "redis"
    |
    */
    'default' => env('CONCURRENCY_DRIVER', 'process'),

    /*
    |--------------------------------------------------------------------------
    | Concurrency Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each driver.
    |
    */
    'driver' => [
        'redis' => [
            'connection' => env('CONCURRENCY_REDIS_CONNECTION', 'default'),
            'queue_prefix' => env('CONCURRENCY_REDIS_PREFIX', 'laravel:concurrency:'),
        ],
    ],

];
