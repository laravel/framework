<?php

namespace Illuminate\Support\EnvProcessors;

use RuntimeException;

final class FloatProcessor implements EnvProcessorInterface
{
    /**
     * Converts the env value to its float representation.
     *
     * @param  string  $value
     * @return float
     *
     * @throws \RuntimeException
     */
    public function __invoke(string $value): float
    {
        if (false === $value = filter_var($value, \FILTER_VALIDATE_FLOAT)) {
            throw new RuntimeException('Non-numeric env var cannot be cast to float.');
        }

        return (float) $value;
    }
}
