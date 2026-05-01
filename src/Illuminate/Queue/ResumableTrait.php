<?php

namespace Illuminate\Queue;

use Illuminate\Bus\JobSequence\ExecutionState;
use Illuminate\Bus\JobSequence\JobSequence;

trait ResumableTrait
{
    protected ?ExecutionState $resumeState;

    protected JobSequence $jobSequence;

    public function setResumeState(?ExecutionState $resumeState): static
    {
        $this->resumeState = $resumeState;

        return $this;
    }

    public function resumeStateKey(): string
    {
        return 'workflow:'.$this->job->getJobId();
    }

    public function setJobSequence(JobSequence $jobSequence): static
    {
        $this->jobSequence = $jobSequence;

        return $this;
    }

    public function getJobSequence(): JobSequence
    {
        return $this->jobSequence;
    }

    public function getResumeStateTtl()
    {
        // @todo
        return 500;
    }
}
