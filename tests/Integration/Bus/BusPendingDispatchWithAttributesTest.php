<?php

namespace Illuminate\Tests\Integration\Bus;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Attributes\OnConnection;
use Illuminate\Foundation\Bus\Attributes\OnQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class BusPendingDispatchWithAttributesTest extends TestCase
{
    public function testDispatchWhereQueueAndConnectionAreFromAttributes(): void
    {
        Queue::fake();
        FakeJobWithOnQueueAndOnConnection::dispatch(123);

        Queue::assertPushed(function (FakeJobWithOnQueueAndOnConnection $job) {
            return $job->connection === "connection_from_attribute"
                && $job->queue === "queue-from-attribute"
                && $job->value === 123;
        });
    }

    public function testDispatchWhereQueueIsFromAttribute(): void
    {
        Queue::fake();

        FakeJobWithOnQueueFromAttribute::dispatch(1234)->onConnection("not-from-attribute");

        Queue::assertPushed(function (FakeJobWithOnQueueFromAttribute $job) {
            return $job->connection === "not-from-attribute"
                && $job->queue === "queue-from-attribute"
                && $job->value === 1234;
        });
    }

    public function testDispatchWhereConnectionIsFromAttribute(): void
    {
        Queue::fake();

        FakeJobWithOnConnection::dispatch(999);

        Queue::assertPushed(function (FakeJobWithOnConnection $job) {
            return $job->connection === "connection_from_attribute"
                && !isset($job->queue)
                && $job->value === 999;
        });
    }

    public function testOverridingQueueAndConnectionDoesNotUseAttributeValues(): void
    {
        Queue::fake();

        FakeJobWithOnQueueAndOnConnection::dispatch('abc')
            ->onQueue('setViaMethod')
            ->onConnection('setViaMethodToo');

        Queue::assertPushed(function (FakeJobWithOnQueueAndOnConnection $job) {
            return $job->queue === "setViaMethod"
                && $job->connection === "setViaMethodToo"
                && $job->value === 'abc';
        });
    }

    public function testAllowsEnumsInAttributes(): void
    {
        Queue::fake();

        FakeJobWithAttributesUsingEnums::dispatch(1234);

        Queue::assertPushed(
            fn (FakeJobWithAttributesUsingEnums $job) => $job->queue === 'my-value'
                && $job->connection == 'other-value'
                && $job->value === 1234
        );
    }

    public function testWorksWithDispatchFunction(): void
    {
        Queue::fake();

        dispatch(new FakeJobWithAttributesUsingEnums('laravel'))->onConnection('zzz');

        Queue::assertPushed(
            fn (FakeJobWithAttributesUsingEnums $job) => $job->queue === 'my-value'
                && $job->connection == 'zzz'
                && $job->value === 'laravel'
        );
    }
}

#[OnQueue('queue-from-attribute')]
#[OnConnection('connection_from_attribute')]
class FakeJobWithOnQueueAndOnConnection implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use InteractsWithQueue;

    public function __construct(public $value)
    {
    }
}

#[OnQueue('queue-from-attribute')]
class FakeJobWithOnQueueFromAttribute implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use InteractsWithQueue;

    public function __construct(public $value)
    {
    }
}

#[OnConnection('connection_from_attribute')]
class FakeJobWithOnConnection implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use InteractsWithQueue;

    public function __construct(public $value)
    {
    }
}

#[OnQueue(PendingDispatchWithAttributesEnum::MyValue)]
#[OnConnection(PendingDispatchWithAttributesEnum::OtherValue)]
class FakeJobWithAttributesUsingEnums implements ShouldQueue
{
    use Dispatchable;
    use Queueable;
    use InteractsWithQueue;

    public function __construct(public $value)
    {
    }
}

enum PendingDispatchWithAttributesEnum: string
{
    case MyValue = 'my-value';
    case OtherValue = 'other-value';
}
