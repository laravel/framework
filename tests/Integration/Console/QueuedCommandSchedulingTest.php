<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueuedCommandSchedulingTest extends TestCase
{
    public function testJobQueuingRespectsJobQueue()
    {
        Queue::fake();

        /** @var \Illuminate\Console\Scheduling\Schedule $scheduler */
        $scheduler = $this->app->make(Schedule::class);
        $scheduler->queuedCommand(FooCommand::class)->name('')->everyMinute();
        $scheduler->queuedCommand(FooCommand::class, ['foo' => 'bar'], 'test-queue')->name('')->everyMinute();
        $scheduler->queuedCommand(FooCommand::class, ['foo' => 'bar'], 'another-queue')->name('')->everyMinute();

        $events = $scheduler->events();
        foreach ($events as $event) {
            $event->run($this->app);
        }

        Queue::assertPushedOn('test-queue', QueuedCommand::class);
        Queue::assertPushedOn('another-queue', QueuedCommand::class);
        Queue::assertPushedOn(null, QueuedCommand::class);
    }

    public function testJobQueuingRespectsJobConnection()
    {
        Queue::fake();

        /** @var \Illuminate\Console\Scheduling\Schedule $scheduler */
        $scheduler = $this->app->make(Schedule::class);
        $scheduler->queuedCommand(FooCommand::class)->name('')->everyMinute();
        $scheduler->queuedCommand(FooCommand::class, ['foo' => 'bar'], null, 'foo')->name('')->everyMinute();
        $scheduler->queuedCommand(FooCommand::class, ['foo' => 'bar'], null, 'bar')->name('')->everyMinute();

        $events = $scheduler->events();
        foreach ($events as $event) {
            $event->run($this->app);
        }

        $this->assertSame(1, Queue::pushed(QueuedCommand::class, function (QueuedCommand $job, $pushedQueue) {
            return $job->connection === null;
        })->count());

        $this->assertSame(1, Queue::pushed(QueuedCommand::class, function (QueuedCommand $job, $pushedQueue) {
            return $job->connection === 'foo';
        })->count());

        $this->assertSame(1, Queue::pushed(QueuedCommand::class, function (QueuedCommand $job, $pushedQueue) {
            return $job->connection === 'bar';
        })->count());
    }
}

class FooCommand extends Command
{
    protected $signature = 'foo:run';

    public function handle()
    {
        //
    }
}
