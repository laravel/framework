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
     * @var array|string
     */
    protected $rules;

    /**
     * The rules to be added to the attribute if the condition fails.
     *
     * @var array|string
     */
    protected $defaultRules;

    /**
     * Create a new conditional rules instance.
     *
     * @param  callable|bool  $condition
     * @param  array|string  $rules
     * @param  array|string  $defaultRules
     * @return void
     */
    public function __construct($condition, $rules, $defaultRules = [])
    {
        $this->condition = $condition;
        $this->rules = $rules;
        $this->defaultRules = $defaultRules;
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

    /**
     * Get the default rules.
     *
     * @return array
     */
    public function defaultRules()
    {
        return is_string($this->defaultRules) ? explode('|', $this->defaultRules) : $this->defaultRules;
    }
}
