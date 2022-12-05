<?php

namespace Illuminate\Contracts\Console\Process;

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
     * @param  int  $signal
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
     * @param  callable|null  $output
     * @return \Illuminate\Console\Process\ProcessResult
     */
    public function wait($output = null);

    /**
     * Wait for some given output from the process.
     *
     * @param  callable  $output
     * @return $this
     */
    public function waitUntil($output);
}
