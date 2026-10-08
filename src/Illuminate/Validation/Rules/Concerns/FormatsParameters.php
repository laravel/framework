<?php

namespace Illuminate\Validation\Rules\Concerns;

trait FormatsParameters
{
    /**
     * Format parameters for the validation rule parser.
     */
    protected function formatParameters(array $parameters): string
    {
        return implode(',', array_map(static function ($parameter) {
            $parameter = (string) $parameter;

            return str_contains($parameter, ',') || str_contains($parameter, '"')
                ? '"'.str_replace('"', '""', $parameter).'"'
                : $parameter;
        }, $parameters));
    }
}
