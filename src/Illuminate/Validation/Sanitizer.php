<?php

namespace Illuminate\Validation;

use Illuminate\Container\Container;

class Sanitizer
{
    /**
     * The registered global sanitizers.
     *
     * @var array
     */
    protected static $sanitizers = [];

    /**
     * Sanitizer bound to the current instance.
     *
     * @var array
     */
    protected $instanceSanitizers = [];

    /**
     * The registered sanitizer aliases.
     *
     * @var array
     */
    protected static $aliases = [];

    /**
     * The data we are sanitizing.
     *
     * @var array
     */
    protected $inputData = [];

    /**
     * The sanitizing rules to apply to the data.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Sanitizer constructor.
     *
     * @param $inputData
     * @param $rules
     */
    public function __construct(array $inputData = [], array $rules = [])
    {
        $this->inputData = $inputData;
        $this->rules = $rules;
    }

    /**
     * Sanitize the data.
     *
     * @return array
     */
    public function sanitize()
    {
        $results = [];

        foreach ($this->rules as $inputKey => $rule) {
            if (! array_key_exists($this->inputData, $inputKey)) {
                continue;
            }

            $results[$inputKey] = $this->applySanitizer($rule, $this->inputData[$inputKey]);
        }
    }

    /**
     * Register a new global sanitizer.
     *
     * @param $name
     * @param \Closure $sanitizer
     */
    public static function add($name, \Closure $sanitizer)
    {
        static::$sanitizers[$name] = $sanitizer;
    }

    /**
     * Create a string alias for a sanitizer.
     *
     * @param $alias
     * @param $actual
     */
    public static function alias($alias, $actual)
    {
        $callable = explode('@', $actual);
        $callable = array_pad($callable, - (count($callable) - 2), 'sanitize');

        if (! class_exists($callable[0])) {
            throw new \InvalidArgumentException("Sanitizer class does not exist");
        }

        if(! method_exists($callable[0], $callable[1])) {
            throw new \InvalidArgumentException("Sanitizer method {$callable[1]} does not exist");
        }

        static::$aliases[$alias] = implode('@', $callable);
    }

    /**
     * Apply a sanitizer to certain data.
     *
     * @param $sanitizer
     * @param $data
     * @return mixed
     */
    protected function applySanitizer($sanitizer, $data)
    {
        if (function_exists($sanitizer) || array_key_exists($sanitizer, static::$sanitizers)) {
            return $sanitizer($data);
        }

        if (array_key_exists($sanitizer, static::$aliases)) {
            $sanitizer = static::$aliases[$sanitizer];
        }

        return Container::getInstance()->call($sanitizer);
    }
}
