<?php

namespace Illuminate\Support\EnvProcessors;

final class StringProcessor implements EnvProcessorInterface
{
    /**
     * Converts the env value to its string representation.
     *
     * @param  string  $value
     * @return string
     */
    public function __invoke(string $value): string
    {
        if ('null' === $value) {
            return '';
        }

        return $value;
    }
}
