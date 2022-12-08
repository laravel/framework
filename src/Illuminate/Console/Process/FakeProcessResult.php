<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Process\Exceptions\ProcessFailedException;
use Illuminate\Contracts\Console\Process\ProcessResult as ProcessResultContract;
use Symfony\Component\Process\Process;

class FakeProcessResult implements ProcessResultContract
{
    /**
     * The command string.
     *
     * @var string
     */
    protected $command;

    /**
     * The process exit code.
     *
     * @var int
     */
    protected $exitCode;

    /**
     * The process output.
     *
     * @var string
     */
    protected $output;

    /**
     * The process error output.
     *
     * @var string
     */
    protected $errorOutput;

    /**
     * Create a new process result instance.
     *
     * @param  string  $command
     * @param  int  $exitCode
     * @param  string  $output
     * @param  string  $errorOutput
     * @return void
     */
    public function __construct(string $command = '', int $exitCode = 0, string $output = '', string $errorOutput = '')
    {
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->errorOutput = $errorOutput;
    }

    /**
     * Get the original command executed by the process.
     *
     * @return string
     */
    public function command()
    {
        return $this->command;
    }

    /**
     * Create a new fake process result with the given command.
     *
     * @param  string  $command
     * @return self
     */
    public function withCommand(string $command)
    {
        return new FakeProcessResult($command, $this->exitCode, $this->output, $this->errorOutput);
    }

    /**
     * Determine if the process was successful.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->exitCode === 0;
    }

    /**
     * Determine if the process failed.
     *
     * @return bool
     */
    public function failed()
    {
        return ! $this->successful();
    }

    /**
     * Get the exit code of the process.
     *
     * @return int
     */
    public function exitCode()
    {
        return $this->exitCode;
    }

    /**
     * Get the standard output of the process.
     *
     * @return string
     */
    public function output()
    {
        return $this->output;
    }

    /**
     * Get the error output of the process.
     *
     * @return string
     */
    public function errorOutput()
    {
        return $this->errorOutput;
    }

    /**
     * Throw an exception if the process failed.
     *
     * @param  callable|null  $callback
     * @return $this
     */
    public function throw(callable $callback = null)
    {
        if ($this->successful()) {
            return $this;
        }

        $exception = new ProcessFailedException($this);

        if ($callback) {
            $callback($this, $exception);
        }

        throw $exception;
    }

    /**
     * Throw an exception if the process failed and the given condition is true.
     *
     * @param  bool  $condition
     * @param  callable|null  $callback
     * @return $this
     */
    public function throwIf(bool $condition, callable $callback = null)
    {
        if ($condition) {
            return $this->throw($callback);
        }

        return $this;
    }
}
