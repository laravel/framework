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

    public function testPauseAllCanExcludeQueues()
    {
        $this->artisan('queue:pause', [
            '--all' => true,
            '--exclude' => ['payments', 'notifications'],
        ])->expectsOutputToContain('Job processing on all queues except [payments, notifications] across all connections has been paused.')
            ->assertSuccessful();

        $this->assertTrue(Queue::isPaused('redis', 'default'));
        $this->assertFalse(Queue::isPaused('redis', 'payments'));
        $this->assertFalse(Queue::isPaused('database', 'notifications'));
    }

    public function testExcludeRequiresAllOption()
    {
        $this->artisan('queue:pause', [
            'queue' => 'default',
            '--exclude' => ['payments'],
        ])->assertFailed();

        $this->assertFalse(Queue::isPaused('redis', 'default'));
    }

    public function testPauseExcludeRequiresQueueName()
    {
        $this->artisan('queue:pause --all --exclude')->assertFailed();

        $this->assertFalse(Queue::isPaused('redis', 'default'));
    }

    public function testResumeAllDispatchesEvent()
    {
        Event::fake();

        $this->artisan('queue:resume --all');

        Event::assertDispatched(QueuesResumed::class);
    }

    public function testResumeAllCanExcludeQueues()
    {
        $this->artisan('queue:pause --all')->assertSuccessful();

        $this->artisan('queue:resume', [
            '--all' => true,
            '--exclude' => ['payments', 'notifications'],
        ])->expectsOutputToContain('Job processing on all queues except [payments, notifications] across all connections has been resumed.')
            ->assertSuccessful();

        $this->assertFalse(Queue::isPaused('redis', 'default'));
        $this->assertTrue(Queue::isPaused('redis', 'payments'));
        $this->assertTrue(Queue::isPaused('database', 'notifications'));
    }

    public function testResumeExcludeRequiresAllOption()
    {
        $this->artisan('queue:pause default')->assertSuccessful();

        $this->artisan('queue:resume', [
            'queue' => 'default',
            '--exclude' => ['payments'],
        ])->assertFailed();

        $this->assertTrue(Queue::isPaused(Queue::getDefaultDriver(), 'default'));
    }

    public function testResumeExcludeRequiresQueueName()
    {
        $this->artisan('queue:pause --all')->assertSuccessful();

        $this->artisan('queue:resume --all --exclude=')->assertFailed();

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
