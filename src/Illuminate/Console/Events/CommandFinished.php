<?php

namespace Illuminate\Console\Events;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandFinished
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
     * The command exit code.
     *
     * @var int
     */
    public $exitCode;

    /**
     * The number of milliseconds it took to execute the command.
     *
     * @var float
     */
    public $time;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  int  $exitCode
     * @param  float  $time
     * @return void
     */
    public function __construct($command, InputInterface $input, OutputInterface $output, $exitCode, $time)
    {
        $this->input = $input;
        $this->output = $output;
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->time = $time;
    }
}
