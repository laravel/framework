<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Exceptions\ProcessNotRunningException;
use Illuminate\Console\Process;
use Illuminate\Console\Process\Results\FakeResult;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @method \Illuminate\Console\Contracts\ProcessResult run(array|string|null $command = null, callable|null $output = null)
 * @method \Illuminate\Console\Process\PendingProcess command(array|string $command)
 * @method \Illuminate\Console\Process\PendingProcess dd()
 * @method \Illuminate\Console\Process\PendingProcess dump()
 * @method \Illuminate\Console\Process\PendingProcess output(callable $output)
 * @method \Illuminate\Console\Process\PendingProcess forever()
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
     * @var array<int, callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult>
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
     * @var array<int, \Illuminate\Console\Process>
     */
    protected $recorded = [];

    /**
     * Assert that a process was recorded matching a given truth test.
     *
     * @param  (callable(\Illuminate\Console\Process): bool)|string  $command
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
     * @param  iterable<int, (callable(\Illuminate\Console\Process): bool)|string>  $commands
     * @return $this
     */
    public function assertRanInOrder($commands)
    {
        $this->assertRanCount(count($commands));

        foreach ($commands as $index => $command) {
            $callback = $this->makeAssertCallback($command);

            PHPUnit::assertTrue($callback(
                $this->recorded[$index],
            ), 'An expected process (#'.($index + 1).') was not recorded.');
        }

        return $this;
    }

    /**
     * Assert that a process was not recorded matching a given truth test.
     *
     * @param  (callable(\Illuminate\Console\Process): bool)|string  $command
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
     * @param  array<array-key, string>|(callable(\Illuminate\Console\Process): bool)|string $command
     * @return callable(\Illuminate\Console\Process): bool
     */
    protected function makeAssertCallback($command)
    {
        if (is_string($command)) {
            return fn ($process) => $process->command() === $command;
        }

        if (is_callable($command)) {
            return $command;
        }

        return fn ($process) => $process->command() === with(new Process($command))->command();
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
     * @param  (iterable<string, callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult>)|(callable(\Illuminate\Console\Process): \Illuminate\Console\Contracts\ProcessResult)|null  $callback
     * @return $this
     */
    public function fake($callback = null)
    {
        $this->recording = true;

        if (is_null($callback)) {
            $callback = fn () => static::result();
        }

        if (is_iterable($callback)) {
            foreach ($callback as $command => $result) {
                $this->stubs[] = (function ($process) use ($command, $result) {
                    if (Str::is($command, $process->command())) {
                        return $result;
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
        $results = $callback(new Pool($this));

        return collect($results)->each(function ($result) {
            if (! $result instanceof DelayedStart) {
                throw new ProcessNotRunningException('Process has not been started. Did you forget to call "run"?');
            }
        })->map->start()->values();
    }

    /**
     * Get a collection of the processes pairs matching the given truth test.
     *
     * @param  (callable(\Illuminate\Console\Process): bool)  $callback
     * @return \Illuminate\Support\Collection<int, \Illuminate\Console\Process>
     */
    public function recorded($callback)
    {
        return collect($this->recorded)
            ->filter(fn ($process) => $callback($process))
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
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public static function result($output = '', $exitCode = 0, $errorOutput = '')
    {
        $output = is_array($output) ? implode("\n", $output) : $output;
        $errorOutput = is_array($errorOutput) ? implode("\n", $errorOutput) : $errorOutput;

        return new FakeResult($output, $exitCode, $errorOutput);
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
            ->beforeStart(function ($process) {
                if ($this->recording) {
                    $this->recorded[] = $process;
                }
            })
            ->stubs($this->stubs)
            ->$method(...$parameters);
    }
}
