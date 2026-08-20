<?php

namespace Illuminate\Concurrency;

use ReflectionClass;
use RuntimeException;
use Throwable;

class TaskResult
{
    /**
     * Create a result envelope for a task that completed successfully.
     */
    public static function success(mixed $value): array
    {
        return [
            'successful' => true,
            'result' => serialize($value),
        ];
    }

    /**
     * Create a result envelope for a task that threw the given exception.
     */
    public static function failure(Throwable $e): array
    {
        $reflection = new ReflectionClass($e);
        $constructor = $reflection->getConstructor();
        $parameters = [];

        if ($constructor && $constructor->getDeclaringClass()->getName() === $reflection->getName()) {
            foreach ($constructor->getParameters() as $parameter) {
                $parameters[$parameter->name] = $e->{$parameter->name} ?? null;
            }
        }

        return [
            'successful' => false,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'parameters' => $parameters,
        ];
    }

    /**
     * Unwrap the given result envelope, returning the task's value or throwing its exception.
     *
     * @throws \Throwable
     */
    public static function unwrap(array $result): mixed
    {
        if (! $result['successful']) {
            throw static::toException($result);
        }

        return unserialize($result['result']);
    }

    /**
     * Reconstruct the failed envelope's exception, degrading gracefully when its constructor cannot be satisfied.
     */
    protected static function toException(array $result): Throwable
    {
        $parameters = $result['parameters'] ?? [];

        try {
            return new $result['exception'](
                ...(! empty(array_filter($parameters, fn ($parameter) => ! is_null($parameter)))
                    ? $parameters
                    : [$result['message']])
            );
        } catch (Throwable) {
            //
        }

        try {
            return new $result['exception']($result['message']);
        } catch (Throwable) {
            return new RuntimeException($result['exception'].': '.$result['message']);
        }
    }
}
