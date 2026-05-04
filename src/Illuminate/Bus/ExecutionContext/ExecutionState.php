<?php

namespace Illuminate\Bus\ExecutionContext;

class ExecutionState
{
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

    public function all(): array
    {
        return $this->data;
    }

    public function options(): array
    {
        return $this->options;
    }

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
