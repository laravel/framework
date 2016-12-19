<?php

namespace Illuminate\Validation\Rules;

class In
{
    /**
     * The name of the rule.
     */
    protected $rule = 'in';

    /**
     * The accepted values.
     *
     * @var array
     */
    protected $values;

    /**
     * Create a new in rule instance.
     *
     * @param  array  $values
     * @param  bool  $array_keys
     * @return void
     */
    public function __construct(array $values, bool $array_keys = false)
    {
        if ($array_keys) {
            $this->values = array_keys($values);
        } else {
            $this->values = $values;
        }
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->rule . ':' . implode(',', $this->values);
    }
}
