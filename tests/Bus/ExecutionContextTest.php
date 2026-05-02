<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Events\StepCompleted;
use Illuminate\Bus\Events\StepStarting;
use Illuminate\Bus\ExecutionContext\ExecutionContext;
use Illuminate\Bus\ExecutionContext\ExecutionState;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ExecutionContextTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testStepReturnsResultAndDispatchesLifecycleEvents()
    {
        Carbon::setTestNow($now = Carbon::parse('2026-04-01 21:53:00'));

        $events = new ExecutionContextRecordingEventDispatcher;
        $context = new ExecutionContext(new Container, $events);

        $result = $context->step('fetch-products', static fn () => ['product-1', 'product-2']);

        $this->assertSame(['product-1', 'product-2'], $result);
        $this->assertCount(2, $events->events);
        $this->assertInstanceOf(StepStarting::class, $events->events[0]);
        $this->assertSame($context, $events->events[0]->executionContext);
        $this->assertSame('fetch-products', $events->events[0]->step);
        $this->assertInstanceOf(StepCompleted::class, $events->events[1]);
        $this->assertSame($context, $events->events[1]->executionContext);
        $this->assertSame('fetch-products', $events->events[1]->step);
        $this->assertSame(['product-1', 'product-2'], $events->events[1]->result);
        $this->assertSame($now->getTimestamp(), $events->events[1]->completedAt);
    }

    public function testStepReturnsStoredResultWhenAlreadyCompleted()
    {
        $events = new ExecutionContextRecordingEventDispatcher;
        $context = new ExecutionContext(new Container, $events);
        $runs = 0;

        $firstResult = $context->step('fetch-products', static function () use (&$runs) {
            $runs++;

            return 'first-result';
        });

        $secondResult = $context->step('fetch-products', static function () use (&$runs) {
            $runs++;

            return 'second-result';
        });

        $this->assertSame('first-result', $firstResult);
        $this->assertSame('first-result', $secondResult);
        $this->assertSame(1, $runs);
        $this->assertCount(2, $events->events);
    }
}

class ExecutionContextRecordingEventDispatcher implements EventDispatcher
{
    public array $events = [];

    public function listen($events, $listener = null)
    {
        //
    }

    public function hasListeners($eventName)
    {
        return false;
    }

    public function subscribe($subscriber)
    {
        //
    }

    public function until($event, $payload = [])
    {
        return null;
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        $this->events[] = $event;

        return null;
    }

    public function push($event, $payload = [])
    {
        //
    }

    public function flush($event)
    {
        //
    }

    public function forget($event)
    {
        //
    }

    public function forgetPushed()
    {
        //
    }
}
