<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Events\QueuesPaused;
use Illuminate\Queue\Events\QueuesResumed;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueuePauseCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:pause default');

        Event::assertDispatched(QueuePaused::class);
    }

    public function testPauseAllDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:pause --all');

        Event::assertDispatched(QueuesPaused::class);
    }

    public function testPauseAllCanExceptQueues()
    {
        Event::fake();

        $this->artisan('queue:pause --all --except=payments,notifications')
            ->expectsOutputToContain('Job processing on all queues except [payments, notifications] across all connections has been paused.')
            ->assertSuccessful();

        Event::assertDispatched(
            QueuesPaused::class,
            fn ($event) => $event->except === ['payments', 'notifications']
        );
        $this->assertSame(
            ['default', 'emails'],
            Queue::getPausedQueues('redis', ['default', 'payments', 'emails', 'notifications'])
        );
    }

    public function testExceptRequiresAllOption()
    {
        $this->artisan('queue:pause', [
            'queue' => 'default',
            '--except' => 'payments',
        ])->assertFailed();

        $this->assertFalse(Queue::isPaused('redis', 'default'));
    }

    public function testPauseExceptRequiresQueueName()
    {
        $this->artisan('queue:pause --all --except')->assertFailed();

        $this->assertFalse(Queue::isPaused('redis', 'default'));
    }

    public function testResumeAllDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:resume --all');

        Event::assertDispatched(QueuesResumed::class);
    }

    public function testResumeAllCanExceptQueues()
    {
        $this->artisan('queue:pause --all')->assertSuccessful();

        Event::fake();

        $this->artisan('queue:resume --all --except="payments, notifications,payments"')
            ->expectsOutputToContain('Job processing on all queues except [payments, notifications] across all connections has been resumed.')
            ->assertSuccessful();

        Event::assertDispatched(
            QueuesResumed::class,
            fn ($event) => $event->except === ['payments', 'notifications']
        );
        $this->assertSame(
            ['payments', 'notifications'],
            Queue::getPausedQueues('redis', ['default', 'payments', 'emails', 'notifications'])
        );
    }

    public function testResumeExceptRequiresAllOption()
    {
        $this->artisan('queue:pause default')->assertSuccessful();

        $this->artisan('queue:resume', [
            'queue' => 'default',
            '--except' => 'payments',
        ])->assertFailed();

        $this->assertTrue(Queue::isPaused(Queue::getDefaultDriver(), 'default'));
    }

    public function testResumeExceptRequiresQueueName()
    {
        $this->artisan('queue:pause --all')->assertSuccessful();

        $this->artisan('queue:resume --all --except=')->assertFailed();

        $this->assertTrue(Queue::isPaused('redis', 'default'));
    }

    public function testDisabledError()
    {
        Event::fake();

        Worker::$pausable = false;

        $this->artisan('queue:pause default');

        Event::assertNotDispatched(QueuePaused::class);

        Worker::$pausable = true;
    }
}
