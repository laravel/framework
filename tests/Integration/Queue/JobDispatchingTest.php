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
        Job::$value = null;
    }

    public function testJobCanUseCustomMethodsAfterDispatch()
    {
        Job::dispatch('test')->replaceValue('new-test');

        $this->assertTrue(Job::$ran);
        $this->assertSame('new-test', Job::$value);
    }

    public function testDispatchesConditionallyWithBoolean()
    {
        Job::dispatchIf(false, 'test')->replaceValue('new-test');

        $this->assertFalse(Job::$ran);
        $this->assertNull(Job::$value);

        Job::dispatchIf(true, 'test')->replaceValue('new-test');

        $this->assertTrue(Job::$ran);
        $this->assertSame('new-test', Job::$value);
    }

    public function testDispatchesConditionallyWithClosure()
    {
        Job::dispatchIf(fn ($job) => $job instanceof Job ? 0 : 1, 'test')->replaceValue('new-test');

        $this->assertFalse(Job::$ran);

        Job::dispatchIf(fn ($job) => $job instanceof Job ? 1 : 0, 'test')->replaceValue('new-test');

        $this->assertTrue(Job::$ran);
    }

    public function testDoesNotDispatchesConditionallyWithBoolean()
    {
        Job::dispatchUnless(true, 'test')->replaceValue('new-test');

        $this->assertFalse(Job::$ran);
        $this->assertNull(Job::$value);

        Job::dispatchUnless(false, 'test')->replaceValue('new-test');

        $this->assertTrue(Job::$ran);
        $this->assertSame('new-test', Job::$value);
    }

    public function testDoesNotDispatchesConditionallyWithClosure()
    {
        Job::dispatchUnless(fn ($job) => $job instanceof Job ? 1 : 0, 'test')->replaceValue('new-test');

        $this->assertFalse(Job::$ran);

        Job::dispatchUnless(fn ($job) => $job instanceof Job ? 0 : 1, 'test')->replaceValue('new-test');

        $this->assertTrue(Job::$ran);
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
