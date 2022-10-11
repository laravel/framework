<?php

namespace Illuminate\Console\Exceptions;

use LogicException;

class ProcessAlreadyStarted extends LogicException
{
    /**
     * Creates a new Process Exception instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('The process has already started.');
    }
}
