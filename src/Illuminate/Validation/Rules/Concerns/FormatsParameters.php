<?php

namespace Illuminate\Validation\Rules\Concerns;

trait FormatsParameters
{
    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        $parameters = $this->toArray();
        $rule = array_shift($parameters);

        return $rule.($parameters ? ':'.$this->formatParameters($parameters) : '');
    }

    /**
     * Format parameters for a legacy validation rule string.
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
