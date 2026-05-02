<?php

namespace Illuminate\Bus\ExecutionContext;

class ExecutionState
{
    public function __construct(
        protected mixed $id,
        protected array $data = [],
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

    public function hasCompletedStep(string $name): bool
    {
        return array_key_exists($name, $this->data) && $this->data[$name]['completed_at'] !== false;
    }

    public function resultFor(string $name): mixed
    {
        return $this->data[$name]['result'];
    }

    public function recordStepResult(string $name, mixed $result, $completedAt): void
    {
        $this->data[$name] = [
            'completed_at' => $completedAt,
            'result' => $result,
        ];
    }
}
