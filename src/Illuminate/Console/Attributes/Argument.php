<?php

namespace Illuminate\Console\Attributes;

use Attribute;
use InvalidArgumentException;
use Illuminate\Console\Command;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Argument extends Input
{
    /**
     * @param \Illuminate\Console\Command $command
     *
     * @throws \InvalidArgumentException when neither an option nor an argument
     *                                   with give key exists and no default value was given 
     */
    protected function getInput(Command $command, string $parameter)
    {
        return $command->argument($parameter);
    }
}
