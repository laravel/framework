<?php

namespace Illuminate\Bus\Events;

use Illuminate\Bus\ExecutionContext\ExecutionState;
use Illuminate\Bus\ExecutionContext\ExecutionStepResult;

class StepCompleted
{
    public function __construct(
        public ExecutionState $state,
        public string $step,
        public ExecutionStepResult $result,
    ) {
    }
}
