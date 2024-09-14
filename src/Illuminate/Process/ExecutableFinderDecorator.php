<?php

namespace Illuminate\Process;

class ExecutableFinderDecorator
{
    public function __construct(protected ExecutableFinder $decorator)
    {
        //
    }
}
