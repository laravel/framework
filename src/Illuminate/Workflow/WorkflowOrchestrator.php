<?php

namespace Illuminate\Workflow;

class WorkflowOrchestrator {
    public function __construct(protected Workflow $workflow)
    {
    }

    public function handle()
    {
        // while not done

    }
}
