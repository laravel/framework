<?php

namespace Illuminate\Bus\Events;

use Illuminate\Bus\ExecutionContext\ExecutionState;

class StepCompleted
{
    public function __construct(
        public ExecutionState $state,
        public string $step,
        public mixed $result,
        public int $completedAt,
    ) {
    }
}
