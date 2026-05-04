<?php

namespace Illuminate\Bus\Events;

use Illuminate\Bus\ExecutionContext\ExecutionState;
use Throwable;

class StepFailed
{
    public function __construct(
        public ExecutionState $state,
        public string $step,
        public Throwable $exception,
    ) {
    }
}
