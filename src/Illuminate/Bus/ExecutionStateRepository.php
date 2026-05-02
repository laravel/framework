<?php

namespace Illuminate\Bus;

use Illuminate\Bus\JobSequence\ExecutionStateOG;

interface ExecutionStateRepository
{
    public function getExecutionState(string $id): ?ExecutionStateOG;

    /**
     * @param  string  $id
     * @param  ExecutionStateOG  $executionState
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return void
     */
    public function saveExecutionState(string $id, $executionState, $ttl): void;

    public function clearExecutionState(string $id): void;
}
