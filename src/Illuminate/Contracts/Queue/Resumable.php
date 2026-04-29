<?php

namespace Illuminate\Contracts\Queue;

use Illuminate\Bus\Workflow\ResumeState;
use Illuminate\Bus\Workflow\Workflow;

interface Resumable
{
    public function setResumeState(?ResumeState $resumeState): static;

    public function resumeStateKey(): string;

    public function setWorkflow(Workflow $workflow): static;

    /**
     * @return \DateTimeInterface|\DateInterval|int|null
     */
    public function getResumeStateTtl();

    public function getWorkflow(): Workflow;
}
