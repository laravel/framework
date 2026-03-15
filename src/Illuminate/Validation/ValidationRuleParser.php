<?php

namespace Illuminate\Validation;

use Closure;
use Illuminate\Contracts\Validation\CompilableRules;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Numeric;
use Illuminate\Validation\Rules\StringRule;
use Illuminate\Validation\Rules\Unique;

class ValidationRuleParser
{
    /**
     * The data being validated.
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
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Parse the human-friendly rules into a full rules array for the validator.
     *
     * @param  array  $rules
     * @return \stdClass
     */
    public function explode($rules)
    {
        $this->implicitAttributes = [];

        $rules = $this->explodeRules($rules);

        return (object) [
            'rules' => $rules,
            'implicitAttributes' => $this->implicitAttributes,
        ];
    }

    /**
     * Explode the rules into an array of explicit rules.
     *
     * @param  array  $rules
     * @return array
     */
    protected function explodeRules($rules)
    {
        foreach ($rules as $key => $rule) {
            if (str_contains($key, '*')) {
                $rules = $this->explodeWildcardRules($rules, $key, [$rule]);

                unset($rules[$key]);
            } else {
                $rules[$key] = $this->explodeExplicitRule($rule, $key);
            }
        }

        return $rules;
    }

    /**
     * Explode the explicit rule into an array if necessary.
     *
     * @param  mixed  $rule
     * @param  string  $attribute
     * @return array
     */
    protected function explodeExplicitRule($rule, $attribute)
    {
        if (is_string($rule)) {
            return explode('|', $rule);
        }

        if (is_object($rule)) {
            if ($rule instanceof Date || $rule instanceof Numeric || $rule instanceof StringRule) {
                return explode('|', (string) $rule);
            }

            return Arr::wrap($this->prepareRule($rule, $attribute));
        }

        $rules = [];

        foreach ($rule as $value) {
            if ($value instanceof Date || $value instanceof Numeric || $value instanceof StringRule) {
                $rules = array_merge($rules, explode('|', (string) $value));
            } else {
                $rules[] = $this->prepareRule($value, $attribute);
            }
        }

        return $rules;
    }

    /**
     * Prepare the given rule for the Validator.
     *
     * @param  mixed  $rule
     * @param  string  $attribute
     * @return mixed
     */
    protected function prepareRule($rule, $attribute)
    {
        if ($rule instanceof Closure) {
            $rule = new ClosureValidationRule($rule);
        }

        if ($rule instanceof InvokableRule || $rule instanceof ValidationRule) {
            $rule = InvokableValidationRule::make($rule);
        }

        if (! is_object($rule) ||
            $rule instanceof RuleContract ||
            ($rule instanceof Exists && $rule->queryCallbacks()) ||
            ($rule instanceof Unique && $rule->queryCallbacks())) {
            return $rule;
        }

        if ($rule instanceof CompilableRules) {
            return $rule->compile(
                $attribute, $this->data[$attribute] ?? null, Arr::dot($this->data), $this->data
            )->rules[$attribute];
        }

        return (string) $rule;
    }

    /**
     * Define a set of rules that apply to each element in an array attribute.
     *
     * @param  array  $results
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array
     */
    protected function explodeWildcardRules($results, $attribute, $rules)
    {
        $rulesList = (array) $rules;

        // CompilableRules need the flattened data context, so use the original approach.
        foreach ($rulesList as $rule) {
            if ($rule instanceof CompilableRules) {
                return $this->explodeWildcardRulesCompilable($results, $attribute, $rules);
            }
        }

        // Fast path: traverse the data structure directly to enumerate matching
        // keys instead of flattening with Arr::dot() and regex matching.
        $keys = $this->expandWildcardKeys($attribute, $this->data);

        if (empty($keys)) {
            return $results;
        }

        // Pre-explode rules once so we don't re-parse the same rule string
        // for every expanded key (e.g. 500 items × same rule string).
        $explodedRules = [];

        foreach ($rulesList as $rule) {
            if (is_string($rule)) {
                $explodedRules = array_merge($explodedRules, explode('|', $rule));
            } else {
                $explodedRules = array_merge($explodedRules, $this->explodeExplicitRule($rule, $attribute));
            }
        }

        foreach ($keys as $key) {
            // Normalize key to match PHP's array key casting (e.g. '0' → int 0)
            // so that strict comparisons in implicitAttributes work correctly.
            $key = ((string) (int) $key === $key) ? (int) $key : $key;

            $this->implicitAttributes[$attribute][] = $key;

            $results[$key] = array_merge($results[$key] ?? [], $explodedRules);
        }

        return $results;
    }

    /**
     * Explode wildcard rules using the original flatten and regex approach.
     *
     * Used for CompilableRules which need the flattened data context.
     *
     * @param  array  $results
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array
     */
    protected function explodeWildcardRulesCompilable($results, $attribute, $rules)
    {
        $keys = $this->expandWildcardKeys($attribute, $this->data);

        if (empty($keys)) {
            return $results;
        }

        // Compute flattened data once for CompilableRules that need it.
        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        foreach ($keys as $key) {
            $key = ((string) (int) $key === $key) ? (int) $key : $key;

            foreach ((array) $rules as $rule) {
                if ($rule instanceof CompilableRules) {
                    $value = Arr::get($this->data, $key);
                    $context = Arr::get($this->data, Str::beforeLast((string) $key, '.'));

                    $compiled = $rule->compile($key, $value, $data, $context);

                    $this->implicitAttributes = array_merge_recursive(
                        $compiled->implicitAttributes,
                        $this->implicitAttributes,
                        [$attribute => [$key]]
                    );

                    $results = $this->mergeRules($results, $compiled->rules);
                } else {
                    $this->implicitAttributes[$attribute][] = $key;

                    $results = $this->mergeRules($results, $key, $rule);
                }
            }
        }

        return $results;
    }

    /**
     * Expand a wildcard attribute into all matching concrete keys by
     * traversing the data structure directly.
     *
     * @param  string  $attribute
     * @param  array  $data
     * @return array
     */
    protected function expandWildcardKeys($attribute, $data)
    {
        $segments = explode('.', $attribute);
        $results = [];

        $this->traverseWildcardSegments($segments, 0, $data, '', $results);

        return $results;
    }

    /**
     * Recursively traverse data segments to expand wildcard keys.
     *
     * @param  array  $segments
     * @param  int  $index
     * @param  mixed  $data
     * @param  string  $prefix
     * @param  array  $results
     * @return void
     */
    protected function traverseWildcardSegments($segments, $index, $data, $prefix, &$results)
    {
        if ($index >= count($segments)) {
            $results[] = rtrim($prefix, '.');

            return;
        }

        $segment = $segments[$index];

        if ($segment === '*') {
            if (! is_array($data)) {
                return;
            }

            foreach ($data as $key => $value) {
                $this->traverseWildcardSegments($segments, $index + 1, $value, $prefix.$key.'.', $results);
            }

            return;
        }

        $nextData = is_array($data) && array_key_exists($segment, $data) ? $data[$segment] : null;

        $this->traverseWildcardSegments($segments, $index + 1, $nextData, $prefix.$segment.'.', $results);
    }

    /**
     * Merge additional rules into a given attribute(s).
     *
     * @param  array  $results
     * @param  string|array  $attribute
     * @param  string|array  $rules
     * @return array
     */
    public function mergeRules($results, $attribute, $rules = [])
    {
        if (is_array($attribute)) {
            foreach ((array) $attribute as $innerAttribute => $innerRules) {
                $results = $this->mergeRulesForAttribute($results, $innerAttribute, $innerRules);
            }

            return $results;
        }

        return $this->mergeRulesForAttribute(
            $results, $attribute, $rules
        );
    }

    /**
     * Merge additional rules into a given attribute.
     *
     * @param  array  $results
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array
     */
    protected function mergeRulesForAttribute($results, $attribute, $rules)
    {
        $merge = head($this->explodeRules([$rules]));

        $results[$attribute] = array_merge(
            isset($results[$attribute]) ? $this->explodeExplicitRule($results[$attribute], $attribute) : [], $merge
        );

        return $results;
    }

    /**
     * Extract the rule name and parameters from a rule.
     *
     * @param  array|string  $rule
     * @return array
     */
    public static function parse($rule)
    {
        if ($rule instanceof RuleContract || $rule instanceof CompilableRules) {
            return [$rule, []];
        }

        if (is_array($rule)) {
            $rule = static::parseArrayRule($rule);
        } else {
            $rule = static::parseStringRule($rule);
        }

        $rule[0] = static::normalizeRule($rule[0]);

        return $rule;
    }

    /**
     * Parse an array based rule.
     *
     * @param  array  $rule
     * @return array
     */
    protected static function parseArrayRule(array $rule)
    {
        return [Str::studly(trim(Arr::get($rule, 0, ''))), array_slice($rule, 1)];
    }

    /**
     * Parse a string based rule.
     *
     * @param  string  $rule
     * @return array
     */
    protected static function parseStringRule($rule)
    {
        $parameters = [];

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        if (str_contains($rule, ':')) {
            [$rule, $parameter] = explode(':', $rule, 2);

            $parameters = static::parseParameters($rule, $parameter);
        }

        return [Str::studly(trim($rule)), $parameters];
    }

    /**
     * Parse a parameter list.
     *
     * @param  string  $rule
     * @param  string  $parameter
     * @return array
     */
    protected static function parseParameters($rule, $parameter)
    {
        return static::ruleIsRegex($rule) ? [$parameter] : str_getcsv($parameter, escape: '\\');
    }

    /**
     * Determine if the rule is a regular expression.
     *
     * @param  string  $rule
     * @return bool
     */
    protected static function ruleIsRegex($rule)
    {
        return in_array(strtolower($rule), ['regex', 'not_regex', 'notregex'], true);
    }

    /**
     * Normalizes a rule so that we can accept short types.
     *
     * @param  string  $rule
     * @return string
     */
    protected static function normalizeRule($rule)
    {
        return match ($rule) {
            'Int' => 'Integer',
            'Bool' => 'Boolean',
            default => $rule,
        };
    }

    /**
     * Expand the conditional rules in the given array of rules.
     *
     * @param  array  $rules
     * @param  array  $data
     * @return array
     */
    public static function filterConditionalRules($rules, array $data = [])
    {
        return (new Collection($rules))->mapWithKeys(function ($attributeRules, $attribute) use ($data) {
            if (! is_array($attributeRules) &&
                ! $attributeRules instanceof ConditionalRules) {
                return [$attribute => $attributeRules];
            }

            if ($attributeRules instanceof ConditionalRules) {
                return [$attribute => $attributeRules->passes($data)
                    ? array_filter($attributeRules->rules($data))
                    : array_filter($attributeRules->defaultRules($data)), ];
            }

            return [$attribute => (new Collection($attributeRules))->map(function ($rule) use ($data) {
                if (! $rule instanceof ConditionalRules) {
                    return [$rule];
                }

                return $rule->passes($data) ? $rule->rules($data) : $rule->defaultRules($data);
            })->filter()->flatten(1)->values()->all()];
        })->all();
    }
}
