<?php

namespace Illuminate\Bus\JobSequence;

class JobSequenceExecutionStateOG extends ExecutionStateOG
{
    public int $stepIndex = 0;
}
