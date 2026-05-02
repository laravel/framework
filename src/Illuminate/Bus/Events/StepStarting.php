<?php

namespace Illuminate\Bus\Events;

use Illuminate\Bus\ExecutionContext\ExecutionState;

class StepStarting
{
    public function __construct(
        public ExecutionState $state,
        public string $step,
    ) {
    }
}
