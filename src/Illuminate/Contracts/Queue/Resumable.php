<?php

namespace Illuminate\Contracts\Queue;

use Illuminate\Bus\Workflow\ExecutionState;
use Illuminate\Bus\Workflow\JobSequence;

interface Resumable
{
    public function resumeStateKey(): string;

    public function setJobSequence(JobSequence $jobSequence): static;

    /**
     * @return \DateTimeInterface|\DateInterval|int|null
     */
    public function getResumeStateTtl();

    public function getJobSequence(): JobSequence;
}
