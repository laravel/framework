<?php

namespace Illuminate\Queue;

use Illuminate\Bus\Workflow\ExecutionState;
use Illuminate\Bus\Workflow\Workflow;

trait ResumableTrait
{
    protected ?ExecutionState $resumeState;

    protected Workflow $workflow;

    public function setResumeState(?ExecutionState $resumeState): static
    {
        $this->resumeState = $resumeState;

        return $this;
    }

    public function resumeStateKey(): string
    {
        return 'workflow:'.$this->job->getJobId();
    }

    public function setWorkflow(Workflow $workflow): static
    {
        $this->workflow = $workflow;

        return $this;
    }

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    public function getResumeStateTtl()
    {
        return 500;
    }
}
