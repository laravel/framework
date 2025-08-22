<?php

namespace Illuminate\Tests\Integration\Log;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class LoggingIntegrationTest extends TestCase
{
    public function testLoggingCanBeRunWithoutEncounteringExceptions()
    {
        $this->expectNotToPerformAssertions();

        Log::info('Hello World');
    }

    public function testCallingLoggerDirectlyDispatchesOneEvent()
    {
        Event::fake([MessageLogged::class]);

        $this->app->make(Logger::class)->debug('my debug message');

        Event::assertDispatchedTimes(MessageLogged::class, 1);
    }
}
