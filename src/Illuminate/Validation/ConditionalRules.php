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
     * @var array|string|\Closure
     */
    protected $rules;

    /**
     * The rules to be added to the attribute if the condition fails.
     *
     * @var array|string|\Closure
     */
    protected $defaultRules;

    /**
     * Create a new conditional rules instance.
     *
     * @param  callable|bool  $condition
     * @param  array|string|\Closure  $rules
     * @param  array|string|\Closure  $defaultRules
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
     * @param  array  $data
     * @return array
     */
    public function rules(array $data = [])
    {
        return is_string($this->rules)
                    ? explode('|', $this->rules)
                    : value($this->rules, new Fluent($data));
    }

    /**
     * Get the default rules.
     *
     * @param  array  $data
     * @return array
     */
    public function defaultRules(array $data = [])
    {
        return is_string($this->defaultRules)
                    ? explode('|', $this->defaultRules)
                    : value($this->defaultRules, new Fluent($data));
    }
}
