<?php

namespace Illuminate\Validation;

class ConditionalRules
{
    /**
     * The boolean condition indicating if the rules should be added to the attribute.
     *
     * @var bool
     */
    protected $condition;

    /**
     * The rules to be added to the attribute.
     *
     * @var array
     */
    protected $rules;

    /**
     * Create a new conditional rules instance.
     *
     * @param  bool  $condition
     * @param  array|string  $rules
     * @return void
     */
    public function __construct($condition, $rules)
    {
        $this->condition = $condition;
        $this->rules = $rules;
    }

    /**
     * Determine if the conditional rules should be added.
     *
     * @return bool
     */
    public function passes()
    {
        return $this->condition;
    }

    /**
     * Get the rules.
     *
     * @return array
     */
    public function rules()
    {
        return is_string($this->rules) ? explode('|', $this->rules) : $this->rules;
    }
}
