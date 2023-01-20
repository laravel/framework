<?php

namespace Illuminate\Tests\Integration\Console;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CommandDurationThresholdTest extends TestCase
{
    public function testItCanHandleExceedingCommandDuration()
    {
        $kernel = $this->app[Kernel::class];
        $kernel->command('foo', fn () => null);
        $input = new StringInput('foo');
        $called = false;
        $kernel->whenCommandLifecycleIsLongerThan(CarbonInterval::seconds(1), function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(Carbon::now());
        $kernel->handle($input, new ConsoleOutput);

        $this->assertFalse($called);

        Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds(1));
        $kernel->terminate($input, 21);

        $this->assertTrue($called);
    }

    public function testItDoesntCallWhenExactlyThresholdDuration()
    {
        $kernel = $this->app[Kernel::class];
        $kernel->command('foo', fn () => null);
        $input = new StringInput('foo');
        $called = false;
        $kernel->whenCommandLifecycleIsLongerThan(CarbonInterval::seconds(1), function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(Carbon::now());
        $kernel->handle($input, new ConsoleOutput);

        $this->assertFalse($called);

        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        $kernel->terminate($input, 21);

        $this->assertFalse($called);
    }

    public function testItProvidesArgsToHandler()
    {
        $kernel = $this->app[Kernel::class];
        $kernel->command('foo', fn () => null);
        $input = new StringInput('foo');
        $args = null;
        $kernel->whenCommandLifecycleIsLongerThan(CarbonInterval::seconds(0), function () use (&$args) {
            $args = func_get_args();
        });

        Carbon::setTestNow($startedAt = Carbon::now());
        $kernel->handle($input, new ConsoleOutput);
        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        $kernel->terminate($input, 21);

        $this->assertCount(3, $args);
        $this->assertTrue($startedAt->eq($args[0]));
        $this->assertSame($input, $args[1]);
        $this->assertSame(21, $args[2]);
    }

    public function testItCanExceedThresholdWhenSpecifyingDurationAsMilliseconds()
    {
        $kernel = $this->app[Kernel::class];
        $kernel->command('foo', fn () => null);
        $input = new StringInput('foo');
        $called = false;
        $kernel->whenCommandLifecycleIsLongerThan(1000, function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(Carbon::now());
        $kernel->handle($input, new ConsoleOutput);

        $this->assertFalse($called);

        Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMilliseconds(1));
        $kernel->terminate($input, 21);

        $this->assertTrue($called);
    }

    public function testItCanStayUnderThresholdWhenSpecifyingDurationAsMilliseconds()
    {
        $kernel = $this->app[Kernel::class];
        $kernel->command('foo', fn () => null);
        $input = new StringInput('foo');
        $called = false;
        $kernel->whenCommandLifecycleIsLongerThan(1000, function () use (&$called) {
            $called = true;
        });

        Carbon::setTestNow(Carbon::now());
        $kernel->handle($input, new ConsoleOutput);

        $this->assertFalse($called);

        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        $kernel->terminate($input, 21);

        $this->assertFalse($called);
    }

    public function testItCanExceedThresholdWhenSpecifyingDurationAsDateTime()
    {
        retry(2, function () {
            Carbon::setTestNow(Carbon::now());

            $input = new StringInput('foo');
            $called = false;

            $kernel = $this->app[Kernel::class];
            $kernel->command('foo', fn () => null);
            $kernel->whenCommandLifecycleIsLongerThan(Carbon::now()->addSecond()->addMillisecond(), function () use (&$called) {
                $called = true;
            });

            $kernel->handle($input, new ConsoleOutput);

            $this->assertFalse($called);

            Carbon::setTestNow(Carbon::now()->addSeconds(1)->addMillisecond());

            $kernel->terminate($input, 21);

            $this->assertTrue($called);
        }, 500);
    }

    public function testItCanStayUnderThresholdWhenSpecifyingDurationAsDateTime()
    {
        Carbon::setTestNow(Carbon::now());
        $kernel = $this->app[Kernel::class];
        $kernel->command('foo', fn () => null);
        $input = new StringInput('foo');
        $called = false;
        $kernel->whenCommandLifecycleIsLongerThan(Carbon::now()->addSecond()->addMillisecond(), function () use (&$called) {
            $called = true;
        });

        $kernel->handle($input, new ConsoleOutput);

        $this->assertFalse($called);

        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        $kernel->terminate($input, 21);

        $this->assertFalse($called);
    }

    public function testItClearsStartTimeAfterHandlingCommand()
    {
        $kernel = $this->app[Kernel::class];
        $kernel->command('foo', fn () => null);
        $input = new StringInput('foo');

        $this->assertNull($kernel->commandStartedAt());

        $kernel->handle($input, new ConsoleOutput);
        $this->assertNotNull($kernel->commandStartedAt());

        $kernel->terminate($input, 21);
        $this->assertNull($kernel->commandStartedAt());
    }
}
