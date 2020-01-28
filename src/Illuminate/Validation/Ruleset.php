<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Ruleset implements Arrayable
{
    /**
     * The rules for the ruleset.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Add a rule to the ruleset.
     *
     * @param  string|object  $rule
     * @param  array  $arguments
     * @return $this
     */
    public function rule($rule, $arguments = [])
    {
        if (is_string($rule)) {
            $this->rules[] = $rule . $this->parseArguments($arguments);
        } else {
            $this->rules[] = $rule;
        }

        return $this;
    }


    /**
     * Parse the arguments given for the rule.
     *
     * @param  array  $arguments
     * @return string
     */
    protected function parseArguments($arguments = [])
    {
        $arguments = array_values($arguments);

        if (empty($arguments)) {
            return '';
        }

        if (is_array($arguments[0])) {
            return $this->parseArguments($arguments[0]);
        }

        return ':' . implode(',', $arguments);
    }

    /**
     * Return the ruleset as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->rules;
    }

    /**
     * Create a new ruleset to allow method chaining.
     *
     * @param [type] $rule
     * @param array $arguments
     * @return void
     */
    public static function make()
    {
        return new static();
    }

    /**
     * Add rules to the ruleset from method calls. For example, a call
     * to notIn($args) would be parsed as not_in:$args.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    public function __call($method, $arguments)
    {
        return $this->rule(Str::snake($method), $arguments);
    }

    /**
     * Allow rules to be added to a new rulset from static calls.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return self
     */
    public static function __callStatic($method, $arguments)
    {
        return static::make()->$method(...$arguments);
    }
}
