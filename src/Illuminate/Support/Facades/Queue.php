<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\QueueFake;

/**
 * @method static int size(string $queue = null)
 * @method static mixed push(string|object $job, mixed $data = '', $queue = null)
 * @method static mixed pushOn(string $queue, string|object $job, mixed $data = '')
 * @method static mixed pushRaw(string $payload, string $queue = null, array $options = [])
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, string|object $job, mixed $data = '', string $queue = null)
 * @method static mixed laterOn(string $queue, \DateTimeInterface|\DateInterval|int $delay, string|object $job, mixed $data = '')
 * @method static mixed bulk(array $jobs, mixed $data = '', string $queue = null)
 * @method static \Illuminate\Contracts\Queue\Job|null pop(string $queue = null)
 * @method static string getConnectionName()
 * @method static \Illuminate\Contracts\Queue\Queue setConnectionName(string $name)
 * @method static void assertNothingPushed()
 * @method static void assertNotPushed(string $job, \Closure $callback = null)
 * @method static void assertPushed(string $job, \Closure|int $callback = null)
 * @method static void assertPushedOn(string $job, int $times = 1)
 * @method static void assertPushedWithChain(string $queue, string $job, \Closure $callback = null)
 *
 * @see \Illuminate\Queue\QueueManager
 * @see \Illuminate\Queue\Queue
 */
class Queue extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Illuminate\Support\Testing\Fakes\QueueFake
     */
    public static function fake()
    {
        static::swap($fake = new QueueFake(static::getFacadeApplication()));

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
