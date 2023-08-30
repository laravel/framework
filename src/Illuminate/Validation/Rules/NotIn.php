<?php

namespace Illuminate\Validation\Rules;

use BackedEnum;
use UnitEnum;

class NotIn
{
    /**
     * The name of the rule.
     *
     * @var string
     */
    protected $rule = 'not_in';

    /**
     * The accepted values.
     *
     * @var array
     */
    protected $values;

    /**
     * Create a new "not in" rule instance.
     *
     * @param  array  $values
     * @return void
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        $values = array_map(function ($value) {
            $value = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof UnitEnum => $value->name,
                default => $value,
            };

            return '"'.str_replace('"', '""', $value).'"';
        }, $this->values);

        return $this->rule.':'.implode(',', $values);
    }
}
