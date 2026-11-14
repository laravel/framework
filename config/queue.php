<?php

use Illuminate\Support\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => Env::get('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis",
    |          "deferred", "background", "failover", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => Env::get('DB_QUEUE_CONNECTION'),
            'table' => Env::get('DB_QUEUE_TABLE', 'jobs'),
            'queue' => Env::get('DB_QUEUE', 'default'),
            'retry_after' => (int) Env::get('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => Env::get('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => Env::get('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) Env::get('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => Env::get('AWS_ACCESS_KEY_ID'),
            'secret' => Env::get('AWS_SECRET_ACCESS_KEY'),
            'prefix' => Env::get('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => Env::get('SQS_QUEUE', 'default'),
            'suffix' => Env::get('SQS_SUFFIX'),
            'region' => Env::get('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
            'overflow' => [
                'enabled' => Env::get('SQS_OVERFLOW_ENABLED', false),
                'store' => Env::get('SQS_OVERFLOW_STORE'),
                'always' => false,
                'delete_after_processing' => true,
                'flush_on_clear' => Env::get('SQS_OVERFLOW_FLUSH_ON_CLEAR', false),
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => Env::get('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => Env::get('REDIS_QUEUE', 'default'),
            'retry_after' => (int) Env::get('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        'deferred' => [
            'driver' => 'deferred',
        ],

        'background' => [
            'driver' => 'background',
        ],

        'failover' => [
            'driver' => 'failover',
            'connections' => [
                'database',
                'deferred',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => Env::get('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => Env::get('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => Env::get('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
