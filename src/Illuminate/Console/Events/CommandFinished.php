<?php

namespace Illuminate\Console\Events;

use Symfony\Component\Console\Input\InputInterface;

class CommandFinished
{
    /**
     * The command name.
     *
     * @var string
     */
    public $command;

    /**
     * The console input.
     *
     * @var string
     */
    public $input;

    /**
     * The command exit code.
     *
     * @var int
     */
    public $exitCode;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  int  $exitCode
     * @return void
     */
    public function __construct($command, InputInterface $input, $exitCode)
    {
        $this->command = $command;
        $this->input = $input;
        $this->exitCode = $exitCode;
    }
}
