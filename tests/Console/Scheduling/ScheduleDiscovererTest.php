<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\ScheduleDiscoverer;
use Illuminate\Tests\Console\Scheduling\Fixtures\AttributedSchedule;
use PHPUnit\Framework\TestCase;

class ScheduleDiscovererTest extends TestCase
{
    public function test_it_discovers_scheduled_methods(): void
    {
        $tasks = (new ScheduleDiscoverer)->discover(
            path: __DIR__.'/Fixtures',
            namespace: 'Illuminate\\Tests\\Console\\Scheduling\\Fixtures',
        );

        $task = collect($tasks)->first(
            fn ($task) => $task->class === AttributedSchedule::class
                && $task->method === 'cleanup'
        );

        $this->assertNotNull($task);
        $this->assertSame('daily', $task->schedule->frequency);
        $this->assertSame('03:00', $task->schedule->at);
        $this->assertSame(
            30,
            $task->schedule->withoutOverlapping
        );
    }
}
