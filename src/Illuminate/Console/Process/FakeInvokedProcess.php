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
     * The number of times the process should indicate that it is "running".
     *
     * @var int
     */
    protected $runIterations;

    /**
     * The current output's index.
     *
     * @var int
     */
    protected $nextOutputIndex = 0;

    /**
     * The current error output's index.
     *
     * @var int
     */
    protected $nextErrorOutputIndex = 0;

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
     * Determine if the process has received the given signal.
     *
     * @param  int  $signal
     * @return bool
     */
    public function hasReceivedSignal(int $signal)
    {
        return in_array($signal, $this->receivedSignals);
    }

    /**
     * Determine if the process is still running.
     *
     * @return bool
     */
    public function running()
    {
        $this->runIterations = is_null($this->runIterations)
                ? $this->process->runIterations
                : $this->runIterations;

        if ($this->runIterations === 0) {
            return false;
        }

        $this->runIterations = $this->runIterations - 1;

        return true;
    }

    /**
     * Get the latest standard output for the process.
     *
     * @return string
     */
    public function latestOutput()
    {
        $outputCount = count($this->process->output);

        for ($i = $this->nextOutputIndex; $i < $outputCount; $i++) {
            if ($this->process->output[$i]['type'] === 'out') {
                $output = $this->process->output[$i]['buffer'];

                $this->nextOutputIndex = $i + 1;

                break;
            }

            $this->nextOutputIndex = $i + 1;
        }

        return $output ?? '';
    }

    /**
     * Get the latest error output for the process.
     *
     * @return string
     */
    public function latestErrorOutput()
    {
        $outputCount = count($this->process->output);

        for ($i = $this->nextErrorOutputIndex; $i < $outputCount; $i++) {
            if ($this->process->output[$i]['type'] === 'err') {
                $output = $this->process->output[$i]['buffer'];

                $this->nextErrorOutputIndex = $i + 1;

                break;
            }

            $this->nextErrorOutputIndex = $i + 1;
        }

        return $output ?? '';
    }

    /**
     * Wait for the process to finish.
     *
     * @param  callable|null  $output
     * @return \Illuminate\Console\Process\ProcessResult
     */
    public function wait($output = null)
    {
        [$outputCount, $outputStartingPoint] = [
            count($this->process->output),
            min($this->nextOutputIndex, $this->nextErrorOutputIndex),
        ];

        if (! $output) {
            return $this->process->toProcessResult($this->command);
        }

        for ($i = $outputStartingPoint; $i < $outputCount; $i++) {
            $currentOutput = $this->process->output[$i];

            if (($currentOutput['type'] === 'out' && $i >= $this->nextOutputIndex) ||
                ($currentOutput['type'] === 'err' && $i >= $this->nextErrorOutputIndex)) {
                $output($currentOutput['type'], $currentOutput['buffer']);
            }
        }

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
