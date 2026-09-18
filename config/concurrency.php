<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Concurrency Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default concurrency driver that will be used
    | by Laravel's concurrency functions. By default, concurrent work will
    | be sent to isolated PHP processes which will return their results.
    |
    | Supported: "process", "fork", "sync", "queue"
    |
    */

    'default' => env('CONCURRENCY_DRIVER', 'process'),

    /*
    |--------------------------------------------------------------------------
    | Concurrency Drivers
    |--------------------------------------------------------------------------
    |
    | Below you may configure each of the concurrency drivers utilized by your
    | application. The queue driver will distribute your tasks to the queue
    | workers, which return their results through the given cache store.
    |
    */

    'drivers' => [

        'process' => [
            'driver' => 'process',
        ],

        'fork' => [
            'driver' => 'fork',
        ],

        'sync' => [
            'driver' => 'sync',
        ],

        'queue' => [
            'driver' => 'queue',
            'connection' => env('CONCURRENCY_QUEUE_CONNECTION'),
            'queue' => env('CONCURRENCY_QUEUE'),
            'store' => env('CONCURRENCY_CACHE_STORE'),
            'timeout' => (int) env('CONCURRENCY_TIMEOUT', 60),
        ],

    ],

];
