<?php

namespace Illuminate\Console\Process;

use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyTimeoutException;
use Symfony\Component\Process\Process;

class PendingProcess
{
    /**
     * The process factory instance.
     *
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    /**
     * The command to invoke the process.
     *
     * @var array<array-key, string>|string|null
     */
    public $command;

    /**
     * The working directory of the process.
     *
     * @var string
     */
    public $path;

    /**
     * The maximum number of seconds the process may run.
     *
     * @var int|null
     */
    public $timeout = 60;

    /**
     * The registered fake handler callbacks.
     *
     * @var array
     */
    protected $fakeHandlers = [];

    /**
     * Create a new pending process instance.
     *
     * @param  \Illuminate\Console\Process\Factory  $factory
     * @return void
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Specify the command that will invoke the process.
     *
     * @param  array<array-key, string>|string|null  $command
     * @return $this
     */
    public function command($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Specify the working directory of the process.
     *
     * @param  string  $path
     * @return $this
     */
    public function path($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Specify the maximum number of seconds the process may run.
     *
     * @param  int  $timeout
     * @return $this
     */
    public function timeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Indicate that the process may run forever without timing out.
     *
     * @return $this
     */
    public function forever()
    {
        $this->timeout = null;

        return $this;
    }

    /**
     * Run the process.
     *
     * @param  array<array-key, string>|string|null  $command
     * @param  callable|null  $output
     * @return \Illuminate\Contracts\Console\Process\ProcessResult
     */
    public function run($command = null, $output = null)
    {
        $this->command = $command ?: $this->command;

        try {
            $process = $this->toSymfonyProcess($command);

            if ($fake = $this->fakeFor($command = $process->getCommandline())) {
                return tap($this->resolveSynchronousFake($command, $fake), function ($result) {
                    $this->factory->recordIfRecording($this, $result);
                });
            } elseif ($this->factory->isRecording() && $this->factory->preventingStrayProcesses()) {
                throw new RuntimeException('Attempted process ['.(string) $this->command.'] without a matching fake.');
            }

            return new ProcessResult(tap($process)->run($output));
        } catch (SymfonyTimeoutException $e) {
            throw new ProcessTimedOutException($e, new ProcessResult($process));
        }
    }

    /**
     * Start the process in the background.
     *
     * @param  array<array-key, string>|string|null  $command
     * @return \Illuminate\Console\Process\InvokedProcess
     */
    public function start($command = null)
    {
        $this->command = $command ?: $this->command;

        $process = $this->toSymfonyProcess($command);

        if ($fake = $this->fakeFor($command = $process->getCommandline())) {
            return tap($this->resolveAsynchronousFake($command, $fake), function (FakeInvokedProcess $process) {
                $this->factory->recordIfRecording($this, $process->predictProcessResult());
            });
        } elseif ($this->factory->isRecording() && $this->factory->preventingStrayProcesses()) {
            throw new RuntimeException('Attempted process ['.(string) $this->command.'] without a matching fake.');
        }

        return new InvokedProcess(tap($process)->start());
    }

    /**
     * Get a Symfony Process instance from the current pending command.
     *
     * @param  array<array-key, string>|string|null  $command
     * @return \Symfony\Component\Process\Process
     */
    protected function toSymfonyProcess($command)
    {
        $command = $command ?? $this->command;

        $process = is_iterable($command)
                ? new Process($command)
                : Process::fromShellCommandline((string) $command);

        $process->setWorkingDirectory((string) $this->path ?? getcwd());
        $process->setTimeout($this->timeout);

        return $process;
    }

    /**
     * Specify the fake process result handlers for the pending process.
     *
     * @param  array  $fakeHandlers
     * @return $this
     */
    public function withFakeHandlers(array $fakeHandlers)
    {
        $this->fakeHandlers = $fakeHandlers;

        return $this;
    }

    /**
     * Get the fake handler for the given command, if applicable.
     *
     * @param  string  $command
     * @return \Closure|null
     */
    protected function fakeFor(string $command)
    {
        return collect($this->fakeHandlers)
                ->first(fn ($handler, $pattern) => Str::is($pattern, $command));
    }

    /**
     * Resolve the given fake handler for a synchronous process.
     *
     * @param  string  $command
     * @param  \Closure  $fake
     * @return mixed
     */
    protected function resolveSynchronousFake(string $command, $fake)
    {
        $result = $fake($this);

        if ($result instanceof ProcessResult) {
            return $result;
        } elseif ($result instanceof FakeProcessResult) {
            return $result->withCommand($command);
        } elseif ($result instanceof FakeProcessDescription) {
            return $result->toProcessResult($command);
        }

        throw new LogicException("Unsupported synchronous process fake result provided.");
    }

    /**
     * Resolve the given fake handler for an asynchronous process.
     *
     * @param  string  $command
     * @param  \Closure  $fake
     * @return \Illuminate\Console\Process\FakeInvokedProcess
     */
    protected function resolveAsynchronousFake(string $command, $fake)
    {
        $result = $fake($this);

        if ($result instanceof ProcessResult) {
            return new FakeInvokedProcess(
                $command,
                (new FakeProcessDescription)
                    ->output($result->output())
                    ->errorOutput($result->errorOutput())
                    ->runsFor(iterations: 0)
                    ->exitCode($result->exitCode())
            );
        } elseif ($result instanceof FakeProcessResult) {
            return new FakeInvokedProcess(
                $command,
                (new FakeProcessDescription)
                    ->output($result->output())
                    ->errorOutput($result->errorOutput())
                    ->runsFor(iterations: 0)
                    ->exitCode($result->exitCode())
            );
        } elseif ($result instanceof FakeProcessDescription) {
            return new FakeInvokedProcess($command, $result);
        }

        throw new LogicException("Unsupported asynchronous process fake result provided.");
    }
}
