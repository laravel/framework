<?php

namespace Illuminate\Queue;

use Illuminate\Bus\JobSequence\ExecutionState;
use Illuminate\Bus\JobSequence\JobSequence;

trait ResumableTrait
{
    protected ?ExecutionState $resumeState;

    protected JobSequence $sequence;

    public function setExecutionState(?ExecutionState $resumeState): static
    {
        $this->resumeState = $resumeState;

        return $this;
    }

    public function resumeStateKey(): string
    {
        return 'resumable_job_execution_state:'.$this->job->getJobId();
    }

    public function setSequence(JobSequence $sequence): static
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getJobSequence(): JobSequence
    {
        return $this->sequence;
    }

    public function getResumeStateTtl()
    {
        // @todo
        return 500;
    }
}
