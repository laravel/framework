<?php

namespace Illuminate\Contracts\Queue;

use Illuminate\Bus\Workflow\ExecutionState;
use Illuminate\Bus\Workflow\Workflow;

interface Resumable
{
    public function setResumeState(?ExecutionState $resumeState): static;

    public function resumeStateKey(): string;

    public function setWorkflow(Workflow $workflow): static;

    /**
     * @return \DateTimeInterface|\DateInterval|int|null
     */
    public function getResumeStateTtl();

    public function getWorkflow(): Workflow;
}
