<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Events\SchedulePaused;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class SchedulePauseCommandTest extends TestCase
{
    public function testDispatchesEvent()
    {
        Event::fake();

        $this->artisan('schedule:pause');

        Event::assertDispatched(SchedulePaused::class);
    }
}
