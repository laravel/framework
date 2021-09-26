<?php

namespace Illuminate\Support\EnvProcessors;

use RuntimeException;

final class IntegerProcessor implements EnvProcessorInterface
{
    /**
     * Converts the env value to its integer representation.
     *
     * @param  string  $value
     * @return int
     *
     * @throws \RuntimeException
     */
    public function __invoke(string $value): int
    {
        if (false === $value = filter_var($value, \FILTER_VALIDATE_INT) ?: filter_var($value, \FILTER_VALIDATE_FLOAT)) {
            throw new RuntimeException('Non-numeric env var cannot be cast to int.');
        }

        return (int) $value;
    }
}
