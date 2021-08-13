<?php

namespace Illuminate\Validation;

class MergeRules
{
    /**
     * The boolean condition indicating if the rules should be merged.
     *
     * @var callable|bool
     */
    protected $condition;

    /**
     * The rules to be added to be merged.
     *
     * @var callable|array
     */
    protected $rules;

    /**
     * Create a new conditional rules instance.
     *
     * @param  callable|bool  $condition
     * @param  callable|array  $rules
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
                    ? call_user_func($this->condition, $data)
                    : $this->condition;
    }

    /**
     * Get the rules.
     *
     * @return array
     */
    public function rules()
    {
        return is_callable($this->rules)
                    ? call_user_func($this->rules)
                    : $this->rules;
    }
}
