<?php

namespace Illuminate\Bus\JobSequence;

class JobSequenceExecutionState extends ExecutionState
{
    public int $stepIndex = 0;
}
