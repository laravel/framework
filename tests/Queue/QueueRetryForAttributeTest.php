<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\Dispatcher;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Attributes\RetryFor;
use Illuminate\Queue\NullQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Testing\Fakes\QueueFake;
use PHPUnit\Framework\TestCase;

class QueueRetryForAttributeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-01-01 00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testJobExpirationIsCalculatedFromTheAttribute()
    {
        $this->assertSame(
            Carbon::now()->addSeconds(90)->getTimestamp(),
            (new NullQueue)->getJobExpiration(new RetryForAttributeJob)
        );
    }

    public function testAttributeAcceptsMinutesAndHours()
    {
        $this->assertSame(90, (new RetryFor(90))->retryFor);
        $this->assertSame(90, (new RetryFor(seconds: 90))->retryFor);
        $this->assertSame(600, (new RetryFor(minutes: 10))->retryFor);
        $this->assertSame(36000, (new RetryFor(hours: 10))->retryFor);
        $this->assertSame(5430, (new RetryFor(seconds: 30, minutes: 30, hours: 1))->retryFor);
    }

    public function testJobExpirationIsCalculatedFromNamedUnits()
    {
        $this->assertSame(
            Carbon::now()->addMinutes(10)->getTimestamp(),
            (new NullQueue)->getJobExpiration(new RetryForAttributeJobUsingMinutes)
        );

        $this->assertSame(
            Carbon::now()->addHours(4)->getTimestamp(),
            (new NullQueue)->getJobExpiration(new RetryForAttributeJobUsingHours)
        );
    }

    public function testJobExpirationIsInheritedFromAParentClassAttribute()
    {
        $this->assertSame(
            Carbon::now()->addSeconds(90)->getTimestamp(),
            (new NullQueue)->getJobExpiration(new RetryForAttributeChildJob)
        );
    }

    public function testRetryUntilMethodTakesPrecedenceOverTheAttribute()
    {
        $this->assertSame(
            Carbon::now()->addSeconds(5)->getTimestamp(),
            (new NullQueue)->getJobExpiration(new RetryForAttributeJobWithRetryUntilMethod)
        );
    }

    public function testRetryUntilPropertyTakesPrecedenceOverTheAttribute()
    {
        $job = new RetryForAttributeJob;
        $job->retryUntil = 1234567890;

        $this->assertSame(1234567890, (new NullQueue)->getJobExpiration($job));
    }

    public function testJobExpirationIsNullWithoutTheAttribute()
    {
        $this->assertNull((new NullQueue)->getJobExpiration(new RetryForAttributeJobWithoutAttribute));
    }

    public function testQueuedMailableUsesTheAttribute()
    {
        $job = new SendQueuedMailable(new RetryForAttributeMailable);

        $this->assertEquals(Carbon::now()->addSeconds(90), $job->retryUntil());
        $this->assertSame(
            Carbon::now()->addSeconds(90)->getTimestamp(),
            (new NullQueue)->getJobExpiration($job)
        );
    }

    public function testQueuedNotificationUsesTheAttribute()
    {
        $job = new SendQueuedNotifications('notifiables', new RetryForAttributeNotification);

        $this->assertEquals(Carbon::now()->addSeconds(90), $job->retryUntil());
        $this->assertSame(
            Carbon::now()->addSeconds(90)->getTimestamp(),
            (new NullQueue)->getJobExpiration($job)
        );
    }

    public function testQueuedListenerUsesTheAttribute()
    {
        $dispatcher = new Dispatcher;
        $queue = new QueueFake(new Container);

        $dispatcher->setQueueResolver(fn () => $queue);
        $dispatcher->listen('some.event', RetryForAttributeListener::class.'@handle');
        $dispatcher->dispatch('some.event', ['foo']);

        $queue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->retryUntil == Carbon::now()->addSeconds(90);
        });
    }
}

#[RetryFor(90)]
class RetryForAttributeJob
{
    public $retryUntil;
}

#[RetryFor(minutes: 10)]
class RetryForAttributeJobUsingMinutes
{
    //
}

#[RetryFor(hours: 4)]
class RetryForAttributeJobUsingHours
{
    //
}

class RetryForAttributeChildJob extends RetryForAttributeJob
{
    //
}

#[RetryFor(90)]
class RetryForAttributeJobWithRetryUntilMethod
{
    public function retryUntil()
    {
        return Carbon::now()->addSeconds(5);
    }
}

class RetryForAttributeJobWithoutAttribute
{
    //
}

#[RetryFor(90)]
class RetryForAttributeMailable extends Mailable
{
    //
}

#[RetryFor(90)]
class RetryForAttributeNotification extends Notification
{
    //
}

#[RetryFor(90)]
class RetryForAttributeListener implements ShouldQueue
{
    public function handle()
    {
        //
    }
}
