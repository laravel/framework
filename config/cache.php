<?php

use Illuminate\Support\Env;
use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => Env::get('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "storage", "octane",
    |                    "session", "failover", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'session' => [
            'driver' => 'session',
            'key' => Env::get('SESSION_CACHE_KEY', '_cache'),
        ],

        'database' => [
            'driver' => 'database',
            'connection' => Env::get('DB_CACHE_CONNECTION'),
            'table' => Env::get('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => Env::get('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => Env::get('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'storage' => [
            'driver' => 'storage',
            'disk' => Env::get('CACHE_STORAGE_DISK'),
            'path' => Env::get('CACHE_STORAGE_PATH', 'framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => Env::get('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                Env::get('MEMCACHED_USERNAME'),
                Env::get('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => Env::get('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => Env::get('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => Env::get('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => Env::get('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => Env::get('AWS_ACCESS_KEY_ID'),
            'secret' => Env::get('AWS_SECRET_ACCESS_KEY'),
            'region' => Env::get('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => Env::get('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => Env::get('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => Env::get('CACHE_PREFIX', Str::slug((string) Env::get('APP_NAME', 'laravel')).'-cache-'),

];
