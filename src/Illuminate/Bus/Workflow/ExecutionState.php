<?php

namespace Illuminate\Bus\Workflow;

class ExecutionState
{
    public int $stepIndex = 0;

    public array $data = [];
}
