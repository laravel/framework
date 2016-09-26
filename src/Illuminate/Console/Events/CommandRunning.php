<?php

namespace Illuminate\Console\Events;

use Symfony\Component\Console\Input\InputInterface;

class CommandRunning
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
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    public $input;

    /**
     * Create a new event instance.
     *
     * @param  string $command
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return void
     */
    public function __construct($command, InputInterface $input)
    {
        $this->command = $command;
        $this->input = $input;
    }
}
