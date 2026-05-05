<?php

namespace Illuminate\Contracts\Workflow;

interface ExecutionRepository
{
    /**
     * Find the ExecutionState if it exists.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string|null
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState|null
     */
    public function find($id);

    /**
     * Store the ExecutionState.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @param  array{ttl?: int}  $options
     * @return \Illuminate\Bus\ExecutionContext\ExecutionState
     */
    public function create($id, $options = []);

    /**
     * Get the result of an individual step if it exists.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $state
     * @return \Illuminate\Bus\ExecutionContext\ExecutionStepResult|null
     */
    public function getStep($state, $step);

    /**
     * Store the result of a single-step.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $state
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionStepResult  $stepResult
     * @param  array{ttl?: \DateTimeInterface|\DateInterval|int|null}  $options
     */
    public function saveStep($state, $stepResult, $options = []): void;

    /**
     * Delete the ExecutionState and its steps.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     */
    public function delete($id);

    /**
     * Delete a single step.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  string  $name
     * @return void
     */
    public function deleteStep($stateId, $name);

    /**
     * Delete many steps.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  array<array-key, string>  $steps
     * @return void
     */
    public function deleteSteps($stateId, $steps);
}
