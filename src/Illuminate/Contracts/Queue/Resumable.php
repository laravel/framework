<?php

namespace Illuminate\Contracts\Queue;

use Illuminate\Bus\JobSequence\ExecutionStateOG;
use Illuminate\Bus\JobSequence\JobSequence;

interface Resumable
{
    public function resumeStateKey(): string;

    public function setSequence(JobSequence $jobSequence): static;

    /**
     * @return \DateTimeInterface|\DateInterval|int|null
     */
    public function getResumeStateTtl();

    public function getJobSequence(): JobSequence;
}
