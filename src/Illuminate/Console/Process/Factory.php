<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Contracts\ProcessResult;
use Illuminate\Console\Exceptions\ProcessInPoolNotStartedException;
use Illuminate\Console\Process;
use Illuminate\Console\Process\Results\FakeResult;
use Illuminate\Console\Process\Results\Result;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @method \Illuminate\Console\Contracts\ProcessResult run(array|string|null $command = null, callable|null $output = null)
 * @method \Illuminate\Console\Process\PendingProcess async(bool $async = true)
 * @method \Illuminate\Console\Process\PendingProcess command(array|string $command)
 * @method \Illuminate\Console\Process\PendingProcess dd()
 * @method \Illuminate\Console\Process\PendingProcess dump()
 * @method \Illuminate\Console\Process\PendingProcess forever()
 * @method \Illuminate\Console\Process\PendingProcess output(callable $output)
 * @method \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method \Illuminate\Console\Process\PendingProcess timeout(float|null $seconds)
 *
 * @see \Illuminate\Console\Process\PendingProcess
 */
class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The stub callables that will handle processes.
     *
     * @var array<int, callable(\Illuminate\Console\Process): (\Illuminate\Console\Contracts\ProcessResult|null)>
     */
    protected $stubs = [];

    /**
     * Indicates if the factory is recording requests and responses.
     *
     * @var bool
     */
    protected $recording = false;

    /**
     * The recorded request array.
     *
     * @var array<int, array{0: \Illuminate\Console\Process, 1: \Illuminate\Console\Contracts\ProcessResult}>
     */
    protected $recorded = [];

    /**
     * Assert that a process was recorded matching a given truth test.
     *
     * @param  (callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): bool)|string  $command
     * @return $this
     */
    public function assertRan($command)
    {
        $callback = $this->makeAssertCallback($command);

        PHPUnit::assertTrue(
            $this->recorded($callback)->count() > 0,
            'An expected process was not recorded.'
        );

        return $this;
    }

    /**
     * Assert that the given process was ram in the given order.
     *
     * @param  array<int, (callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): bool)|string>  $commands
     * @return $this
     */
    public function assertRanInOrder($commands)
    {
        $this->assertRanCount(count($commands));

        foreach ($commands as $index => $command) {
            $callback = $this->makeAssertCallback($command);

            PHPUnit::assertTrue($callback(
                $this->recorded[$index][0],
                $this->recorded[$index][1]
            ), 'An expected process (#'.($index + 1).') was not recorded.');
        }

        return $this;
    }

    /**
     * Assert that a process was not recorded matching a given truth test.
     *
     * @param  (callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): bool)|string  $command
     * @return $this
     */
    public function assertNotRan($command)
    {
        $callback = $this->makeAssertCallback($command);

        PHPUnit::assertFalse(
            $this->recorded($callback)->count() > 0,
            'Unexpected process was recorded.'
        );

        return $this;
    }

    /**
     * Makes an assert callback for the given command "expectation".
     *
     * @param  array<array-key, string>|(callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): bool)|string $command
     * @return callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): bool
     */
    protected function makeAssertCallback($command)
    {
        if (is_string($command)) {
            return fn ($process, $result) => $process->command() === $command;
        }

        if (is_callable($command)) {
            return $command;
        }

        return fn ($process, $result) => $process->command() === with(new Process($command))->command();
    }

    /**
     * Assert that no process was recorded.
     *
     * @return $this
     */
    public function assertNothingRan()
    {
        PHPUnit::assertEmpty(
            $this->recorded,
            'Processes were recorded.'
        );

        return $this;
    }

    /**
     * Assert how many processes have been recorded.
     *
     * @param  int  $count
     * @return $this
     */
    public function assertRanCount($count)
    {
        PHPUnit::assertCount($count, $this->recorded);

        return $this;
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub results.
     *
     * @param  (array<int, callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult|null>)|(callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult|null)|null  $callback
     * @return $this
     */
    public function fake($callback = null)
    {
        $this->recording = true;

        if (is_null($callback)) {
            $callback = static::result();
        }

        if (is_iterable($callback)) {
            foreach ($callback as $command => $result) {
                $this->stubs[] = (function ($process) use ($command, $result) {
                    if (Str::is($command, $process->command())) {
                        return value($result);
                    }
                });
            }

            return $this;
        }

        $this->stubs[] = $callback;

        return $this;
    }

    /**
     * Send a pool of processes concurrently.
     *
     * @param  callable(\Illuminate\Console\Process\Pool): iterable<array-key, \Illuminate\Console\Contracts\ProcessResult>  $callback
     * @return array<array-key, \Illuminate\Console\Contracts\ProcessResult>
     */
    public function pool($callback)
    {
        return collect($callback(new Pool($this)))->each(function ($result) {
            if (! $result instanceof ProcessResult) {
                throw new ProcessInPoolNotStartedException('One or more processes added to the pool have not been started. Did you forget to call "run"?');
            }
        })->each->wait();
    }

    /**
     * Get a collection of the processes pairs matching the given truth test.
     *
     * @param  (callable(\Illuminate\Console\Process, \Illuminate\Console\Contracts\ProcessResult): bool)  $callback
     * @return \Illuminate\Support\Collection<int, array{0: \Illuminate\Console\Process, 1: \Illuminate\Console\Contracts\ProcessResult}>
     */
    public function recorded($callback)
    {
        return collect($this->recorded)
            ->filter(fn ($pair) => $callback($pair[0], $pair[1]))
            ->values();
    }

    /**
     * Create a new pending process instance for this factory.
     *
     * @return \Illuminate\Console\Process\PendingProcess
     */
    protected function newPendingProcess()
    {
        return new PendingProcess($this);
    }

    /**
     * Create a new result instance for use during stubbing.
     *
     * @param  array<array-key, string>|string  $output
     * @param  int  $exitCode
     * @param  array<array-key, string>|string  $errorOutput
     * @return callable(): \Illuminate\Console\Contracts\ProcessResult
     */
    public static function result($output = '', $exitCode = 0, $errorOutput = '')
    {
        return function () use ($output, $exitCode, $errorOutput) {
            if (is_array($output)) {
                $output = collect($output)->map(fn ($line) => "$line\n")->implode('');
            }

            if (is_array($errorOutput)) {
                $errorOutput = collect($errorOutput)->map(fn ($line) => "$line\n")->implode('');
            }

            return new FakeResult($output, $exitCode, $errorOutput);
        };
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  iterable<array-key, string>  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->newPendingProcess()
            ->afterWait(function ($process, $result) {
                if ($this->recording) {
                    $this->recorded[] = [$process, $result];
                }
            })
            ->stubs($this->stubs)
            ->$method(...$parameters);
    }
}
