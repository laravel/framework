<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class MissingIf implements ParameterizedRule
{
    use FormatsParameters;

    protected string $anotherField;

    protected array $values;

    /**
     * Create a new missing_if rule instance.
     */
    public function __construct(string $anotherField, string|int|float|bool|null|array $values)
    {
        $this->anotherField = $anotherField;
        $this->values = is_array($values) ? $values : array_slice(func_get_args(), 1);
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        $values = array_map(static fn ($value) => match (true) {
            is_null($value) => 'null',
            $value === true => 'true',
            $value === false => 'false',
            default => (string) $value,
        }, $this->values);

        return ['missing_if', $this->anotherField, ...array_values($values)];
    }
}
