<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FakeSqsJobWithMessageGroup implements ShouldQueue
{
    use Queueable;

    protected static $messageGroupFactory;

    public function handle(): void
    {
        //
    }

    /**
     * Message group method called by SqsQueue.
     *
     * @return string
     */
    public function messageGroup(): string
    {
        return static::$messageGroupFactory
            ? (string) call_user_func(static::$messageGroupFactory)
            : 'group-1';
    }

    /**
     * Set the callable that will be used to generate message groups.
     *
     * @param  callable|null  $factory
     * @return void
     */
    public static function createMessageGroupsUsing(?callable $factory = null)
    {
        static::$messageGroupFactory = $factory;
    }

    /**
     * Indicate that message groups should be created normally and not using a custom factory.
     *
     * @return void
     */
    public static function createMessageGroupsNormally()
    {
        static::$messageGroupFactory = null;
    }
}
