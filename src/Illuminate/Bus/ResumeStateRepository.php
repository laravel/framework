<?php

namespace Illuminate\Bus;

use Illuminate\Bus\Workflow\ResumeState;

interface ResumeStateRepository
{
    public function getResumeState(string $id): ?ResumeState;

    /**
     * @param  string  $id
     * @param  ResumeState  $resumeState
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return void
     */
    public function saveCheckpoint(string $id, $resumeState, $ttl): void;

    public function clearResumeState(string $id): void;
}
