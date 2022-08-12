<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Orchestra\Testbench\TestCase;

class JobDispatchingTest extends TestCase
{
    protected function tearDown(): void
    {
        Job::$ran = false;
    }

    public function testJobCanUseCustomMethodsAfterDispatch()
    {
        Job::dispatch('test')->replaceValue('new-test');

        $this->assertTrue(Job::$ran);
        $this->assertSame('new-test', Job::$value);
    }
}

class Job implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;
    public static $value = null;

    public function __construct($value)
    {
        static::$value = $value;
    }

    public function handle()
    {
        static::$ran = true;
    }

    public function replaceValue($value)
    {
        static::$value = $value;
    }
}
