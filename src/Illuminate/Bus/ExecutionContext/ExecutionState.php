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

    public function hasCompletedStep(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function resultFor(string $name): mixed
    {
        return $this->data[$name]->result;
    }

    public function recordStepResult(ExecutionStepResult $result): void
    {
        $this->data[$result->name] = $result;
    }

    public function forgetStep(string $name): void
    {
        unset($this->data[$name]);
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data,
            'options' => $this->options,
        ];
    }

    public function __unserialize(array $values): void
    {
        $this->id = $values['id'];
        $this->data = $values['data'];
        $this->options = $values['options'] ?? [];
    }
}
