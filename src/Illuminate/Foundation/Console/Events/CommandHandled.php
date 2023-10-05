<?php

namespace Illuminate\Foundation\Console\Events;

class CommandHandled
{
    /**
     * Create a new event instance.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $output
     * @param  int  $output
     */
    public function __construct(public $input, public $output, public $exitCode)
    {
        //
    }
}
