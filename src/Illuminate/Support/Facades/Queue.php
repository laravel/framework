<?php

namespace Illuminate\Support\Facades;

use Illuminate\Queue\Worker;
use Illuminate\Support\Testing\Fakes\QueueFake;

/**
 * @method static void addConnector(string $driver, \Closure $resolver)
 * @method static void after(mixed $callback)
 * @method static void before(mixed $callback)
 * @method static void bulk(array $jobs, mixed $data = '', string|null $queue = null)
 * @method static bool connected(string|null $name = null)
 * @method static \Illuminate\Contracts\Queue\Queue connection(string|null $name = null)
 * @method static void createPayloadUsing(callable|null $callback)
 * @method static void exceptionOccurred(mixed $callback)
 * @method static void extend(string $driver, \Closure $resolver)
 * @method static void failing(mixed $callback)
 * @method static \Illuminate\Contracts\Foundation\Application getApplication()
 * @method static string getConnectionName()
 * @method static \Illuminate\Container\Container getContainer()
 * @method static string getDefaultDriver()
 * @method static mixed getJobBackoff(mixed $job)
 * @method static mixed getJobExpiration(mixed $job)
 * @method static string getName(string|null $connection = null)
 * @method static mixed laterOn(string $queue, \DateTimeInterface|\DateInterval|int $delay, string $job, mixed $data = '')
 * @method static void looping(mixed $callback)
 * @method static mixed pushOn(string $queue, string $job, mixed $data = '')
 * @method static \Illuminate\Queue\QueueManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static \Illuminate\Queue\Queue setConnectionName(string $name)
 * @method static void setContainer(\Illuminate\Container\Container $container)
 * @method static void setDefaultDriver(string $name)
 * @method static void stopping(mixed $callback)
 *
 * @see \Illuminate\Queue\QueueManager
 * @see \Illuminate\Queue\Queue
 */
class Queue extends Facade
{
    /**
     * Register a callback to be executed to pick jobs.
     *
     * @param  string  $workerName
     * @param  callable  $callback
     * @return void
     */
    public static function popUsing($workerName, $callback)
    {
        return Worker::popUsing($workerName, $callback);
    }

    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $jobsToFake
     * @return \Illuminate\Support\Testing\Fakes\QueueFake
     */
    public static function fake($jobsToFake = [])
    {
        static::swap($fake = new QueueFake(static::getFacadeApplication(), $jobsToFake, static::getFacadeRoot()));

        return $fake;
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
