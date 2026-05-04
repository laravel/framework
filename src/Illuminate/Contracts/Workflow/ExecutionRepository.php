<?php

namespace Illuminate\Contracts\Workflow;

interface ExecutionRepository
{
    /**
     * Find the ExecutionState if it exists.
     *
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState|null
     */
    public function find($id);

    /**
     * Store the ExecutionState.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState
     */
    public function create($id, $ttl = null);

    /**
     * Get the result of an individual step if it exists.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $state
     * @return \Illuminate\Bus\ExecutionContext\ExecutionStepResult|null
     */
    public function getStep($state, $step);

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $state
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionStepResult  $stepResult
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    public function saveStep($state, $stepResult, $ttl = null): void;

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     */
    public function delete($id): void;

    /**
     * Delete a single step.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  string  $steps
     * @return void
     */
    public function deleteStep($stateId, $name): void;

    /**
     * Delete many steps.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  array<array-key, string>  $steps
     * @return void
     */
    public function deleteSteps($stateId, $steps): void;
}
