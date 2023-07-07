<?php

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;

class ArrayRule
{
    /**
     * The name of the rule.
     *
     * @var string
     */
    protected $rule = 'array';

    /**
     * The accepted values.
     *
     * @var array|string
     */
    protected $values;

    /**
     * Create a new in rule instance.
     *
     * @param array|string|null $values
     */
    public function __construct(array|string|null $values = null)
    {
        if (is_string($values)) {
            if (! enum_exists($values) || ! method_exists($values, 'cases')) {
                throw new InvalidArgumentException('The provided condition must be an enum.');
            }
        }

        $this->values = $values;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     *
     * @see \Illuminate\Validation\ValidationRuleParser::parseParameters
     */
    public function __toString()
    {
        if ($this->values === null) {
            return $this->rule;
        }

        $values = array_map(function ($value) {
            return '"'.str_replace('"', '""', $value).'"';
        }, is_array($this->values) ? $this->values : array_column($this->values::cases(), 'value'));

        return $this->rule.':'.implode(',', $values);
    }
}
