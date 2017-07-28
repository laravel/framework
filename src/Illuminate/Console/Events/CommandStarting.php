<?php

namespace Illuminate\Console\Events;

use Symfony\Component\Console\Input\InputInterface;

class CommandStarting
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
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return void
     */
    public function __construct($command, InputInterface $input)
    {
        $this->command = $command;
        $this->input = $input;
    }
}
