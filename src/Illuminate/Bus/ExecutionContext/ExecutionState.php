<?php

namespace Illuminate\Bus\ExecutionContext;

/**
 * @phpstan-type StateOptions array{ttl?: int}
 */
class ExecutionState
{
    /**
     * @param  mixed  $id
     * @param  array<string, ExecutionStepResult>  $data
     * @param  StateOptions  $options
     */
    public function __construct(
        protected mixed $id,
        protected array $data = [],
        protected array $options = [],
    ) {
    }

    public function id(): mixed
    {
        return $this->id;
    }

    /**
     * Get all the stored ExecutionStepResults.
     *
     * @return array<string, ExecutionStepResult>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get options set on the ExecutionState.
     *
     * @return StateOptions
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * Get a single option or return the default.
     *
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Whether the given step has been completed.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasCompletedStep($name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Get the result value for a step.
     *
     * @param  string  $name
     * @return mixed
     */
    public function resultFor($name): mixed
    {
        return $this->data[$name]->result;
    }

    /**
     * Memoize the result of a step.
     *
     * @param  ExecutionStepResult  $result
     * @return void
     */
    public function recordStepResult(ExecutionStepResult $result): void
    {
        $this->data[$result->name] = $result;
    }

    /**
     * Forget the step's result.
     *
     * @param  string  $name
     * @return void
     */
    public function forgetStep($name): void
    {
        unset($this->data[$name]);
    }

    /**
     * Clear all the underlying steps.
     *
     * @return void
     */
    public function clearSteps(): void
    {
        $this->data = [];
    }

    /**
     * Serialize the state.
     *
     * @return array{id: mixed, data: array<string, ExecutionStepResult>, options: StateOptions}
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data,
            'options' => $this->options,
        ];
    }

    /**
     * Restore the state.
     *
     * @param  array{id: mixed, data: array<string, ExecutionStepResult>, options: StateOptions}  $values
     * @return void
     */
    public function __unserialize(array $values): void
    {
        $this->id = $values['id'];
        $this->data = $values['data'];
        $this->options = $values['options'] ?? [];
    }
}
