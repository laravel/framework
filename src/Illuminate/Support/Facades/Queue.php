<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\QueueFake;

/**
 * @method static void before(mixed $callback) Register an event listener for the before job event.
 * @method static void after(mixed $callback) Register an event listener for the after job event.
 * @method static void exceptionOccurred(mixed $callback) Register an event listener for the exception occurred job event.
 * @method static void looping(mixed $callback) Register an event listener for the daemon queue loop.
 * @method static void failing(mixed $callback) Register an event listener for the failed job event.
 * @method static void stopping(mixed $callback) Register an event listener for the daemon queue stopping.
 * @method static bool connected(string $name) Determine if the driver is connected.
 * @method static \Illuminate\Contracts\Queue\Queue connection(string $name) Resolve a queue connection instance.
 * @method static void extend(string $driver, \Closure $resolver) Add a queue connection resolver.
 * @method static void addConnector(string $driver, \Closure $resolver) Add a queue connection resolver.
 * @method static string getDefaultDriver() Get the name of the default queue connection.
 * @method static void setDefaultDriver(string $name) Set the name of the default queue connection.
 * @method static string getName(string $connection) Get the full name for the given connection.
 * @method static bool isDownForMaintenance() Determine if the application is in maintenance mode.
 * @method static int size(string $queue) Get the size of the queue.
 * @method static mixed push(string $job, mixed $data, string $queue) Push a new job onto the queue.
 * @method static mixed pushRaw(string $payload, string $queue, array $options) Push a raw payload onto the queue.
 * @method static mixed later(\DateTimeInterface | \DateInterval | int $delay, string $job, mixed $data, string $queue) Push a new job onto the queue after a delay.
 * @method static \Illuminate\Contracts\Queue\Job|null pop(string $queue) Pop the next job off of the queue.
 * @method static mixed pushOn(string $queue, string $job, mixed $data) Push a new job onto the queue.
 * @method static mixed laterOn(string $queue, \DateTimeInterface | \DateInterval | int $delay, string $job, mixed $data) Push a new job onto the queue after a delay.
 * @method static mixed bulk(array $jobs, mixed $data, string $queue) Push an array of jobs onto the queue.
 * @method static mixed getJobExpiration(mixed $job) Get the expiration timestamp for an object-based queue handler.
 * @method static string getConnectionName() Get the connection name for the queue.
 * @method static $this setConnectionName(string $name) Set the connection name for the queue.
 * @method static void setContainer(\Illuminate\Container\Container $container) Set the IoC container instance.
 *
 * @see \Illuminate\Queue\QueueManager
 * @see \Illuminate\Queue\Queue
 */
class Queue extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return void
     */
    public static function fake()
    {
        static::swap(new QueueFake(static::getFacadeApplication()));
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'queue';
    }
}
