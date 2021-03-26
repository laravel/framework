<?php

namespace Illuminate\Console\Events;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CommandFailed
{
    /**
     * The command name.
     *
     * @var string
     */
    public $command;

    /**
     * The console input implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface|null
     */
    public $input;

    /**
     * The command output implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface|null
     */
    public $output;

    /**
     * The exception that was thrown.
     *
     * @var \Throwable
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $exception
     * @return void
     */
    public function __construct($command, InputInterface $input, OutputInterface $output, Throwable $exception)
    {
        $this->command = $command;
        $this->input = $input;
        $this->output = $output;
        $this->exception = $exception;
    }
}
