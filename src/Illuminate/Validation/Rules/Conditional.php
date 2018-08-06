<?php

namespace Illuminate\Validation\Rules;

class Conditional
{
    /**
     * @var bool|\Closure
     */
    protected $condition;

    /**
     * @var array
     */
    protected $passRules = [];

    /**
     * @var array
     */
    protected $failRules = [];

    /**
     * Create a new conditional rule instance.
     *
     * @param  bool|\Closure  $condition
     * @return void
     */
    public function __construct($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @param  mixed ...$rules
     *
     * @return $this
     */
    public function passes(...$rules)
    {
        if (is_array($rules[0])) {
            $rules = $rules[0];
        }

        $this->passRules = $rules;

        return $this;
    }

    /**
     * @param  mixed ...$rules
     *
     * @return $this
     */
    public function fails(...$rules)
    {
        if (is_array($rules[0])) {
            $rules = $rules[0];
        }

        $this->failRules = $rules;

        return $this;
    }

    /**
     * Get the rules that should be applied.
     *
     * @return array
     */
    public function getRules()
    {
        return value($this->condition) ? $this->passRules : $this->failRules;
    }
}
