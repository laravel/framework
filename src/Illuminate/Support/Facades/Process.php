<?php

namespace Illuminate\Support\Facades;

use Illuminate\Console\Process\Factory;

/**
 * @method static \Illuminate\Console\Process\FakeProcessResult result(array|string $output = '', array|string $errorOutput = '', int $exitCode = 0)
 * @method static \Illuminate\Console\Process\FakeProcessDescription describe()
 * @method static \Illuminate\Console\Process\FakeProcessSequence sequence(array $processes = [])
 * @method static \Illuminate\Console\Process\Factory fake(\Closure|array|null $callback = null)
 * @method static bool isRecording()
 * @method static \Illuminate\Console\Process\Factory recordIfRecording(\Illuminate\Console\Process\PendingProcess $process, \Illuminate\Contracts\Console\Process\ProcessResult $result)
 * @method static \Illuminate\Console\Process\Factory record(\Illuminate\Console\Process\PendingProcess $process, \Illuminate\Contracts\Console\Process\ProcessResult $result)
 * @method static \Illuminate\Console\Process\Factory preventStrayProcesses(bool $prevent = true)
 * @method static bool preventingStrayProcesses()
 * @method static \Illuminate\Console\Process\Factory assertRan(\Closure $callback)
 * @method static \Illuminate\Console\Process\Factory assertRanTimes(\Closure $callback, int $times = 1)
 * @method static \Illuminate\Console\Process\Factory assertNotRan(\Closure $callback)
 * @method static \Illuminate\Console\Process\Factory assertNothingRan()
 * @method static \Illuminate\Console\Process\Pool pool(callable $callback)
 * @method static \Illuminate\Console\Process\ProcessPoolResults concurrently(callable $callback, callable|null $output)
 * @method static \Illuminate\Console\Process\PendingProcess newPendingProcess()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 *
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
}
