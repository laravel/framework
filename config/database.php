<?php

use Illuminate\Support\Env;
use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => Env::get('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => Env::get('DB_URL'),
            'database' => Env::get('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'prefix_indexes' => null,
            'mask_bindings_in_exception_messages' => Env::get('DB_MASK_BINDINGS', false),
            'foreign_key_constraints' => Env::get('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
            'pragmas' => [],
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => Env::get('DB_URL'),
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', '3306'),
            'database' => Env::get('DB_DATABASE', 'laravel'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'unix_socket' => Env::get('DB_SOCKET', ''),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'mask_bindings_in_exception_messages' => Env::get('DB_MASK_BINDINGS', false),
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => Env::get('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => Env::get('DB_URL'),
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', '3306'),
            'database' => Env::get('DB_DATABASE', 'laravel'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'unix_socket' => Env::get('DB_SOCKET', ''),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'mask_bindings_in_exception_messages' => Env::get('DB_MASK_BINDINGS', false),
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => Env::get('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => Env::get('DB_URL'),
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', '5432'),
            'database' => Env::get('DB_DATABASE', 'laravel'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => Env::get('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'mask_bindings_in_exception_messages' => Env::get('DB_MASK_BINDINGS', false),
            'search_path' => 'public',
            'sslmode' => 'prefer',
            'pooled' => Env::get('DB_POOLED', false),
            'direct' => array_filter([
                'host' => Env::get('DB_DIRECT_HOST'),
                'port' => Env::get('DB_DIRECT_PORT'),
                'username' => Env::get('DB_DIRECT_USERNAME'),
                'password' => Env::get('DB_DIRECT_PASSWORD'),
                'sslmode' => Env::get('DB_DIRECT_SSLMODE'),
            ]),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => Env::get('DB_URL'),
            'host' => Env::get('DB_HOST', 'localhost'),
            'port' => Env::get('DB_PORT', '1433'),
            'database' => Env::get('DB_DATABASE', 'laravel'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => Env::get('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'mask_bindings_in_exception_messages' => Env::get('DB_MASK_BINDINGS', false),
            // 'encrypt' => Env::get('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => Env::get('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => Env::get('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => Env::get('REDIS_CLUSTER', 'redis'),
            'prefix' => Env::get('REDIS_PREFIX', Str::slug((string) Env::get('APP_NAME', 'laravel')).'-database-'),
            'persistent' => Env::get('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => Env::get('REDIS_URL'),
            'host' => Env::get('REDIS_HOST', '127.0.0.1'),
            'username' => Env::get('REDIS_USERNAME'),
            'password' => Env::get('REDIS_PASSWORD'),
            'port' => Env::get('REDIS_PORT', '6379'),
            'database' => Env::get('REDIS_DB', '0'),
            'max_retries' => Env::get('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => Env::get('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => Env::get('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => Env::get('REDIS_BACKOFF_CAP', 1000),
            'command_retries' => Env::get('REDIS_COMMAND_RETRIES', 0),
        ],

        'cache' => [
            'url' => Env::get('REDIS_URL'),
            'host' => Env::get('REDIS_HOST', '127.0.0.1'),
            'username' => Env::get('REDIS_USERNAME'),
            'password' => Env::get('REDIS_PASSWORD'),
            'port' => Env::get('REDIS_PORT', '6379'),
            'database' => Env::get('REDIS_CACHE_DB', '1'),
            'max_retries' => Env::get('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => Env::get('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => Env::get('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => Env::get('REDIS_BACKOFF_CAP', 1000),
            'command_retries' => Env::get('REDIS_COMMAND_RETRIES', 0),
        ],

    ],

];
