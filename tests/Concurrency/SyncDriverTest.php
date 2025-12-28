<?php

namespace Illuminate\Tests\Concurrency;

use Illuminate\Concurrency\SyncDriver;
use Illuminate\Support\Defer\DeferredCallback;
use Orchestra\Testbench\TestCase;

class SyncDriverTest extends TestCase
{
    public function testRunReturnsResultsAndPreservesKeys()
    {
        $driver = new SyncDriver();

        $input = [
            'first' => fn () => 1 + 1,
            'second' => fn () => 2 + 2,
        ];

        $output = $driver->run($input);

        $this->assertIsArray($output);
        $this->assertArrayHasKey('first', $output);
        $this->assertArrayHasKey('second', $output);
        $this->assertEquals(2, $output['first']);
        $this->assertEquals(4, $output['second']);
    }

    public function testRunWithListInputPreservesOrder()
    {
        $driver = new SyncDriver();

        [$first, $second, $third] = $driver->run([
            function () { usleep(100000); return 'first'; },
            function () { usleep(50000); return 'second'; },
            function () { usleep(20000); return 'third'; },
        ]);

        $this->assertEquals('first', $first);
        $this->assertEquals('second', $second);
        $this->assertEquals('third', $third);
    }

    public function testDeferReturnsDeferredCallbackThatInvokesTasks()
    {
        $driver = new SyncDriver();

        $called = [];

        $deferred = $driver->defer([
            function () use (&$called) { $called[] = 'first'; },
            function () use (&$called) { $called[] = 'second'; },
        ]);

        $this->assertInstanceOf(DeferredCallback::class, $deferred);
        $this->assertEmpty($called);

        // Invoke the deferred callback
        $deferred();

        $this->assertCount(2, $called);
        $this->assertEquals(['first', 'second'], $called);
    }
}

