<?php

namespace Illuminate\Console\Attributes;

use InvalidArgumentException;
use Illuminate\Console\Command;

class Option extends Input
{
    /**
     * @param \Illuminate\Console\Command $command
     *
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     */
    private function getInput(Command $command, string $parameter)
    {
        return $command->option($parameter);
    }
}
