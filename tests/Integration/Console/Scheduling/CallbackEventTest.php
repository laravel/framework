<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Exception;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Support\Stringable;
use Illuminate\Tests\Console\Fixtures\FakeEventMutex;
use Orchestra\Testbench\TestCase;

class CallbackEventTest extends TestCase
{
    private EventMutex $mutex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mutex = new FakeEventMutex;
    }

    public function testDefaultResultIsSuccess()
    {
        $success = null;

        $event = (new CallbackEvent($this->mutex, function () {
        }))->onSuccess(function () use (&$success) {
            $success = true;
        })->onFailure(function () use (&$success) {
            $success = false;
        });

        $event->run($this->app);

        $this->assertTrue($success);
    }

    public function testFalseResponseIsFailure()
    {
        $success = null;

        $event = (new CallbackEvent($this->mutex, function () {
            return false;
        }))->onSuccess(function () use (&$success) {
            $success = true;
        })->onFailure(function () use (&$success) {
            $success = false;
        });

        $event->run($this->app);

        $this->assertFalse($success);
    }

    public function testExceptionIsFailure()
    {
        $success = null;

        $event = (new CallbackEvent($this->mutex, function () {
            throw new Exception;
        }))->onSuccess(function () use (&$success) {
            $success = true;
        })->onFailure(function () use (&$success) {
            $success = false;
        });

        try {
            $event->run($this->app);
        } catch (Exception) {
        }

        $this->assertFalse($success);
    }

    public function testExceptionBubbles()
    {
        $event = new CallbackEvent($this->mutex, function () {
            throw new Exception;
        });

        $this->expectException(Exception::class);

        $event->run($this->app);
    }

    public function testOnSuccessCallbackCanReceiveEvent()
    {
        $callbackEvent = null;

        $event = (new CallbackEvent($this->mutex, function () {
        }))->onSuccess(function (CallbackEvent $event) use (&$callbackEvent) {
            $callbackEvent = $event;
        });

        $event->run($this->app);

        $this->assertSame($event, $callbackEvent);
    }

    public function testOnFailureCallbackCanReceiveEvent()
    {
        $callbackEvent = null;

        $event = (new CallbackEvent($this->mutex, function () {
            return false;
        }))->onFailure(function (CallbackEvent $event) use (&$callbackEvent) {
            $callbackEvent = $event;
        });

        $event->run($this->app);

        $this->assertSame($event, $callbackEvent);
    }

    public function testOutputCallbackCanReceiveEvent()
    {
        $callbackEvent = null;
        $outputValue = null;

        $event = (new CallbackEvent($this->mutex, function () {
        }))->onSuccess(function (Stringable $output, CallbackEvent $event) use (&$callbackEvent, &$outputValue) {
            $callbackEvent = $event;
            $outputValue = (string) $output;
        });

        $event->run($this->app);

        $this->assertSame($event, $callbackEvent);
        $this->assertSame('', $outputValue);
    }
}
