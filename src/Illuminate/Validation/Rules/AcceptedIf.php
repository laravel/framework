<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rules\Concerns\FormatsParameters;
use Stringable;

class AcceptedIf implements Stringable
{
    use FormatsParameters;

    protected string $anotherField;

    /**
     * @var array<int, string|int|float|bool|null>
     */
    protected array $values;

    /**
     * Create a new accepted_if rule instance.
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
        $values = array_map(
            static fn ($value) => is_string($value) ? $value : json_encode($value),
            $this->values,
        );

        return 'accepted_if:'.$this->formatParameters([$this->anotherField, ...$values]);
    }
}
