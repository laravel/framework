<?php

namespace Illuminate\Bus;

use Illuminate\Bus\JobSequence\ExecutionState;

interface ExecutionStateRepository
{
    public function getExecutionState(string $id): ?ExecutionState;

    /**
     * @param  string  $id
     * @param  ExecutionState  $executionState
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return void
     */
    public function saveExecutionState(string $id, $executionState, $ttl): void;

    public function clearExecutionState(string $id): void;
}
