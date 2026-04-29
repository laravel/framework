<?php

namespace Illuminate\Queue;

use Illuminate\Bus\Workflow\ResumeState;
use Illuminate\Bus\Workflow\Workflow;

trait ResumableTrait
{
    protected ?ResumeState $resumeState;

    protected Workflow $workflow;

    public function setResumeState(?ResumeState $resumeState): static
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
