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
     * The number of times the process should indicate that it is "running".
     *
     * @var int
     */
    public $runIterations = 0;

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
     * Specify how many times the "isRunning" method should return "true".
     *
     * @param  int  $iterations
     * @return $this
     */
    public function runsFor(int $iterations)
    {
        $this->runIterations = $iterations;

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
