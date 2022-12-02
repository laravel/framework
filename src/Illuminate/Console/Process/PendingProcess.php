<?php

namespace Illuminate\Console\Process;

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
    protected $command;

    /**
     * The working directory of the process.
     *
     * @var string
     */
    protected $path;

    /**
     * The maximum number of seconds the process may run.
     *
     * @var int|null
     */
    protected $timeout = 60;

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
     * @return \Illuminate\Console\Contracts\ProcessResult
     */
    public function run($command = null, $output = null)
    {
        try {
            $process = $this->toSymfonyProcess($command);

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
        return new InvokedProcess(tap($this->toSymfonyProcess($command))->start());
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
}
