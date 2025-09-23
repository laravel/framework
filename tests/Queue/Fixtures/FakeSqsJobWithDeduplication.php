<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FakeSqsJobWithDeduplication implements ShouldQueue
{
    use Queueable;

    protected static $deduplicationIdFactory;

    public function handle(): void
    {
        //
    }

    /**
     * Deduplication ID method called by SqsQueue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @return string
     */
    public function deduplicationId($payload, $queue): string
    {
        return static::$deduplicationIdFactory
            ? (string) call_user_func(static::$deduplicationIdFactory, $payload, $queue)
            : hash('sha256', json_encode(func_get_args()));
    }

    /**
     * Set the callable that will be used to generate deduplication IDs.
     *
     * @param  callable|null  $factory
     * @return void
     */
    public static function createDeduplicationIdsUsing(?callable $factory = null)
    {
        static::$deduplicationIdFactory = $factory;
    }

    /**
     * Indicate that deduplication IDs should be created normally and not using a custom factory.
     *
     * @return void
     */
    public static function createDeduplicationIdsNormally()
    {
        static::$deduplicationIdFactory = null;
    }
}
