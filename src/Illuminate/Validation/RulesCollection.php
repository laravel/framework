<?php

namespace Illuminate\Validation;

use Illuminate\Support\Str;

class RulesCollection
{
    /**
     * Rules in {rule} => {parameters} format.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param string|array $rules
     */
    public function __construct($rules)
    {
        $this->rules = [];

        $this->merge($rules);
    }

    /**
     * @param  array|string $rules
     * @return array
     */
    protected function parseRules($rules)
    {
        if (is_string($rules)) {
            return $this->parseRules(explode('|', $rules));
        }

        foreach ($rules as $rule => $params) {
            unset($rules[$rule]);

            if (is_int($rule)) {
                if (is_string($params)) {
                    list($rule, $params) = $this->parseStringRule($params);
                } elseif (is_array($params)) {
                    $rule = array_shift($params);
                    $params = $params;
                }
            }

            $rule = $this->normalizeRule($rule);

            $rules[$rule] = $params;
        }

        return $rules;
    }

    /**
     * Normalizes a rule so that we can accept short types.
     *
     * @param  string  $rule
     * @return string
     */
    protected function normalizeRule($rule)
    {
        if (class_exists($rule)) {
            return $rule;
        }

        $rule = trim(Str::snake($rule));

        switch ($rule) {
            case 'int':
                return 'integer';
            case 'bool':
                return 'boolean';
            default:
                return $rule;
        }
    }

    /**
     * @param  string $rule rule to be parsed in string format (like: `in:1,2,3`)
     * @return array array containing rule and parameters
     */
    protected function parseStringRule($rule)
    {
        $params = [];

        if (Str::contains($rule, ':')) {
            list($rule, $params) = explode(':', $rule, 2);
            $params = str_getcsv($params);
        }

        return [$rule, $params];
    }

    /**
     * Merge new rules with current ones.
     * @param  array|string $rules rules to be parsed. Either array or string format
     * @return self
     */
    public function merge($rules)
    {
        $this->rules = array_merge($this->rules, $this->parseRules($rules));

        return $this;
    }

    /**
     * Check if collection has given rule.
     *
     * @param  string  $rule rule key that has to be checked
     * @return bool wheter rule exists in collection or not
     */
    public function has($rules)
    {
        if (is_string($rules)) {
            $rules = (array) $rules;
        }

        foreach ($rules as $rule) {
            if (array_key_exists($rule, $this->rules)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get collection rules.
     *
     * @return array collection rules
     */
    public function get($rule = null)
    {
        if (is_null($rule)) {
            return $this->rules;
        }

        if (array_key_exists($rule, $this->rules)) {
            return $this->rules[$rule];
        }
    }
}
