<?php

namespace Illuminate\Console\Process;

use Symfony\Component\Process\Process;

class FakeProcessDescription
{
    /**
     * The process' ID.
     *
     * @var int|null
     */
    public $processId = 1000;

    /**
     * All of the process' output in the order it was described.
     *
     * @var array
     */
    public $output = [];

    /**
     * The process' exit code.
     *
     * @var int
     */
    public $exitCode = 0;

    /**
     * The current output's index.
     *
     * @var int
     */
    protected $lastOutputIndex = 0;

    /**
     * The current error output's index.
     *
     * @var int
     */
    protected $lastErrorOutputIndex = 0;

    /**
     * Specify the process ID that should be assigned to the process.
     *
     * @param  int  $processId
     * @return $this
     */
    public function id(int $processId)
    {
        $this->processId = $processId;

        return $this;
    }

    /**
     * Describe a line of standard output.
     *
     * @param  string  $output
     * @return $this
     */
    public function output(string $output)
    {
        $this->output[] = ['type' => 'out', 'buffer' => $output];

        return $this;
    }

    /**
     * Describe a line of error output.
     *
     * @param  string  $output
     * @return $this
     */
    public function errorOutput(string $output)
    {
        $this->output[] = ['type' => 'err', 'buffer' => $output];

        return $this;
    }

    /**
     * Specify the process exit code.
     *
     * @param  int  $exitCode
     * @return $this
     */
    public function exitCode(int $exitCode)
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    /**
     * Get the next output for the process.
     *
     * @return string
     */
    public function getIncrementalOutput()
    {
        $outputCount = count($this->output);

        for ($i = $this->lastOutputIndex; $i < $outputCount; $i++) {
            if ($this->output[$i]['type'] === 'out') {
                $output = $this->output[$i]['buffer'];

                $this->lastOutputIndex = $i;

                break;
            }

            $this->lastOutputIndex = $i;
        }

        return $output ?? '';
    }

    /**
     * Get the next error output for the process.
     *
     * @return string
     */
    public function getIncrementalErrorOutput()
    {
        $outputCount = count($this->output);

        for ($i = $this->lastErrorOutputIndex; $i < $outputCount; $i++) {
            if ($this->output[$i]['type'] === 'err') {
                $output = $this->output[$i]['buffer'];

                $this->lastErrorOutputIndex = $i;

                break;
            }

            $this->lastErrorOutputIndex = $i;
        }

        return $output ?? '';
    }

    /**
     * Reset the incremental output.
     *
     * @return $this
     */
    public function resetIncrementalOutput()
    {
        $this->lastOutputIndex = 0;
        $this->lastErrorOutputIndex = 0;

        return $this;
    }

    /**
     * Turn the fake process description into an actual process.
     *
     * @param  string  $command
     * @return \Symfony\Component\Process\Process
     */
    public function toSymfonyProcess(string $command)
    {
        return Process::fromShellCommandline($command);
    }

    /**
     * Conver the process description into a process result.
     *
     * @param  string  $command
     * @return \Illuminate\Contracts\Console\Process\ProcessResult
     */
    public function toProcessResult(string $command)
    {
        return new FakeProcessResult(
            command: $command,
            exitCode: $this->exitCode,
            output: $this->resolveOutput(),
            errorOutput: $this->resolveErrorOutput(),
        );
    }

    /**
     * Resolve the standard output as a string.
     *
     * @return string
     */
    protected function resolveOutput()
    {
        return collect($this->output)
                ->filter(fn ($output) => $output['type'] === 'out')
                ->map
                ->buffer
                ->implode("\n");
    }

    /**
     * Resolve the error output as a string.
     *
     * @return string
     */
    protected function resolveErrorOutput()
    {
        return collect($this->output)
                ->filter(fn ($output) => $output['type'] === 'err')
                ->map
                ->buffer
                ->implode("\n");
    }
}
