<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Exception;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\EventMutex;
use Mockery as m;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class CallbackEventTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testDefaultResultIsSuccess()
    {
        $event = new CallbackEvent(m::mock(EventMutex::class), function () {
        });

        $event->run($this->app);

        $this->assertSame(0, $event->exitCode);
    }

    public function testFalseResponseIsFailure()
    {
        $event = new CallbackEvent(m::mock(EventMutex::class), function () {
            return false;
        });

        $event->run($this->app);

        $this->assertSame(1, $event->exitCode);
    }

    public function testExceptionIsFailure()
    {
        $event = new CallbackEvent(m::mock(EventMutex::class), function () {
            throw new \Exception;
        });

        try {
            $event->run($this->app);
        } catch (Exception $e) {
        }

        $this->assertSame(1, $event->exitCode);
    }

    public function testExceptionBubbles()
    {
        $event = new CallbackEvent(m::mock(EventMutex::class), function () {
            throw new Exception;
        });

        $this->expectException(Exception::class);

        $event->run($this->app);
    }
}
