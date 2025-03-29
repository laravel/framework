# Redis Concurrency Driver for Laravel

This document describes the Redis Concurrency Driver, a new feature that extends Laravel's Concurrency component to support distributed task execution using Redis.

## Overview

The Redis Concurrency Driver enables developers to execute concurrent tasks distributed across multiple servers or processes using Redis as a message broker. This is particularly useful in environments where tasks need to be distributed across different nodes in a cluster.

## Requirements

- Laravel 12.x
- PHP 8.2+
- Redis Server
- `predis/predis` or the PHP Redis extension

## Configuration

To use the Redis Concurrency Driver, you need to configure it in your `config/concurrency.php` file:

```php
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
```

## Basic Usage

You can use the Redis Concurrency Driver to run tasks concurrently:

```php
use Illuminate\Support\Facades\Concurrency;

// Using the default driver (set to 'redis' in config)
$results = Concurrency::run([
    fn () => performTask(1),
    fn () => performTask(2),
    fn () => performTask(3),
]);

// Or explicitly specifying the driver
$results = Concurrency::driver('redis')->run([
    fn () => performTask(1),
    fn () => performTask(2),
    fn () => performTask(3),
]);
```

## Redis Task Processor

For the Redis Concurrency Driver to work, you need to run the Redis Task Processor command on at least one server:

```bash
php artisan concurrency:redis-processor
```

This command will continuously poll the Redis queue for tasks and process them. You can run multiple instances of this command on different servers to scale out task processing.

### Command Options

- `--connection`: Redis connection to use (default: 'default')
- `--queue-prefix`: Queue prefix for Redis keys (default: 'laravel:concurrency:')
- `--timeout`: Number of seconds to run the processor (default: 60, 0 for no timeout)
- `--sleep`: Number of seconds to sleep when no jobs are found (default: 1)

Example:

```bash
php artisan concurrency:redis-processor --connection=concurrency --queue-prefix=myapp:concurrency: --timeout=3600
```

## Deferred Execution

The Redis Concurrency Driver also supports deferred execution of tasks:

```php
use Illuminate\Support\Facades\Concurrency;

Concurrency::driver('redis')->defer([
    fn () => sendEmail($user),
    fn () => generateReport(),
]);
```

Deferred tasks will be executed after the current request/process completes, without waiting for the results.

## Limitations

- Closures must be serializable. Use `SerializableClosure` from `laravel/serializable-closure` package.
- Tasks must not rely on the current application state as they might be executed in a different process.
- Long-running tasks should be avoided or broken down into smaller tasks.

## Error Handling

If a task throws an exception, it will be caught and propagated back to the caller of the `run` method.

## Performance Considerations

- Redis concurrency is optimized for distributed environments where tasks can be executed on multiple servers.
- For local concurrency on a single server, the 'process' or 'fork' drivers may offer better performance.
- For very high-throughput applications, consider using Laravel's Queue system instead. 