<?php

namespace Illuminate\Validation\Rules;

use Closure;

class RequiredIf
{
    /**
     * The name of the rule.
     */
    protected $rule = 'required';

    /**
     * The condition that validates the attribute.
     *
     * @var bool|\Closure
     */
    public $condition;

    /**
     * Create a new Closure based required validation rule.
     *
     * @param  bool|\Closure  $condition
     * @return void
     */
    public function __construct($condition)
    {
        $this->condition = $condition;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->condition instanceof Closure) {
            return $this->condition->__invoke() ? $this->rule : '';
        }

        return $this->condition ? $this->rule : '';
    }
}
