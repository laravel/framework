<?php

namespace Illuminate\Tests\Concurrency;

use Illuminate\Concurrency\SyncDriver;
use Illuminate\Container\Container;
use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SyncDriverTest extends TestCase
{
    protected $previousContainer;

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    protected function swapDeferredCallbackCollection()
    {
        $this->previousContainer = Container::getInstance();

        $container = new Container;
        $container->singleton(DeferredCallbackCollection::class);

        Container::setInstance($container);
    }

    public function testRunInvokesTheGivenTaskAndReturnsItsResult()
    {
        $driver = new SyncDriver;

        $this->assertSame(['result'], $driver->run(fn () => 'result'));
    }

    public function testRunInvokesEachGivenTaskAndPreservesTheirKeys()
    {
        $driver = new SyncDriver;

        $results = $driver->run([
            'first' => fn () => 1,
            'second' => fn () => 2,
        ]);

        $this->assertSame(['first' => 1, 'second' => 2], $results);
    }

    public function testRunInvokesTheGivenTasksInOrder()
    {
        $driver = new SyncDriver;

        $invoked = [];

        $driver->run([
            function () use (&$invoked) {
                $invoked[] = 'first';
            },
            function () use (&$invoked) {
                $invoked[] = 'second';
            },
            function () use (&$invoked) {
                $invoked[] = 'third';
            },
        ]);

        $this->assertSame(['first', 'second', 'third'], $invoked);
    }

    public function testRunReturnsAnEmptyArrayWhenGivenNoTasks()
    {
        $driver = new SyncDriver;

        $this->assertSame([], $driver->run([]));
    }

    public function testRunPropagatesExceptionsThrownByTheGivenTasks()
    {
        $driver = new SyncDriver;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The task failed.');

        $driver->run(fn () => throw new RuntimeException('The task failed.'));
    }

    public function testDeferReturnsADeferredCallback()
    {
        $driver = new SyncDriver;

        $this->assertInstanceOf(DeferredCallback::class, $driver->defer(fn () => null));
    }

    public function testDeferInvokesTheGivenTaskWhenTheDeferredCallbacksAreInvoked()
    {
        $this->swapDeferredCallbackCollection();

        $driver = new SyncDriver;
        $invoked = false;

        $driver->defer(function () use (&$invoked) {
            $invoked = true;
        });

        $this->assertFalse($invoked);

        Container::getInstance()->make(DeferredCallbackCollection::class)->invoke();

        $this->assertTrue($invoked);
    }

    public function testDeferInvokesEachGivenTaskWhenTheDeferredCallbacksAreInvoked()
    {
        $this->swapDeferredCallbackCollection();

        $driver = new SyncDriver;
        $invoked = [];

        $driver->defer([
            function () use (&$invoked) {
                $invoked[] = 'first';
            },
            function () use (&$invoked) {
                $invoked[] = 'second';
            },
        ]);

        $this->assertSame([], $invoked);

        Container::getInstance()->make(DeferredCallbackCollection::class)->invoke();

        $this->assertSame(['first', 'second'], $invoked);
    }
}
