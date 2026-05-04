<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Illuminate\Support\Collection;

class ExecutionState
{
    use SerializesAndRestoresModelIdentifiers;

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
        return ($this->data[$name]['completed_at'] ?? false) !== false;
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

    public function __serialize(): array
    {
        return [
            'id' => $this->serializeValue($this->id),
            'data' => $this->serializeValue($this->data),
        ];
    }

    public function __unserialize(array $values): void
    {
        $this->id = $this->restoreValue($values['id']);
        $this->data = $this->restoreValue($values['data']);
    }

    protected function serializeValue(mixed $value): mixed
    {
        $value = $this->getSerializedPropertyValue($value);

        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                $value[$key] = $this->serializeValue($nestedValue);
            }

            return $value;
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($nestedValue) => $this->serializeValue($nestedValue));
        }

        return $value;
    }

    protected function restoreValue(mixed $value): mixed
    {
        $value = $this->getRestoredPropertyValue($value);

        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                $value[$key] = $this->restoreValue($nestedValue);
            }

            return $value;
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($nestedValue) => $this->restoreValue($nestedValue));
        }

        return $value;
    }
}
