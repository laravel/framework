<?php

namespace Illuminate\Validation;

use Closure;
use DateTime;
use Countable;
use Exception;
use Throwable;
use DateTimeZone;
use RuntimeException;
use DateTimeInterface;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Fluent;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class ValidationRuleParser
{
    /**
     * The data being validated.
     *
     * Needed in order to properly explode array rules.
     *
     * @var array
     */
    public $data;

    /**
     * The implicit attributes.
     *
     * @var array
     */
    public $implicitAttributes = [];

    /**
     * Create a new validation rule parser.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Parse the human-friendly rules into a full rules array for the validator.
     *
     * @return StdClass
     */
    public function parse($rules)
    {
        $this->implicitAttributes = [];

        $rules = $this->explodeRules($rules);

        return (object) [
            'rules' => $rules,
            'implicitAttributes' => $this->implicitAttributes
        ];
    }

    /**
     * Explode the rules into an array of rules.
     *
     * @param  array  $rules
     * @return array
     */
    protected function explodeRules($rules)
    {
        foreach ($rules as $key => $rule) {
            if (Str::contains($key, '*')) {
                $rules = $this->each($rules, $key, [$rule]);

                unset($rules[$key]);
            } else {
                if (is_string($rule)) {
                    $rules[$key] = explode('|', $rule);
                } elseif (is_object($rule)) {
                    $rules[$key] = [$rule];
                } else {
                    $rules[$key] = $rule;
                }
            }
        }

        return $rules;
    }

    /**
     * Define a set of rules that apply to each element in an array attribute.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function each($masterRules, $attribute, $rules)
    {
        $pattern = str_replace('\*', '[^\.]+', preg_quote($attribute));

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        foreach ($data as $key => $value) {
            if (Str::startsWith($key, $attribute) || (bool) preg_match('/^'.$pattern.'\z/', $key)) {
                foreach ((array) $rules as $ruleKey => $ruleValue) {
                    if (! is_string($ruleKey) || Str::endsWith($key, $ruleKey)) {
                        $this->implicitAttributes[$attribute][] = $key;

                        $masterRules = $this->mergeRules($masterRules, $key, $ruleValue);
                    }
                }
            }
        }

        return $masterRules;
    }

    /**
     * Merge additional rules into a given attribute(s).
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array
     */
    public function mergeRules($masterRules, $attribute, $rules = [])
    {
        if (is_array($attribute)) {
            foreach ($attribute as $innerAttribute => $innerRules) {
                $masterRules = $this->mergeRulesForAttribute($masterRules, $innerAttribute, $innerRules);
            }

            return $masterRules;
        }

        return $this->mergeRulesForAttribute($masterRules, $attribute, $rules);
    }

    /**
     * Merge additional rules into a given attribute.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return $this
     */
    protected function mergeRulesForAttribute($masterRules, $attribute, $rules)
    {
        $current = isset($masterRules[$attribute]) ? $masterRules[$attribute] : [];

        $merge = head($this->explodeRules([$rules]));

        $masterRules[$attribute] = array_merge($current, $merge);

        return $masterRules;
    }
}
