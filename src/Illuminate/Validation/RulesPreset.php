<?php

namespace Illuminate\Validation;

use BadMethodCallException;
use Illuminate\Support\Str;

abstract class RulesPreset
{
    protected $preset;

    public static function make()
    {
        return app()->make(static::class);
    }

    public function rules($overrides = [])
    {
        $rules = $this->{$this->preset}();

        foreach ($overrides as $field => $fieldRules) {
            $rules[$field] = array_merge(
                isset($rules[$field]) ? $this->normalizeRules($rules[$field]) : [],
                $this->normalizeRules($fieldRules)
            );
        }

        return $rules;
    }

    protected function normalizeRules($rules)
    {
        return is_array($rules) ? $rules : [$rules];
    }

    /**
     * Selects a preset.
     *
     * @param $preset
     *
     * @return $this
     */
    public function preset($preset)
    {
        $presetMethod = Str::camel("preset-{$preset}");

        if (! method_exists($this, $presetMethod)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $presetMethod
            ));
        }

        $this->preset = $presetMethod;

        return $this;
    }

    public static function __callStatic($method, $parameters)
    {
        return static::make()->preset($method);
    }
}
