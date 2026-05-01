<?php

namespace Illuminate\Bus\JobSequence;

class ExecutionState
{
    public int $stepIndex = 0;

    public array $data = [];
}
