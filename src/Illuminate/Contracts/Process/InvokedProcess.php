<?php

namespace Illuminate\Contracts\Process;

interface InvokedProcess
{
    /**
     * Get the process ID if the process is still running.
     *
     * @return int|null
     */
    public function id();

    /**
     * Send a signal to the process.
     *
     * @return $this
     */
    public function signal(int $signal);

    /**
     * Determine if the process is still running.
     *
     * @return bool
     */
    public function running();

    /**
     * Get the standard output for the process.
     *
     * @return string
     */
    public function output();

    /**
     * Get the error output for the process.
     *
     * @return string
     */
    public function errorOutput();

    /**
     * Get the latest standard output for the process.
     *
     * @return string
     */
    public function latestOutput();

    /**
     * Get the latest error output for the process.
     *
     * @return string
     */
    public function latestErrorOutput();

    /**
     * Wait for the process to finish.
     *
     * @return \Illuminate\Process\ProcessResult
     */
    public function wait(?callable $output = null);
}
