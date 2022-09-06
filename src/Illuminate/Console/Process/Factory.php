<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Exceptions\ProcessNotRunningException;
use Illuminate\Console\Process;
use Illuminate\Console\Process\Results\FakeResult;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @method \Illuminate\Console\Contracts\ProcessResult run(iterable|string $command = [])
 * @method \Illuminate\Console\Process\PendingProcess dd()
 * @method \Illuminate\Console\Process\PendingProcess dump()
 * @method \Illuminate\Console\Process\PendingProcess forever()
 * @method \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method \Illuminate\Console\Process\PendingProcess timeout(int $seconds)
 * @method \Illuminate\Console\Process\PendingProcess stub(callable $callback)
 * @method \Illuminate\Console\Process\PendingProcess withArguments(iterable $arguments)
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
     * @var iterable<int, \Illuminate\Console\Process>
     */
    protected $recorded = [];

    /**
     * Assert that a process was recorded matching a given truth test.
     *
     * @param  (callable(\Illuminate\Console\Process): bool)|string  $callback
     * @return void
     */
    public function assertRan($callback)
    {
        $callback = is_string($callback) ? fn ($process) => $process->command() === $callback : $callback;

        PHPUnit::assertTrue(
            $this->recorded($callback)->count() > 0,
            'An expected process was not recorded.'
        );
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
                    if ($command === '*' || $process->command() == $command) {
                        return $result;
                    }
                });
            }

            return $this;
        }

        $this->stubs[] = fn ($process) => is_callable($callback) ? $callback($process) : $callback;

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
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public static function result($output = '', $exitCode = 0)
    {
        $output = is_array($output) ? implode("\n", $output) : $output;

        return new FakeResult($output, $exitCode);
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
