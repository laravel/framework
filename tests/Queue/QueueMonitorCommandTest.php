<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\MonitorCommand;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueMonitorCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testEventIsDispatchedWhenTheOldestPendingJobWaitedTooLong()
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        $events = $this->runMonitorCommand(size: 3, oldestPending: Carbon::now()->subMinutes(10)->getTimestamp(), options: ['--max-wait' => 300]);

        $this->assertCount(1, $events);
        $this->assertSame(3, $events[0]->size);
        $this->assertSame(600, $events[0]->wait);
    }

    public function testNoEventIsDispatchedWhenTheOldestPendingJobIsWithinTheMaxWait()
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        $this->assertCount(0, $this->runMonitorCommand(size: 3, oldestPending: Carbon::now()->subMinute()->getTimestamp(), options: ['--max-wait' => 300]));
        $this->assertCount(0, $this->runMonitorCommand(size: 0, oldestPending: null, options: ['--max-wait' => 300]));
    }

    public function testWaitIsIgnoredWithoutTheMaxWaitOption()
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        $this->assertCount(0, $this->runMonitorCommand(size: 3, oldestPending: Carbon::now()->subHour()->getTimestamp()));
    }

    protected function runMonitorCommand(int $size, ?int $oldestPending, array $options = []): array
    {
        $queue = Mockery::mock(Queue::class);
        $queue->shouldReceive('size')->andReturn($size);
        $queue->shouldReceive('pendingSize')->andReturn($size);
        $queue->shouldReceive('delayedSize')->andReturn(0);
        $queue->shouldReceive('reservedSize')->andReturn(0);
        $queue->shouldReceive('creationTimeOfOldestPendingJob')->andReturn($oldestPending);

        $manager = Mockery::mock(Factory::class);
        $manager->shouldReceive('connection')->with('redis')->andReturn($queue);

        $dispatched = [];

        $events = new Dispatcher;
        $events->listen(QueueBusy::class, function ($event) use (&$dispatched) {
            $dispatched[] = $event;
        });

        $command = new MonitorCommand($manager, $events);
        $command->setLaravel(new Application);
        $command->run(new ArrayInput(['queues' => 'redis:default', ...$options]), new BufferedOutput);

        return $dispatched;
    }
}
