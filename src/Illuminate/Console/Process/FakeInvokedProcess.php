<?php

namespace Illuminate\Console\Process;

use Illuminate\Console\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Contracts\Console\Process\InvokedProcess as InvokedProcessContract;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyTimeoutException;
use Symfony\Component\Process\Process;

class FakeInvokedProcess implements InvokedProcessContract
{
    /**
     * The command being faked.
     *
     * @var string
     */
    protected $command;

    /**
     * The underlying process description.
     *
     * @var \Illuminate\Console\Process\FakeProcessDescription
     */
    protected $process;

    /**
     * The signals that have been received.
     *
     * @var array
     */
    protected $receivedSignals = [];

    /**
     * Create a new invoked process instance.
     *
     * @param  string  $command
     * @param  \Illuminate\Console\Process\FakeProcessDescription  $process
     * @return void
     */
    public function __construct(string $command, FakeProcessDescription $process)
    {
        $this->command = $command;
        $this->process = $process;
    }

    /**
     * Get the process ID if the process is still running.
     *
     * @return int|null
     */
    public function id()
    {
        return $this->process->processId;
    }

    /**
     * Send a signal to the process.
     *
     * @param  int  $signal
     * @return $this
     */
    public function signal(int $signal)
    {
        $this->receivedSignals[] = $signal;

        return $this;
    }

    /**
     * Determine if the process is still running.
     *
     * @return bool
     */
    public function running()
    {
        return $this->process->isRunning();
    }

    /**
     * Get the latest standard output for the process.
     *
     * @return string
     */
    public function latestOutput()
    {
        return $this->process->getIncrementalOutput();
    }

    /**
     * Get the latest error output for the process.
     *
     * @return string
     */
    public function latestErrorOutput()
    {
        return $this->process->getIncrementalErrorOutput();
    }

    /**
     * Wait for the process to finish.
     *
     * @param  callable|null  $output
     * @return \Illuminate\Console\Process\ProcessResult
     */
    public function wait($output = null)
    {
        return $this->process->toProcessResult($this->command);
    }

    /**
     * Wait for some given output from the process.
     *
     * @param  callable  $output
     * @return $this
     */
    public function waitUntil($output)
    {
        foreach ($this->process->output as $processOutput) {
            if ($output($processOutput['type'], $processOutput['buffer'])) {
                return $this;
            }
        }

        throw new ProcessTimedOutException(
            new SymfonyTimeoutException($this->process->toSymfonyProcess($this->command), 1),
            $this->process->toProcessResult(),
        );
    }
}
