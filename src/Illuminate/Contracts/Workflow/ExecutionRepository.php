<?php

namespace Illuminate\Contracts\Workflow;

interface ExecutionRepository
{
    /**
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState|null
     */
    public function find(mixed $id);

    /**
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState
     */
    public function create(mixed $id, $ttl = null);

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState  $state
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function saveStep($state, string $name, $ttl = null): void;

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     */
    public function delete($id): void;
}
