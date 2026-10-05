<?php

namespace Illuminate\Validation\Rules;

use Stringable;

class AcceptedIf implements Stringable
{
    protected string $anotherField;

    /**
     * @var array<int, string|int|float|bool|null>
     */
    protected array $values;

    public function __construct(string $anotherField, string|int|float|bool|null|array $values)
    {
        $this->anotherField = $anotherField;
        $this->values = is_array($values) ? $values : array_slice(func_get_args(), 1);
    }

    public function __toString(): string
    {
        $values = array_map(
            static fn ($value) => is_string($value) ? $value : json_encode($value)),
            $this->values,
        );

        return 'accepted_if:'.$this->anotherField.','.implode(',', $values);
    }
}
