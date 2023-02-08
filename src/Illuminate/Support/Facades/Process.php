<?php

namespace Illuminate\Support\Facades;

use Closure;
use Illuminate\Console\Process\Factory;

/**
 * @method static \Illuminate\Console\Process\PendingProcess command(array|string $command)
 * @method static \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method static \Illuminate\Console\Process\PendingProcess timeout(int $timeout)
 * @method static \Illuminate\Console\Process\PendingProcess idleTimeout(int $timeout)
 * @method static \Illuminate\Console\Process\PendingProcess forever()
 * @method static \Illuminate\Console\Process\PendingProcess env(array $environment)
 * @method static \Illuminate\Console\Process\PendingProcess quietly()
 * @method static \Illuminate\Console\Process\PendingProcess tty(bool $tty = true)
 * @method static \Illuminate\Console\Process\PendingProcess options(array $options)
 * @method static \Illuminate\Contracts\Console\Process\ProcessResult run(array|string|null $command = null, callable|null $output = null)
 * @method static \Illuminate\Console\Process\InvokedProcess start(array|string|null $command = null, callable $output = null)
 * @method static \Illuminate\Console\Process\PendingProcess withFakeHandlers(array $fakeHandlers)
 * @method static \Illuminate\Console\Process\FakeProcessResult result(array|string $output = '', array|string $errorOutput = '', int $exitCode = 0)
 * @method static \Illuminate\Console\Process\FakeProcessDescription describe()
 * @method static \Illuminate\Console\Process\FakeProcessSequence sequence(array $processes = [])
 * @method static bool isRecording()
 * @method static \Illuminate\Console\Process\Factory recordIfRecording(\Illuminate\Console\Process\PendingProcess $process, \Illuminate\Contracts\Console\Process\ProcessResult $result)
 * @method static \Illuminate\Console\Process\Factory record(\Illuminate\Console\Process\PendingProcess $process, \Illuminate\Contracts\Console\Process\ProcessResult $result)
 * @method static \Illuminate\Console\Process\Factory preventStrayProcesses(bool $prevent = true)
 * @method static bool preventingStrayProcesses()
 * @method static \Illuminate\Console\Process\Factory assertRan(\Closure|string $callback)
 * @method static \Illuminate\Console\Process\Factory assertRanTimes(\Closure|string $callback, int $times = 1)
 * @method static \Illuminate\Console\Process\Factory assertNotRan(\Closure|string $callback)
 * @method static \Illuminate\Console\Process\Factory assertDidntRun(\Closure|string $callback)
 * @method static \Illuminate\Console\Process\Factory assertNothingRan()
 * @method static \Illuminate\Console\Process\Pool pool(callable $callback)
 * @method static \Illuminate\Console\Process\ProcessPoolResults concurrently(callable $callback, callable|null $output = null)
 * @method static \Illuminate\Console\Process\PendingProcess newPendingProcess()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 *
 * @see \Illuminate\Console\Process\PendingProcess
 * @see \Illuminate\Console\Process\Factory
 */
class Process extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }

    /**
     * Indicate that the process factory should fake processes.
     *
     * @param  \Closure|array|null  $callback
     * @return \Illuminate\Console\Process\Factory
     */
    public static function fake(Closure|array $callback = null)
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }
}
