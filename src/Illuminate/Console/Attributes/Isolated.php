<?php

namespace Illuminate\Console\Attributes;

use Attribute;
use Illuminate\Console\Command;

#[Attribute(Attribute::TARGET_CLASS)]
class Isolated
{
    /**
     * Create a new Isolated attribute instance.
     *
     * @param  int  $exitCode  The exit code when command is already running
     */
    public function __construct(
        public int $exitCode = Command::SUCCESS,
    ) {}
}
