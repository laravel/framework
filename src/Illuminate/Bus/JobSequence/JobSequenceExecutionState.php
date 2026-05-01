<?php

namespace Illuminate\Bus\JobSequence;

use Illuminate\Bus\JobSequence\ExecutionState;

class JobSequenceExecutionState extends ExecutionState
{
    public int $stepIndex = 0;
}
