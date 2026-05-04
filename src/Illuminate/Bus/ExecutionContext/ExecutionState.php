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
        protected mixed $ttl = null,
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

    public function ttl(): mixed
    {
        return $this->ttl;
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
            'id' => $this->serializeValue($this->id),
            'data' => $this->serializeValue($this->data),
            'ttl' => $this->serializeValue($this->ttl),
        ];
    }

    public function __unserialize(array $values): void
    {
        $this->id = $this->restoreValue($values['id']);
        $this->data = $this->restoreValue($values['data']);
        $this->ttl = $this->restoreValue($values['ttl'] ?? null);
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
