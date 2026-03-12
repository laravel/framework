<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Events\ScheduleResumed;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class ScheduleResumeCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->artisan('schedule:resume');

        Event::assertDispatched(ScheduleResumed::class);
    }
}
