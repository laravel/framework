<?php

namespace Illuminate\Contracts\Workflow;

interface ExecutionRepository
{
    /**
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState|null
     */
    public function find(string $id);

    /**
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState
     */
    public function create(mixed $id);

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState  $state
     */
    public function saveStep($state, string $name): void;

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     */
    public function delete($id): void;
}
