<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Illuminate\Concurrency\SyncDriver;
use Illuminate\Support\Defer\DeferredCallback;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class SyncDriverTest extends TestCase
{
    private SyncDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new SyncDriver();
    }

    public function testRunExecutesTasksSynchronously()
    {
        $executed = [];

        $results = $this->driver->run([
            'first' => function () use (&$executed) {
                $executed[] = 'first';

                return 1;
            },
            'second' => function () use (&$executed) {
                $executed[] = 'second';

                return 2;
            },
        ]);

        $this->assertEquals(['first', 'second'], $executed);
        $this->assertEquals(['first' => 1, 'second' => 2], $results);
    }

    public function testRunWithSingleCallback()
    {
        $callback = fn () => 'result';

        $results = $this->driver->run($callback);

        $this->assertEquals(['result'], $results);
    }

    public function testDeferReturnsCallbackImplementation()
    {
        $executed = [];

        $deferred = $this->driver->defer([
            function () use (&$executed) {
                $executed[] = 'first';
            },
            function () use (&$executed) {
                $executed[] = 'second';
            },
        ]);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);
        $this->assertEmpty($executed, 'Tasks should not be executed immediately');
    }

    public function testDeferWithSingleCallback()
    {
        $executed = false;
        $callback = function () use (&$executed) {
            $executed = true;
        };

        $deferred = $this->driver->defer($callback);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);
        $this->assertFalse($executed, 'Task should not be executed immediately');
    }

    public function testRunHandlesExceptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $this->driver->run([
            fn () => throw new RuntimeException('Test exception'),
        ]);
    }
}
