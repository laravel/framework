<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Process\Results\FakeResult;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Process\Process;

/**
 * @method \Illuminate\Console\Contracts\ProcessResult run(iterable|string $arguments)
 * @method \Illuminate\Console\Process\PendingProcess dd()
 * @method \Illuminate\Console\Process\PendingProcess dump()
 * @method \Illuminate\Console\Process\PendingProcess forever()
 * @method \Illuminate\Console\Process\PendingProcess path(string $path)
 * @method \Illuminate\Console\Process\PendingProcess timeout(int $seconds)
 * @method \Illuminate\Console\Process\PendingProcess stub(callable $callback)
 * @method \Illuminate\Console\Process\PendingProcess withArguments(iterable $arguments)
 */
class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The stub callables that will handle processes.
     *
     * @var iterable<int, callable>
     */
    protected $stubCallbacks;

    /**
     * Register a stub callable that will intercept requests and be able to return stub results.
     *
     * @param  iterable<string, callable(\Symfony\Component\Process\Process): \Illuminate\Console\Contracts\ProcessResult>|callable(\Symfony\Component\Process\Process): \Illuminate\Console\Contracts\ProcessResult|string|null  $callback
     * @return $this
     */
    public function fake($callback = null)
    {
        $this->stubCallbacks ??= collect();

        if (is_null($callback)) {
            $callback = fn () => static::result();
        }

        if (is_iterable($callback)) {
            foreach ($callback as $url => $result) {
                $this->stubCallbacks->push(function ($process) use ($url, $result) {
                    $url = str($url)->explode(' ')
                        ->map(fn ($part) => trim($part))
                        ->filter(fn ($part) => ! empty($part))
                        ->values()
                        ->implode(' ');

                    if ($url === '*' || $process->getCommandline() === (new Process(explode(' ', $url)))->getCommandLine()) {
                        return $result;
                    }
                });
            }

            return $this;
        }

        $this->stubCallbacks = $this->stubCallbacks->push(
            fn ($process) => is_callable($callback) ? $callback($process) : $callback,
        );

        return $this;
    }

    /**
     * Create a new pending process instance for this factory.
     *
     * @return \Illuminate\Console\Process\PenfdingProcess
     */
    protected function newPendingProcess()
    {
        return new PendingProcess();
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

        return tap($this->newPendingProcess(), function ($request) {
            $request->stub($this->stubCallbacks);
        })->{$method}(...$parameters);
    }
}
