<?php

namespace Illuminate\Workflow;

use Illuminate\Bus\Workflow\Workflow;

class WorkflowOrchestrator {
    public function __construct(protected Workflow $workflow)
    {
    }

    public function handle()
    {
        // while not done

    }
}
