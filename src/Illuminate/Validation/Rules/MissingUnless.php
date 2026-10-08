<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class MissingUnless implements Stringable
{
    use FormatsParameters;

    protected string $anotherField;

    protected array $values;

    /**
     * Create a new missing_unless rule instance.
     */
    public function __construct(string $anotherField, string|int|float|bool|null|array $values)
    {
        $this->anotherField = $anotherField;
        $this->values = is_array($values) ? $values : array_slice(func_get_args(), 1);
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        $values = array_map(static fn ($value) => match (true) {
            is_null($value) => 'null',
            $value === true => 'true',
            $value === false => 'false',
            default => (string) $value,
        }, $this->values);

        return 'missing_unless:'.$this->formatParameters([$this->anotherField, ...$values]);
    }
}
