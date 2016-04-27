<?php

namespace Illuminate\Validation;

use Closure;
use DateTime;
use Countable;
use Exception;
use DateTimeZone;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use BadMethodCallException;
use InvalidArgumentException;
use Illuminate\Support\Fluent;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class Validator implements ValidatorContract
{
    /**
     * The Translator implementation.
     *
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * The Presence Verifier implementation.
     *
     * @var \Illuminate\Validation\PresenceVerifierInterface
     */
    protected $presenceVerifier;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The failed validation rules.
     *
     * @var array
     */
    protected $failedRules = [];

    /**
     * The message bag instance.
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $messages;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The files under validation.
     *
     * @var array
     */
    protected $files = [];

    /**
     * The initial rules provided.
     *
     * @var array
     */
    protected $initialRules;

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    protected $rules;

    /**
     * The array of wildcard attributes with their asterisks expanded.
     *
     * @var array
     */
    protected $implicitAttributes = [];

    /**
     * All of the registered "after" callbacks.
     *
     * @var array
     */
    protected $after = [];

    /**
     * The array of custom error messages.
     *
     * @var array
     */
    protected $customMessages = [];

    /**
     * The array of fallback error messages.
     *
     * @var array
     */
    protected $fallbackMessages = [];

    /**
     * The array of custom attribute names.
     *
     * @var array
     */
    protected $customAttributes = [];

    /**
     * The array of custom displayable values.
     *
     * @var array
     */
    protected $customValues = [];

    /**
     * All of the custom validator extensions.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * All of the custom replacer extensions.
     *
     * @var array
     */
    protected $replacers = [];

    /**
     * The size related validation rules.
     *
     * @var array
     */
    protected $sizeRules = ['Size', 'Between', 'Min', 'Max'];

    /**
     * The numeric related validation rules.
     *
     * @var array
     */
    protected $numericRules = ['Numeric', 'Integer'];

    /**
     * The validation rules that imply the field is required.
     *
     * @var array
     */
    protected $implicitRules = [
        'Required', 'Filled', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
        'RequiredIf', 'RequiredUnless', 'Accepted', 'Present',
        // 'Array', 'Boolean', 'Integer', 'Numeric', 'String',
    ];

    /**
     * The validation rules which depend on other fields as parameters.
     *
     * @var array
     */
    protected $dependentRules = [
        'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
        'RequiredIf', 'RequiredUnless', 'Confirmed', 'Same', 'Different', 'Unique',
        'Before', 'After',
    ];

    /**
     * Create a new Validator instance.
     *
     * @param  \Symfony\Component\Translation\TranslatorInterface  $translator
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $this->translator = $translator;
        $this->customMessages = $messages;
        $this->data = $this->parseData($data);
        $this->customAttributes = $customAttributes;
        $this->initialRules = $rules;

        $this->setRules($rules);
    }

    /**
     * Parse the data and hydrate the files array.
     *
     * @param  array   $data
     * @param  string  $arrayKey
     * @return array
     */
    protected function parseData(array $data, $arrayKey = null)
    {
        if (is_null($arrayKey)) {
            $this->files = [];
        }

        foreach ($data as $key => $value) {
            $key = ($arrayKey) ? "$arrayKey.$key" : $key;

            // If this value is an instance of the HttpFoundation File class we will
            // remove it from the data array and add it to the files array, which
            // we use to conveniently separate out these files from other data.
            if ($value instanceof File) {
                $this->files[$key] = $value;

                unset($data[$key]);
            } elseif (is_array($value)) {
                $this->parseData($value, $key);
            }
        }

        return $data;
    }

    /**
     * Explode the rules into an array of rules.
     *
     * @param  string|array  $rules
     * @return array
     */
    protected function explodeRules($rules)
    {
        foreach ($rules as $key => $rule) {
            if (Str::contains($key, '*')) {
                $this->each($key, [$rule]);

                unset($rules[$key]);
            } else {
                $rules[$key] = (is_string($rule)) ? explode('|', $rule) : $rule;
            }
        }

        return $rules;
    }

    /**
     * After an after validation callback.
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback)
    {
        $this->after[] = function () use ($callback) {
            return call_user_func_array($callback, [$this]);
        };

        return $this;
    }

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return void
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        $payload = new Fluent($this->attributes());

        if (call_user_func($callback, $payload)) {
            foreach ((array) $attribute as $key) {
                if (Str::contains($key, '*')) {
                    $this->explodeRules([$key => $rules]);
                } else {
                    $this->mergeRules($key, $rules);
                }
            }
        }
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
    public function each($attribute, $rules)
    {
        $data = Arr::dot($this->initializeAttributeOnData($attribute));

        $pattern = str_replace('\*', '[^\.]+', preg_quote($attribute));

        $data = array_merge($data, $this->extractValuesForWildcards(
            $data, $attribute
        ));

        foreach ($data as $key => $value) {
            if (Str::startsWith($key, $attribute) || (bool) preg_match('/^'.$pattern.'\z/', $key)) {
                foreach ((array) $rules as $ruleKey => $ruleValue) {
                    if (! is_string($ruleKey) || Str::endsWith($key, $ruleKey)) {
                        $this->implicitAttributes[$attribute][] = $key;

                        $this->mergeRules($key, $ruleValue);
                    }
                }
            }
        }
    }

    /**
     * Gather a copy of the attribute data filled with any missing attributes.
     *
     * @param  string  $attribute
     * @return array
     */
    protected function initializeAttributeOnData($attribute)
    {
        $explicitPath = $this->getLeadingExplicitAttributePath($attribute);

        $data = $this->extractDataFromPath($explicitPath);

        if (! Str::contains($attribute, '*') || Str::endsWith($attribute, '*')) {
            return $data;
        }

        return data_set($data, $attribute, null, true);
    }

    /**
     * Get all of the exact attribute values for a given wildcard attribute.
     *
     * @param  array  $data
     * @param  string  $attribute
     * @return array
     */
    public function extractValuesForWildcards($data, $attribute)
    {
        $keys = [];

        $pattern = str_replace('\*', '[^\.]+', preg_quote($attribute));

        foreach ($data as $key => $value) {
            if ((bool) preg_match('/^'.$pattern.'/', $key, $matches)) {
                $keys[] = $matches[0];
            }
        }

        $keys = array_unique($keys);

        $data = [];

        foreach ($keys as $key) {
            $data[$key] = array_get($this->data, $key);
        }

        return $data;
    }

    /**
     * Merge additional rules into a given attribute(s).
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return $this
     */
    public function mergeRules($attribute, $rules = [])
    {
        if (is_array($attribute)) {
            foreach ($attribute as $innerAttribute => $innerRules) {
                $this->mergeRulesForAttribute($innerAttribute, $innerRules);
            }

            return $this;
        }

        return $this->mergeRulesForAttribute($attribute, $rules);
    }

    /**
     * Merge additional rules into a given attribute.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return $this
     */
    protected function mergeRulesForAttribute($attribute, $rules)
    {
        $current = isset($this->rules[$attribute]) ? $this->rules[$attribute] : [];

        $merge = head($this->explodeRules([$rules]));

        $this->rules[$attribute] = array_merge($current, $merge);

        return $this;
    }

    /**
     * Determine if the data passes the validation rules.
     *
     * @return bool
     */
    public function passes()
    {
        $this->messages = new MessageBag;

        // We'll spin through each rule, validating the attributes attached to that
        // rule. Any error messages will be added to the containers with each of
        // the other error messages, returning true if we don't have messages.
        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                $this->validate($attribute, $rule);

                if ($this->shouldStopValidating($attribute)) {
                    break;
                }
            }
        }

        // Here we will spin through all of the "after" hooks on this validator and
        // fire them off. This gives the callbacks a chance to perform all kinds
        // of other validation that needs to get wrapped up in this operation.
        foreach ($this->after as $after) {
            call_user_func($after);
        }

        return $this->messages->isEmpty();
    }

    /**
     * Determine if the data fails the validation rules.
     *
     * @return bool
     */
    public function fails()
    {
        return ! $this->passes();
    }

    /**
     * Validate a given attribute against a rule.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return void
     */
    protected function validate($attribute, $rule)
    {
        list($rule, $parameters) = $this->parseRule($rule);

        if ($rule == '') {
            return;
        }

        // First we will get the correct keys for the given attribute in case the field is nested in
        // an array. Then we determine if the given rule accepts other field names as parameters.
        // If so, we will replace any asterisks found in the parameters with the correct keys.
        if (($keys = $this->getExplicitKeys($attribute)) &&
            $this->dependsOnOtherFields($rule)) {
            $parameters = $this->replaceAsterisksInParameters($parameters, $keys);
        }

        // We will get the value for the given attribute from the array of data and then
        // verify that the attribute is indeed validatable. Unless the rule implies
        // that the attribute is required, rules are not run for missing values.
        $value = $this->getValue($attribute);

        $validatable = $this->isValidatable($rule, $attribute, $value);

        $method = "validate{$rule}";

        if ($validatable && ! $this->$method($attribute, $value, $parameters, $this)) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    /**
     * Returns the data which was valid.
     *
     * @return array
     */
    public function valid()
    {
        if (! $this->messages) {
            $this->passes();
        }

        return array_diff_key($this->data, $this->messages()->toArray());
    }

    /**
     * Returns the data which was invalid.
     *
     * @return array
     */
    public function invalid()
    {
        if (! $this->messages) {
            $this->passes();
        }

        return array_intersect_key($this->data, $this->messages()->toArray());
    }

    /**
     * Get the value of a given attribute.
     *
     * @param  string  $attribute
     * @return mixed
     */
    protected function getValue($attribute)
    {
        if (! is_null($value = Arr::get($this->data, $attribute))) {
            return $value;
        } elseif (! is_null($value = Arr::get($this->files, $attribute))) {
            return $value;
        }
    }

    /**
     * Determine if the attribute is validatable.
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function isValidatable($rule, $attribute, $value)
    {
        return $this->presentOrRuleIsImplicit($rule, $attribute, $value) &&
               $this->passesOptionalCheck($attribute) &&
               $this->hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute);
    }

    /**
     * Determine if the field is present, or the rule implies required.
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function presentOrRuleIsImplicit($rule, $attribute, $value)
    {
        return $this->validateRequired($attribute, $value) || $this->isImplicit($rule);
    }

    /**
     * Determine if the attribute passes any optional check.
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function passesOptionalCheck($attribute)
    {
        if ($this->hasRule($attribute, ['Sometimes'])) {
            return array_key_exists($attribute, Arr::dot($this->data))
                || in_array($attribute, array_keys($this->data))
                || array_key_exists($attribute, $this->files);
        }

        return true;
    }

    /**
     * Determine if a given rule implies the attribute is required.
     *
     * @param  string  $rule
     * @return bool
     */
    protected function isImplicit($rule)
    {
        return in_array($rule, $this->implicitRules);
    }

    /**
     * Determine if it's a necessary presence validation.
     *
     * This is to avoid possible database type comparison errors.
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @return bool
     */
    protected function hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute)
    {
        return in_array($rule, ['Unique', 'Exists'])
                        ? ! $this->messages->has($attribute) : true;
    }

    /**
     * Add a failed rule and error message to the collection.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return void
     */
    protected function addFailure($attribute, $rule, $parameters)
    {
        $this->addError($attribute, $rule, $parameters);

        $this->failedRules[$attribute][$rule] = $parameters;
    }

    /**
     * Add an error message to the validator's collection of messages.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return void
     */
    protected function addError($attribute, $rule, $parameters)
    {
        $message = $this->getMessage($attribute, $rule);

        $message = $this->doReplacements($message, $attribute, $rule, $parameters);

        $this->messages->add($attribute, $message);
    }

    /**
     * "Validate" optional attributes.
     *
     * Always returns true, just lets us put sometimes in rules.
     *
     * @return bool
     */
    protected function validateSometimes()
    {
        return true;
    }

    /**
     * "Break" on first validation fail.
     *
     * Always returns true, just lets us put "bail" in rules.
     *
     * @return bool
     */
    protected function validateBail()
    {
        return true;
    }

    /**
     * Stop on error if "bail" rule is assigned and attribute has a message.
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function shouldStopValidating($attribute)
    {
        if (! $this->hasRule($attribute, ['Bail'])) {
            return false;
        }

        return $this->messages->has($attribute);
    }

    /**
     * Validate that a required attribute exists.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateRequired($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        } elseif ($value instanceof File) {
            return (string) $value->getPath() != '';
        }

        return true;
    }

    /**
     * Validate that an attribute exists even if not filled.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validatePresent($attribute, $value)
    {
        return Arr::has($this->data, $attribute);
    }

    /**
     * Validate the given attribute is filled if it is present.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateFilled($attribute, $value)
    {
        if (Arr::has(array_merge($this->data, $this->files), $attribute)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Determine if any of the given attributes fail the required test.
     *
     * @param  array  $attributes
     * @return bool
     */
    protected function anyFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if (! $this->validateRequired($key, $this->getValue($key))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if all of the given attributes fail the required test.
     *
     * @param  array  $attributes
     * @return bool
     */
    protected function allFailingRequired(array $attributes)
    {
        foreach ($attributes as $key) {
            if ($this->validateRequired($key, $this->getValue($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that an attribute exists when any other attribute exists.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWith($attribute, $value, $parameters)
    {
        if (! $this->allFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes exists.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWithAll($attribute, $value, $parameters)
    {
        if (! $this->anyFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWithout($attribute, $value, $parameters)
    {
        if ($this->anyFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when all other attributes do not.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredWithoutAll($attribute, $value, $parameters)
    {
        if ($this->allFailingRequired($parameters)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute has a given value.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  mixed   $parameters
     * @return bool
     */
    protected function validateRequiredIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_if');

        $data = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (is_bool($data)) {
            array_walk($values, function (&$value) {
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }
            });
        }

        if (in_array($data, $values)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute does not have a given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    protected function validateRequiredUnless($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_unless');

        $data = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (! in_array($data, $values)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Get the number of attributes in a list that are present.
     *
     * @param  array  $attributes
     * @return int
     */
    protected function getPresentCount($attributes)
    {
        $count = 0;

        foreach ($attributes as $key) {
            if (Arr::get($this->data, $key) || Arr::get($this->files, $key)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Validate that the values of an attribute is in another attribute.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateInArray($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'in_array');

        $explicitPath = $this->getLeadingExplicitAttributePath($parameters[0]);

        $attributeData = $this->extractDataFromPath($explicitPath);

        $otherValues = Arr::where(Arr::dot($attributeData), function ($key) use ($parameters) {
            return Str::is($parameters[0], $key);
        });

        return in_array($value, $otherValues);
    }

    /**
     * Validate that an attribute has a matching confirmation.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateConfirmed($attribute, $value)
    {
        return $this->validateSame($attribute, $value, [$attribute.'_confirmation']);
    }

    /**
     * Validate that two attributes match.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateSame($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'same');

        $other = Arr::get($this->data, $parameters[0]);

        return isset($other) && $value === $other;
    }

    /**
     * Validate that an attribute is different from another attribute.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDifferent($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'different');

        $other = Arr::get($this->data, $parameters[0]);

        return isset($other) && $value !== $other;
    }

    /**
     * Validate that an attribute was "accepted".
     *
     * This validation rule implies the attribute is "required".
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAccepted($attribute, $value)
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];

        return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute is an array.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateArray($attribute, $value)
    {
        if (! $this->hasAttribute($attribute)) {
            return true;
        }

        return is_null($value) || is_array($value);
    }

    /**
     * Validate that an attribute is a boolean.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateBoolean($attribute, $value)
    {
        if (! $this->hasAttribute($attribute)) {
            return true;
        }

        $acceptable = [true, false, 0, 1, '0', '1'];

        return is_null($value) || in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute is an integer.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateInteger($attribute, $value)
    {
        if (! $this->hasAttribute($attribute)) {
            return true;
        }

        return is_null($value) || filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate that an attribute is numeric.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateNumeric($attribute, $value)
    {
        if (! $this->hasAttribute($attribute)) {
            return true;
        }

        return is_null($value) || is_numeric($value);
    }

    /**
     * Validate that an attribute is a string.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateString($attribute, $value)
    {
        if (! $this->hasAttribute($attribute)) {
            return true;
        }

        return is_null($value) || is_string($value);
    }

    /**
     * Validate the attribute is a valid JSON string.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateJson($attribute, $value)
    {
        if (! is_scalar($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate that an attribute has a given number of digits.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'digits');

        return $this->validateNumeric($attribute, $value)
            && strlen((string) $value) == $parameters[0];
    }

    /**
     * Validate that an attribute is between a given number of digits.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDigitsBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string) $value);

        return $this->validateNumeric($attribute, $value)
          && $length >= $parameters[0] && $length <= $parameters[1];
    }

    /**
     * Validate the size of an attribute.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateSize($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'size');

        return $this->getSize($attribute, $value) == $parameters[0];
    }

    /**
     * Validate the size of an attribute is between a set of values.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'between');

        $size = $this->getSize($attribute, $value);

        return $size >= $parameters[0] && $size <= $parameters[1];
    }

    /**
     * Validate the size of an attribute is greater than a minimum value.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateMin($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min');

        return $this->getSize($attribute, $value) >= $parameters[0];
    }

    /**
     * Validate the size of an attribute is less than a maximum value.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateMax($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max');

        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $this->getSize($attribute, $value) <= $parameters[0];
    }

    /**
     * Get the size of an attribute.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return mixed
     */
    protected function getSize($attribute, $value)
    {
        $hasNumeric = $this->hasRule($attribute, $this->numericRules);

        // This method will determine if the attribute is a number, string, or file and
        // return the proper size accordingly. If it is a number, then number itself
        // is the size. If it is a file, we take kilobytes, and for a string the
        // entire length of the string will be considered the attribute size.
        if (is_numeric($value) && $hasNumeric) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof File) {
            return $value->getSize() / 1024;
        }

        return mb_strlen($value);
    }

    /**
     * Validate an attribute is contained within a list of values.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateIn($attribute, $value, $parameters)
    {
        if (is_array($value) && $this->hasRule($attribute, 'Array')) {
            return count(array_diff($value, $parameters)) == 0;
        }

        return ! is_array($value) && in_array((string) $value, $parameters);
    }

    /**
     * Validate an attribute is not contained within a list of values.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateNotIn($attribute, $value, $parameters)
    {
        return ! $this->validateIn($attribute, $value, $parameters);
    }

    /**
     * Validate an attribute is unique among other values.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDistinct($attribute, $value, $parameters)
    {
        $attributeName = $this->getPrimaryAttribute($attribute);

        $explicitPath = $this->getLeadingExplicitAttributePath($attributeName);

        $attributeData = $this->extractDataFromPath($explicitPath);

        $data = Arr::where(Arr::dot($attributeData), function ($key) use ($attribute, $attributeName) {
            return $key != $attribute && Str::is($attributeName, $key);
        });

        return ! in_array($value, array_values($data));
    }

    /**
     * Validate the uniqueness of an attribute value on a given database table.
     *
     * If a database column is not specified, the attribute will be used.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateUnique($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'unique');

        list($connection, $table) = $this->parseTable($parameters[0]);
        // The second parameter position holds the name of the column that needs to
        // be verified as unique. If this parameter isn't specified we will just
        // assume that this column to be verified shares the attribute's name.
        $column = isset($parameters[1])
                    ? $parameters[1] : $this->guessColumnForQuery($attribute);

        list($idColumn, $id) = [null, null];

        if (isset($parameters[2])) {
            list($idColumn, $id) = $this->getUniqueIds($parameters);

            if (preg_match('/\[(.*)\]/', $id, $matches)) {
                $id = $this->getValue($matches[1]);
            }

            if (strtolower($id) == 'null') {
                $id = null;
            }
        }

        // The presence verifier is responsible for counting rows within this store
        // mechanism which might be a relational database or any other permanent
        // data store like Redis, etc. We will use it to determine uniqueness.
        $verifier = $this->getPresenceVerifier();

        $verifier->setConnection($connection);

        $extra = $this->getUniqueExtra($parameters);

        return $verifier->getCount(

            $table, $column, $value, $id, $idColumn, $extra

        ) == 0;
    }

    /**
     * Parse the connection / table for the unique / exists rules.
     *
     * @param  string  $table
     * @return array
     */
    protected function parseTable($table)
    {
        return Str::contains($table, '.') ? explode('.', $table, 2) : [null, $table];
    }

    /**
     * Get the excluded ID column and value for the unique rule.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getUniqueIds($parameters)
    {
        $idColumn = isset($parameters[3]) ? $parameters[3] : 'id';

        return [$idColumn, $parameters[2]];
    }

    /**
     * Get the extra conditions for a unique rule.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getUniqueExtra($parameters)
    {
        if (isset($parameters[4])) {
            return $this->getExtraConditions(array_slice($parameters, 4));
        }

        return [];
    }

    /**
     * Validate the existence of an attribute value in a database table.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateExists($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'exists');

        list($connection, $table) = $this->parseTable($parameters[0]);

        // The second parameter position holds the name of the column that should be
        // verified as existing. If this parameter is not specified we will guess
        // that the columns being "verified" shares the given attribute's name.
        $column = isset($parameters[1])
                    ? $parameters[1] : $this->guessColumnForQuery($attribute);

        $expected = (is_array($value)) ? count($value) : 1;

        return $this->getExistCount(
            $connection, $table, $column, $value, $parameters
        ) >= $expected;
    }

    /**
     * Get the number of records that exist in storage.
     *
     * @param  mixed   $connection
     * @param  string  $table
     * @param  string  $column
     * @param  mixed   $value
     * @param  array   $parameters
     * @return int
     */
    protected function getExistCount($connection, $table, $column, $value, $parameters)
    {
        $verifier = $this->getPresenceVerifier();

        $verifier->setConnection($connection);

        $extra = $this->getExtraExistConditions($parameters);

        if (is_array($value)) {
            return $verifier->getMultiCount($table, $column, $value, $extra);
        }

        return $verifier->getCount($table, $column, $value, null, null, $extra);
    }

    /**
     * Get the extra exist conditions.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getExtraExistConditions(array $parameters)
    {
        return $this->getExtraConditions(array_values(array_slice($parameters, 2)));
    }

    /**
     * Get the extra conditions for a unique / exists rule.
     *
     * @param  array  $segments
     * @return array
     */
    protected function getExtraConditions(array $segments)
    {
        $extra = [];

        $count = count($segments);

        for ($i = 0; $i < $count; $i = $i + 2) {
            $extra[$segments[$i]] = $segments[$i + 1];
        }

        return $extra;
    }

    /**
     * Guess the database column from the given attribute name.
     *
     * @param  string  $attribute
     * @return string
     */
    public function guessColumnForQuery($attribute)
    {
        if (in_array($attribute, array_collapse($this->implicitAttributes))
                && ! is_numeric($last = last(explode('.', $attribute)))) {
            return $last;
        }

        return $attribute;
    }

    /**
     * Validate that an attribute is a valid IP.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateIp($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate that an attribute is a valid e-mail address.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateEmail($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate that an attribute is a valid URL.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateUrl($attribute, $value)
    {
        /*
         * This pattern is derived from Symfony\Component\Validator\Constraints\UrlValidator (2.7.4).
         *
         * (c) Fabien Potencier <fabien@symfony.com> http://symfony.com
         */
        $pattern = '~^
            ((aaa|aaas|about|acap|acct|acr|adiumxtra|afp|afs|aim|apt|attachment|aw|barion|beshare|bitcoin|blob|bolo|callto|cap|chrome|chrome-extension|cid|coap|coaps|com-eventbrite-attendee|content|crid|cvs|data|dav|dict|dlna-playcontainer|dlna-playsingle|dns|dntp|dtn|dvb|ed2k|example|facetime|fax|feed|feedready|file|filesystem|finger|fish|ftp|geo|gg|git|gizmoproject|go|gopher|gtalk|h323|ham|hcp|http|https|iax|icap|icon|im|imap|info|iotdisco|ipn|ipp|ipps|irc|irc6|ircs|iris|iris.beep|iris.lwz|iris.xpc|iris.xpcs|itms|jabber|jar|jms|keyparc|lastfm|ldap|ldaps|magnet|mailserver|mailto|maps|market|message|mid|mms|modem|ms-help|ms-settings|ms-settings-airplanemode|ms-settings-bluetooth|ms-settings-camera|ms-settings-cellular|ms-settings-cloudstorage|ms-settings-emailandaccounts|ms-settings-language|ms-settings-location|ms-settings-lock|ms-settings-nfctransactions|ms-settings-notifications|ms-settings-power|ms-settings-privacy|ms-settings-proximity|ms-settings-screenrotation|ms-settings-wifi|ms-settings-workplace|msnim|msrp|msrps|mtqp|mumble|mupdate|mvn|news|nfs|ni|nih|nntp|notes|oid|opaquelocktoken|pack|palm|paparazzi|pkcs11|platform|pop|pres|prospero|proxy|psyc|query|redis|rediss|reload|res|resource|rmi|rsync|rtmfp|rtmp|rtsp|rtsps|rtspu|secondlife|service|session|sftp|sgn|shttp|sieve|sip|sips|skype|smb|sms|smtp|snews|snmp|soap.beep|soap.beeps|soldat|spotify|ssh|steam|stun|stuns|submit|svn|tag|teamspeak|tel|teliaeid|telnet|tftp|things|thismessage|tip|tn3270|turn|turns|tv|udp|unreal|urn|ut2004|vemmi|ventrilo|videotex|view-source|wais|webcal|ws|wss|wtai|wyciwyg|xcon|xcon-userid|xfire|xmlrpc\.beep|xmlrpc.beeps|xmpp|xri|ymsgr|z39\.50|z39\.50r|z39\.50s))://                                 # protocol
            (([\pL\pN-]+:)?([\pL\pN-]+)@)?          # basic auth
            (
                ([\pL\pN\pS-\.])+(\.?([\pL]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                              # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                 # a IP address
                    |                                              # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # a IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
            (/?|/\S+)                               # a /, nothing or a / with something
        $~ixu';

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate that an attribute is an active URL.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateActiveUrl($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            return count(dns_get_record($url, DNS_A | DNS_AAAA)) > 0;
        }

        return false;
    }

    /**
     * Validate the MIME type of a file is an image MIME type.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateImage($attribute, $value)
    {
        return $this->validateMimes($attribute, $value, ['jpeg', 'png', 'gif', 'bmp', 'svg']);
    }

    /**
     * Validate the guessed extension of a file upload is in a set of file extensions.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateMimes($attribute, $value, $parameters)
    {
        if (! $this->isAValidFileInstance($value)) {
            return false;
        }

        return $value->getPath() != '' && in_array($value->guessExtension(), $parameters);
    }

    /**
     * Validate the MIME type of a file upload attribute is in a set of MIME types.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    protected function validateMimetypes($attribute, $value, $parameters)
    {
        if (! $this->isAValidFileInstance($value)) {
            return false;
        }

        return $value->getPath() != '' && in_array($value->getMimeType(), $parameters);
    }

    /**
     * Check that the given value is a valid file instance.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isAValidFileInstance($value)
    {
        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $value instanceof File;
    }

    /**
     * Validate that an attribute contains only alphabetic characters.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlpha($attribute, $value)
    {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaNum($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dashes, and underscores.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaDash($attribute, $value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value);
    }

    /**
     * Validate that an attribute passes a regular expression check.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value);
    }

    /**
     * Validate that an attribute is a valid date.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateDate($attribute, $value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if ((! is_string($value) && ! is_numeric($value)) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * Validate that an attribute matches a date format.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateDateFormat($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'date_format');

        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $parsed = date_parse_from_format($parameters[0], $value);

        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }

    /**
     * Validate the date is before a given date.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateBefore($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'before');

        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        if ($format = $this->getDateFormat($attribute)) {
            return $this->validateBeforeWithFormat($format, $value, $parameters);
        }

        if (! ($date = strtotime($parameters[0]))) {
            return strtotime($value) < strtotime($this->getValue($parameters[0]));
        }

        return strtotime($value) < $date;
    }

    /**
     * Validate the date is before a given date with a given format.
     *
     * @param  string  $format
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateBeforeWithFormat($format, $value, $parameters)
    {
        $param = $this->getValue($parameters[0]) ?: $parameters[0];

        return $this->checkDateTimeOrder($format, $value, $param);
    }

    /**
     * Validate the date is after a given date.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateAfter($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'after');

        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        if ($format = $this->getDateFormat($attribute)) {
            return $this->validateAfterWithFormat($format, $value, $parameters);
        }

        if (! ($date = strtotime($parameters[0]))) {
            return strtotime($value) > strtotime($this->getValue($parameters[0]));
        }

        return strtotime($value) > $date;
    }

    /**
     * Validate the date is after a given date with a given format.
     *
     * @param  string  $format
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateAfterWithFormat($format, $value, $parameters)
    {
        $param = $this->getValue($parameters[0]) ?: $parameters[0];

        return $this->checkDateTimeOrder($format, $param, $value);
    }

    /**
     * Given two date/time strings, check that one is after the other.
     *
     * @param  string  $format
     * @param  string  $before
     * @param  string  $after
     * @return bool
     */
    protected function checkDateTimeOrder($format, $before, $after)
    {
        $before = $this->getDateTimeWithOptionalFormat($format, $before);

        $after = $this->getDateTimeWithOptionalFormat($format, $after);

        return ($before && $after) && ($after > $before);
    }

    /**
     * Get a DateTime instance from a string.
     *
     * @param  string  $format
     * @param  string  $value
     * @return \DateTime|null
     */
    protected function getDateTimeWithOptionalFormat($format, $value)
    {
        $date = DateTime::createFromFormat($format, $value);

        if ($date) {
            return $date;
        }

        try {
            return new DateTime($value);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Validate that an attribute is a valid timezone.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateTimezone($attribute, $value)
    {
        try {
            new DateTimeZone($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the date format for an attribute if it has one.
     *
     * @param  string  $attribute
     * @return string|null
     */
    protected function getDateFormat($attribute)
    {
        if ($result = $this->getRule($attribute, 'DateFormat')) {
            return $result[1][0];
        }
    }

    /**
     * Get the validation message for an attribute and rule.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getMessage($attribute, $rule)
    {
        $lowerRule = Str::snake($rule);

        $inlineMessage = $this->getInlineMessage($attribute, $lowerRule);

        // First we will retrieve the custom message for the validation rule if one
        // exists. If a custom validation message is being used we'll return the
        // custom message, otherwise we'll keep searching for a valid message.
        if (! is_null($inlineMessage)) {
            return $inlineMessage;
        }

        $customKey = "validation.custom.{$attribute}.{$lowerRule}";

        $customMessage = $this->getCustomMessageFromTranslator($customKey);

        // First we check for a custom defined validation message for the attribute
        // and rule. This allows the developer to specify specific messages for
        // only some attributes and rules that need to get specially formed.
        if ($customMessage !== $customKey) {
            return $customMessage;
        }

        // If the rule being validated is a "size" rule, we will need to gather the
        // specific error message for the type of attribute being validated such
        // as a number, file or string which all have different message types.
        elseif (in_array($rule, $this->sizeRules)) {
            return $this->getSizeMessage($attribute, $rule);
        }

        // Finally, if no developer specified messages have been set, and no other
        // special messages apply for this rule, we will just pull the default
        // messages out of the translator service for this validation rule.
        $key = "validation.{$lowerRule}";

        if ($key != ($value = $this->translator->trans($key))) {
            return $value;
        }

        return $this->getInlineMessage(
            $attribute, $lowerRule, $this->fallbackMessages
        ) ?: $key;
    }

    /**
     * Get the inline message for a rule if it exists.
     *
     * @param  string  $attribute
     * @param  string  $lowerRule
     * @param  array   $source
     * @return string|null
     */
    protected function getInlineMessage($attribute, $lowerRule, $source = null)
    {
        $source = $source ?: $this->customMessages;

        $keys = ["{$attribute}.{$lowerRule}", $lowerRule];

        // First we will check for a custom message for an attribute specific rule
        // message for the fields, then we will check for a general custom line
        // that is not attribute specific. If we find either we'll return it.
        foreach ($keys as $key) {
            foreach (array_keys($source) as $sourceKey) {
                if (Str::is($sourceKey, $key)) {
                    return $source[$sourceKey];
                }
            }
        }
    }

    /**
     * Get the custom error message from translator.
     *
     * @param  string  $customKey
     * @return string
     */
    protected function getCustomMessageFromTranslator($customKey)
    {
        if (($message = $this->translator->trans($customKey)) !== $customKey) {
            return $message;
        }

        $shortKey = preg_replace('/^validation\.custom\./', '', $customKey);

        $customMessages = Arr::dot(
            (array) $this->translator->trans('validation.custom')
        );

        foreach ($customMessages as $key => $message) {
            if (Str::contains($key, ['*']) && Str::is($key, $shortKey)) {
                return $message;
            }
        }

        return $customKey;
    }

    /**
     * Get the proper error message for an attribute and size rule.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getSizeMessage($attribute, $rule)
    {
        $lowerRule = Str::snake($rule);

        // There are three different types of size validations. The attribute may be
        // either a number, file, or string so we will check a few things to know
        // which type of value it is and return the correct line for that type.
        $type = $this->getAttributeType($attribute);

        $key = "validation.{$lowerRule}.{$type}";

        return $this->translator->trans($key);
    }

    /**
     * Get the data type of the given attribute.
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getAttributeType($attribute)
    {
        // We assume that the attributes present in the file array are files so that
        // means that if the attribute does not have a numeric rule and the files
        // list doesn't have it we'll just consider it a string by elimination.
        if ($this->hasRule($attribute, $this->numericRules)) {
            return 'numeric';
        } elseif ($this->hasRule($attribute, ['Array'])) {
            return 'array';
        } elseif (array_key_exists($attribute, $this->files)) {
            return 'file';
        }

        return 'string';
    }

    /**
     * Replace all error message place-holders with actual values.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function doReplacements($message, $attribute, $rule, $parameters)
    {
        $value = $this->getAttribute($attribute);

        $message = str_replace(
            [':ATTRIBUTE', ':Attribute', ':attribute'],
            [Str::upper($value), Str::ucfirst($value), $value],
            $message
        );

        if (isset($this->replacers[Str::snake($rule)])) {
            $message = $this->callReplacer($message, $attribute, Str::snake($rule), $parameters);
        } elseif (method_exists($this, $replacer = "replace{$rule}")) {
            $message = $this->$replacer($message, $attribute, $rule, $parameters);
        }

        return $message;
    }

    /**
     * Transform an array of attributes to their displayable form.
     *
     * @param  array  $values
     * @return array
     */
    protected function getAttributeList(array $values)
    {
        $attributes = [];

        // For each attribute in the list we will simply get its displayable form as
        // this is convenient when replacing lists of parameters like some of the
        // replacement functions do when formatting out the validation message.
        foreach ($values as $key => $value) {
            $attributes[$key] = $this->getAttribute($value);
        }

        return $attributes;
    }

    /**
     * Get the displayable name of the attribute.
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getAttribute($attribute)
    {
        $primaryAttribute = $this->getPrimaryAttribute($attribute);

        $expectedAttributes = $attribute != $primaryAttribute ? [$attribute, $primaryAttribute] : [$attribute];

        foreach ($expectedAttributes as $expectedAttributeName) {
            // The developer may dynamically specify the array of custom attributes
            // on this Validator instance. If the attribute exists in this array
            // it takes precedence over all other ways we can pull attributes.
            if (isset($this->customAttributes[$expectedAttributeName])) {
                return $this->customAttributes[$expectedAttributeName];
            }

            $key = "validation.attributes.{$expectedAttributeName}";

            // We allow for the developer to specify language lines for each of the
            // attributes allowing for more displayable counterparts of each of
            // the attributes. This provides the ability for simple formats.
            if (($line = $this->translator->trans($key)) !== $key) {
                return $line;
            }
        }

        // When no language line has been specified for the attribute and it is
        // also an implicit attribute we will display the raw attribute name
        // and not modify it with any replacements before we display this.
        if (isset($this->implicitAttributes[$primaryAttribute])) {
            return $attribute;
        }

        return str_replace('_', ' ', Str::snake($attribute));
    }

    /**
     * Get the primary attribute name.
     *
     * For example, if "name.0" is given, "name.*" will be returned.
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getPrimaryAttribute($attribute)
    {
        foreach ($this->implicitAttributes as $unparsed => $parsed) {
            if (in_array($attribute, $parsed)) {
                return $unparsed;
            }
        }

        return $attribute;
    }

    /**
     * Get the displayable name of the value.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return string
     */
    public function getDisplayableValue($attribute, $value)
    {
        if (isset($this->customValues[$attribute][$value])) {
            return $this->customValues[$attribute][$value];
        }

        $key = "validation.values.{$attribute}.{$value}";

        if (($line = $this->translator->trans($key)) !== $key) {
            return $line;
        }

        return $value;
    }

    /**
     * Replace all place-holders for the between rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceBetween($message, $attribute, $rule, $parameters)
    {
        return str_replace([':min', ':max'], $parameters, $message);
    }

    /**
     * Replace all place-holders for the date_format rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceDateFormat($message, $attribute, $rule, $parameters)
    {
        return str_replace(':format', $parameters[0], $message);
    }

    /**
     * Replace all place-holders for the different rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceDifferent($message, $attribute, $rule, $parameters)
    {
        return $this->replaceSame($message, $attribute, $rule, $parameters);
    }

    /**
     * Replace all place-holders for the digits rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceDigits($message, $attribute, $rule, $parameters)
    {
        return str_replace(':digits', $parameters[0], $message);
    }

    /**
     * Replace all place-holders for the digits (between) rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceDigitsBetween($message, $attribute, $rule, $parameters)
    {
        return $this->replaceBetween($message, $attribute, $rule, $parameters);
    }

    /**
     * Replace all place-holders for the min rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceMin($message, $attribute, $rule, $parameters)
    {
        return str_replace(':min', $parameters[0], $message);
    }

    /**
     * Replace all place-holders for the max rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceMax($message, $attribute, $rule, $parameters)
    {
        return str_replace(':max', $parameters[0], $message);
    }

    /**
     * Replace all place-holders for the in rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceIn($message, $attribute, $rule, $parameters)
    {
        foreach ($parameters as &$parameter) {
            $parameter = $this->getDisplayableValue($attribute, $parameter);
        }

        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace all place-holders for the not_in rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceNotIn($message, $attribute, $rule, $parameters)
    {
        return $this->replaceIn($message, $attribute, $rule, $parameters);
    }

    /**
     * Replace all place-holders for the in_array rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceInArray($message, $attribute, $rule, $parameters)
    {
        return str_replace(':other', $this->getAttribute($parameters[0]), $message);
    }

    /**
     * Replace all place-holders for the mimes rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceMimes($message, $attribute, $rule, $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace all place-holders for the required_with rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceRequiredWith($message, $attribute, $rule, $parameters)
    {
        $parameters = $this->getAttributeList($parameters);

        return str_replace(':values', implode(' / ', $parameters), $message);
    }

    /**
     * Replace all place-holders for the required_with_all rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceRequiredWithAll($message, $attribute, $rule, $parameters)
    {
        return $this->replaceRequiredWith($message, $attribute, $rule, $parameters);
    }

    /**
     * Replace all place-holders for the required_without rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceRequiredWithout($message, $attribute, $rule, $parameters)
    {
        return $this->replaceRequiredWith($message, $attribute, $rule, $parameters);
    }

    /**
     * Replace all place-holders for the required_without_all rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceRequiredWithoutAll($message, $attribute, $rule, $parameters)
    {
        return $this->replaceRequiredWith($message, $attribute, $rule, $parameters);
    }

    /**
     * Replace all place-holders for the size rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceSize($message, $attribute, $rule, $parameters)
    {
        return str_replace(':size', $parameters[0], $message);
    }

    /**
     * Replace all place-holders for the required_if rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceRequiredIf($message, $attribute, $rule, $parameters)
    {
        $parameters[1] = $this->getDisplayableValue($parameters[0], Arr::get($this->data, $parameters[0]));

        $parameters[0] = $this->getAttribute($parameters[0]);

        return str_replace([':other', ':value'], $parameters, $message);
    }

    /**
     * Replace all place-holders for the required_unless rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceRequiredUnless($message, $attribute, $rule, $parameters)
    {
        $other = $this->getAttribute(array_shift($parameters));

        return str_replace([':other', ':values'], [$other, implode(', ', $parameters)], $message);
    }

    /**
     * Replace all place-holders for the same rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceSame($message, $attribute, $rule, $parameters)
    {
        return str_replace(':other', $this->getAttribute($parameters[0]), $message);
    }

    /**
     * Replace all place-holders for the before rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceBefore($message, $attribute, $rule, $parameters)
    {
        if (! (strtotime($parameters[0]))) {
            return str_replace(':date', $this->getAttribute($parameters[0]), $message);
        }

        return str_replace(':date', $parameters[0], $message);
    }

    /**
     * Replace all place-holders for the after rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceAfter($message, $attribute, $rule, $parameters)
    {
        return $this->replaceBefore($message, $attribute, $rule, $parameters);
    }

    /**
     * Get all attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return array_merge($this->data, $this->files);
    }

    /**
     * Checks if an attribute exists.
     *
     * @param  string  $attribute
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return Arr::has($this->attributes(), $attribute);
    }

    /**
     * Determine if the given attribute has a rule in the given set.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return bool
     */
    protected function hasRule($attribute, $rules)
    {
        return ! is_null($this->getRule($attribute, $rules));
    }

    /**
     * Get a rule and its parameters for a given attribute.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array|null
     */
    protected function getRule($attribute, $rules)
    {
        if (! array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            list($rule, $parameters) = $this->parseRule($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }

    /**
     * Extract the rule name and parameters from a rule.
     *
     * @param  array|string  $rules
     * @return array
     */
    protected function parseRule($rules)
    {
        if (is_array($rules)) {
            $rules = $this->parseArrayRule($rules);
        } else {
            $rules = $this->parseStringRule($rules);
        }

        $rules[0] = $this->normalizeRule($rules[0]);

        return $rules;
    }

    /**
     * Parse an array based rule.
     *
     * @param  array  $rules
     * @return array
     */
    protected function parseArrayRule(array $rules)
    {
        return [Str::studly(trim(Arr::get($rules, 0))), array_slice($rules, 1)];
    }

    /**
     * Parse a string based rule.
     *
     * @param  string  $rules
     * @return array
     */
    protected function parseStringRule($rules)
    {
        $parameters = [];

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);

            $parameters = $this->parseParameters($rules, $parameter);
        }

        return [Str::studly(trim($rules)), $parameters];
    }

    /**
     * Parse a parameter list.
     *
     * @param  string  $rule
     * @param  string  $parameter
     * @return array
     */
    protected function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    /**
     * Normalizes a rule so that we can accept short types.
     *
     * @param  string  $rule
     * @return string
     */
    protected function normalizeRule($rule)
    {
        switch ($rule) {
            case 'Int':
                return 'Integer';
            case 'Bool':
                return 'Boolean';
            default:
                return $rule;
        }
    }

    /**
     * Determine if the given rule depends on other fields.
     *
     * @param  string  $rule
     * @return bool
     */
    protected function dependsOnOtherFields($rule)
    {
        return in_array($rule, $this->dependentRules);
    }

    /**
     * Get the explicit keys from an attribute flattened with dot notation.
     *
     * E.g. 'foo.1.bar.spark.baz' -> [1, 'spark'] for 'foo.*.bar.*.baz'
     *
     * @param  string  $attribute
     * @return array
     */
    protected function getExplicitKeys($attribute)
    {
        $pattern = str_replace('\*', '([^\.]+)', preg_quote($this->getPrimaryAttribute($attribute)));

        if (preg_match('/^'.$pattern.'/', $attribute, $keys)) {
            array_shift($keys);

            return $keys;
        }

        return [];
    }

    /**
     * Get the explicit part of the attribute name.
     *
     * E.g. 'foo.bar.*.baz' -> 'foo.bar'
     *
     * Allows us to not spin through all of the flattened data for some operations.
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getLeadingExplicitAttributePath($attribute)
    {
        return rtrim(explode('*', $attribute)[0], '.') ?: null;
    }

    /**
     * Extract data based on the given dot-notated path.
     *
     * Used to extract a sub-section of the data for faster iteration.
     *
     * @param  string  $attribute
     * @return array
     */
    protected function extractDataFromPath($attribute)
    {
        $results = [];

        $value = Arr::get($this->data, $attribute, '__missing__');

        if ($value != '__missing__') {
            Arr::set($results, $attribute, $value);
        }

        return $results;
    }

    /**
     * Replace each field parameter which has asterisks with the given keys.
     *
     * @param  array  $parameters
     * @param  array  $keys
     * @return array
     */
    protected function replaceAsterisksInParameters(array $parameters, array $keys)
    {
        return array_map(function ($field) use ($keys) {
            return $this->replaceAsterisksWithKeys($field, $keys);
        }, $parameters);
    }

    /**
     * Replace asterisks with explicit keys.
     *
     * E.g. 'foo.*.bar.*.baz', [1, 'spark'] -> foo.1.bar.spark.baz
     *
     * @param  string  $field
     * @param  array  $keys
     * @return string
     */
    protected function replaceAsterisksWithKeys($field, array $keys)
    {
        return vsprintf(str_replace('*', '%s', $field), $keys);
    }

    /**
     * Get the array of custom validator extensions.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Register an array of custom validator extensions.
     *
     * @param  array  $extensions
     * @return void
     */
    public function addExtensions(array $extensions)
    {
        if ($extensions) {
            $keys = array_map('\Illuminate\Support\Str::snake', array_keys($extensions));

            $extensions = array_combine($keys, array_values($extensions));
        }

        $this->extensions = array_merge($this->extensions, $extensions);
    }

    /**
     * Register an array of custom implicit validator extensions.
     *
     * @param  array  $extensions
     * @return void
     */
    public function addImplicitExtensions(array $extensions)
    {
        $this->addExtensions($extensions);

        foreach ($extensions as $rule => $extension) {
            $this->implicitRules[] = Str::studly($rule);
        }
    }

    /**
     * Register a custom validator extension.
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addExtension($rule, $extension)
    {
        $this->extensions[Str::snake($rule)] = $extension;
    }

    /**
     * Register a custom implicit validator extension.
     *
     * @param  string   $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addImplicitExtension($rule, $extension)
    {
        $this->addExtension($rule, $extension);

        $this->implicitRules[] = Str::studly($rule);
    }

    /**
     * Get the array of custom validator message replacers.
     *
     * @return array
     */
    public function getReplacers()
    {
        return $this->replacers;
    }

    /**
     * Register an array of custom validator message replacers.
     *
     * @param  array  $replacers
     * @return void
     */
    public function addReplacers(array $replacers)
    {
        if ($replacers) {
            $keys = array_map('\Illuminate\Support\Str::snake', array_keys($replacers));

            $replacers = array_combine($keys, array_values($replacers));
        }

        $this->replacers = array_merge($this->replacers, $replacers);
    }

    /**
     * Register a custom validator message replacer.
     *
     * @param  string  $rule
     * @param  \Closure|string  $replacer
     * @return void
     */
    public function addReplacer($rule, $replacer)
    {
        $this->replacers[Str::snake($rule)] = $replacer;
    }

    /**
     * Get the data under validation.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $this->parseData($data);

        $this->setRules($this->initialRules);

        return $this;
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Set the validation rules.
     *
     * @param  array  $rules
     * @return $this
     */
    public function setRules(array $rules)
    {
        $this->initialRules = $rules;

        $this->rules = [];

        $rules = $this->explodeRules($this->initialRules);

        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    /**
     * Set the custom attributes on the validator.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function setAttributeNames(array $attributes)
    {
        $this->customAttributes = $attributes;

        return $this;
    }

    /**
     * Set the custom values on the validator.
     *
     * @param  array  $values
     * @return $this
     */
    public function setValueNames(array $values)
    {
        $this->customValues = $values;

        return $this;
    }

    /**
     * Get the files under validation.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set the files under validation.
     *
     * @param  array  $files
     * @return $this
     */
    public function setFiles(array $files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get the Presence Verifier implementation.
     *
     * @return \Illuminate\Validation\PresenceVerifierInterface
     *
     * @throws \RuntimeException
     */
    public function getPresenceVerifier()
    {
        if (! isset($this->presenceVerifier)) {
            throw new RuntimeException('Presence verifier has not been set.');
        }

        return $this->presenceVerifier;
    }

    /**
     * Set the Presence Verifier implementation.
     *
     * @param  \Illuminate\Validation\PresenceVerifierInterface  $presenceVerifier
     * @return void
     */
    public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier)
    {
        $this->presenceVerifier = $presenceVerifier;
    }

    /**
     * Get the Translator implementation.
     *
     * @return \Symfony\Component\Translation\TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Set the Translator implementation.
     *
     * @param  \Symfony\Component\Translation\TranslatorInterface  $translator
     * @return void
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get the custom messages for the validator.
     *
     * @return array
     */
    public function getCustomMessages()
    {
        return $this->customMessages;
    }

    /**
     * Set the custom messages for the validator.
     *
     * @param  array  $messages
     * @return void
     */
    public function setCustomMessages(array $messages)
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
    }

    /**
     * Get the custom attributes used by the validator.
     *
     * @return array
     */
    public function getCustomAttributes()
    {
        return $this->customAttributes;
    }

    /**
     * Add custom attributes to the validator.
     *
     * @param  array  $customAttributes
     * @return $this
     */
    public function addCustomAttributes(array $customAttributes)
    {
        $this->customAttributes = array_merge($this->customAttributes, $customAttributes);

        return $this;
    }

    /**
     * Get the custom values for the validator.
     *
     * @return array
     */
    public function getCustomValues()
    {
        return $this->customValues;
    }

    /**
     * Add the custom values for the validator.
     *
     * @param  array  $customValues
     * @return $this
     */
    public function addCustomValues(array $customValues)
    {
        $this->customValues = array_merge($this->customValues, $customValues);

        return $this;
    }

    /**
     * Get the fallback messages for the validator.
     *
     * @return array
     */
    public function getFallbackMessages()
    {
        return $this->fallbackMessages;
    }

    /**
     * Set the fallback messages for the validator.
     *
     * @param  array  $messages
     * @return void
     */
    public function setFallbackMessages(array $messages)
    {
        $this->fallbackMessages = $messages;
    }

    /**
     * Get the failed validation rules.
     *
     * @return array
     */
    public function failed()
    {
        return $this->failedRules;
    }

    /**
     * Get the message container for the validator.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function messages()
    {
        if (! $this->messages) {
            $this->passes();
        }

        return $this->messages;
    }

    /**
     * An alternative more semantic shortcut to the message container.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
    {
        return $this->messages();
    }

    /**
     * Get the messages for the instance.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this->messages();
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Call a custom validator extension.
     *
     * @param  string  $rule
     * @param  array   $parameters
     * @return bool|null
     */
    protected function callExtension($rule, $parameters)
    {
        $callback = $this->extensions[$rule];

        if ($callback instanceof Closure) {
            return call_user_func_array($callback, $parameters);
        } elseif (is_string($callback)) {
            return $this->callClassBasedExtension($callback, $parameters);
        }
    }

    /**
     * Call a class based validator extension.
     *
     * @param  string  $callback
     * @param  array   $parameters
     * @return bool
     */
    protected function callClassBasedExtension($callback, $parameters)
    {
        list($class, $method) = explode('@', $callback);

        return call_user_func_array([$this->container->make($class), $method], $parameters);
    }

    /**
     * Call a custom validator message replacer.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string|null
     */
    protected function callReplacer($message, $attribute, $rule, $parameters)
    {
        $callback = $this->replacers[$rule];

        if ($callback instanceof Closure) {
            return call_user_func_array($callback, func_get_args());
        } elseif (is_string($callback)) {
            return $this->callClassBasedReplacer($callback, $message, $attribute, $rule, $parameters);
        }
    }

    /**
     * Call a class based validator message replacer.
     *
     * @param  string  $callback
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function callClassBasedReplacer($callback, $message, $attribute, $rule, $parameters)
    {
        list($class, $method) = explode('@', $callback);

        return call_user_func_array([$this->container->make($class), $method], array_slice(func_get_args(), 1));
    }

    /**
     * Require a certain number of parameters to be present.
     *
     * @param  int    $count
     * @param  array  $parameters
     * @param  string  $rule
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }

    /**
     * Handle dynamic calls to class methods.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $rule = Str::snake(substr($method, 8));

        if (isset($this->extensions[$rule])) {
            return $this->callExtension($rule, $parameters);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}
