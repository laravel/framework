<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Queue\Job;

interface ResumeStateRepository
{
    public function getResumeState(Job $job): array;

    public function saveCheckpoint($job, $checkpoint, $data): void;

    public function clearResumeState($job): void;
}
