<?php

namespace Illuminate\Console\Process;

use Symfony\Component\Process\Process;

class Response
{
    /**
     * The underlying process instance.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * Creates a new Response instance.
     *
     * @param \Symfony\Component\Process\Process
     */
    public function __construct($process)
    {
        $this->process = $process;
    }

    /**
     * Get the output of the response.
     *
     * @return string
     */
    public function output()
    {
        if ($this->process->isRunning()) {
            $this->process->wait();
        }

        return $this->process->getOutput();
    }

    /**
     * Determine if the response exit code was "OK".
     *
     * @return bool
     */
    public function ok()
    {
        if ($this->process->isRunning()) {
            $this->process->wait();
        }

        return $this->process->getExitCode() == 0;
    }
}
