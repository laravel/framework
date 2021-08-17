<?php

namespace Illuminate\Validation;

use Illuminate\Support\Fluent;

class ConditionalRules
{
    /**
     * The boolean condition indicating if the rules should be added to the attribute.
     *
     * @var callable|bool
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
     * @param  callable|bool  $condition
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
     * @param  array  $data
     * @return bool
     */
    public function passes(array $data = [])
    {
        return is_callable($this->condition)
                    ? call_user_func($this->condition, new Fluent($data))
                    : $this->condition;
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
