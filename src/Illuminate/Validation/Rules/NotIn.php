<?php

namespace Illuminate\Validation\Rules;

class NotIn extends Rule
{
    /**
     * The name of the rule.
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
     * The string representation of the rule.
     *
     * @return string
     */
    public function toString()
    {
        return $this->rule.':'.implode(',', $this->values);
    }
}
