<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class PresentIf implements Stringable
{
    protected string $anotherField;

    protected array $values;

    public function __construct(string $anotherField, string|int|float|bool|null|array $values)
    {
        $this->anotherField = $anotherField;
        $this->values = is_array($values) ? $values : array_slice(func_get_args(), 1);
    }

    public function __toString(): string
    {
        $values = array_map(static fn ($value) => match (true) {
            is_null($value) => 'null',
            $value === true => 'true',
            $value === false => 'false',
            default => (string) $value,
        }, $this->values);

        return 'present_if:'.$this->anotherField.','.implode(',', $values);
    }
}
