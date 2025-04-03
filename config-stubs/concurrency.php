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
        'process' => [
            // Amount of time in seconds to wait for a process to finish
            'timeout' => env('CONCURRENCY_PROCESS_TIMEOUT', 60),
        ],
        'fork' => [
            // Maximum number of concurrent tasks to run
            'max_tasks' => env('CONCURRENCY_FORK_MAX_TASKS', null),
        ],
        'sync' => [
            // Sync driver has no configuration options
        ],
        'redis' => [
            // Redis connection to use from config/database.php
            'connection' => env('CONCURRENCY_REDIS_CONNECTION', 'default'),

            // Queue/key prefix for Redis keys
            'queue_prefix' => env('CONCURRENCY_REDIS_PREFIX', 'laravel:concurrency:'),

            // How long task locks should be held (in seconds)
            'lock_timeout' => env('CONCURRENCY_REDIS_LOCK_TIMEOUT', 60),

            // Redis processor specific settings
            'processor' => [
                // How often to check for scheduled tasks in seconds
                'scheduled_check_interval' => env('CONCURRENCY_REDIS_SCHEDULED_CHECK_INTERVAL', 1),

                // How long to run the processor before exiting (0 = forever)
                'timeout' => env('CONCURRENCY_REDIS_PROCESSOR_TIMEOUT', 0),

                // How long to sleep when no tasks are found
                'sleep' => env('CONCURRENCY_REDIS_PROCESSOR_SLEEP', 1),

                // Maximum number of reconnection attempts
                'max_attempts' => env('CONCURRENCY_REDIS_PROCESSOR_MAX_ATTEMPTS', 3),
            ],
        ],
    ],

];
