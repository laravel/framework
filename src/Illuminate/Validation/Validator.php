<?php

namespace Illuminate\Validation;

use RuntimeException;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\ClosureRule;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Symfony\Component\Translation\TranslatorInterface;

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
     * The message bag instance.
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $messages;

    /**
     * Validator raw data (both normal data and files).
     *
     * @var array
     */
    protected $rawData;

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
     * Map attributes as {implicit} => [{explicit1}, {explicit2}]
     * For example `foo.*` => [`foo.bar`, `foo.baz`].
     *
     * @var array
     */
    protected $attributesMap;

    /**
     * Map attributes as {explicit} => {implicit}
     * For example 'foo.bar' => 'foo.*',.
     *
     * @var array
     */
    protected $attributesExplicitMap;

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    protected $ruleSets;

    /**
     * Rules available for this validator.
     *
     * @var array
     */
    protected $availableRules = [
        'accepted' => Rules\AcceptedRule::class,
        'active_url' => Rules\ActiveUrlRule::class,
        'alpha_dash' => Rules\AlphaDashRule::class,
        'alpha_num' => Rules\AlphaNumRule::class,
        'alpha' => Rules\AlphaRule::class,
        'after' => Rules\AfterRule::class,
        'array' => Rules\ArrayRule::class,
        'bail' => Rules\BailRule::class,
        'before' => Rules\BeforeRule::class,
        'between' => Rules\BetweenRule::class,
        'boolean' => Rules\BooleanRule::class,
        'confirmed' => Rules\ConfirmedRule::class,
        'date_format' => Rules\DateFormatRule::class,
        'date' => Rules\DateRule::class,
        'different' => Rules\DifferentRule::class,
        'digits_between' => Rules\DigitsBetweenRule::class,
        'digits' => Rules\DigitsRule::class,
        'dimensions' => Rules\DimensionsRule::class,
        'distinct' => Rules\DistinctRule::class,
        'email' => Rules\EmailRule::class,
        'file' => Rules\FileRule::class,
        'filled' => Rules\FilledRule::class,
        'image' => Rules\ImageRule::class,
        'in_array' => Rules\InArrayRule::class,
        'in' => Rules\InRule::class,
        'integer' => Rules\IntegerRule::class,
        'ip' => Rules\IpRule::class,
        'json' => Rules\JsonRule::class,
        'max' => Rules\MaxRule::class,
        'mimes' => Rules\MimesRule::class,
        'mimetypes' => Rules\MimetypesRule::class,
        'min' => Rules\MinRule::class,
        'not_in' => Rules\NotInRule::class,
        'nullable' => Rules\NullableRule::class,
        'numeric' => Rules\NumericRule::class,
        'present' => Rules\PresentRule::class,
        'regex' => Rules\RegexRule::class,
        'required' => Rules\RequiredRule::class,
        'same' => Rules\SameRule::class,
        'size' => Rules\SizeRule::class,
        'sometimes' => Rules\SometimesRule::class,
        'string' => Rules\StringRule::class,
        'timezone' => Rules\TimezoneRule::class,
        'url' => Rules\UrlRule::class,
    ];

    /**
     * The size related validation rules.
     *
     * @var array
     */
    protected $sizeRules = ['size', 'between', 'min', 'max'];

    /**
     * The numeric related validation rules.
     *
     * @var array
     */
    protected $numericRules = ['numeric', 'integer'];

    /**
     * The failed validation rules.
     *
     * @var array
     */
    protected $failedRules = [];

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
        $this->rawData = $this->parseData($data);
        $this->data = $this->hydrateFiles($this->rawData);

        $this->setRuleSets($rules);
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

        return $this;
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
     * Get data under validation.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get files under validation.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Gets initial, parsed data (both files and standard data).
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Creates rule instance from given rule name.
     *
     * @param  string $key rule name in available rules list
     * @return \Illuminate\Validation\Rules|null rule instance or null
     */
    protected function createRuleInstance($key)
    {
        if (class_exists($key) && array_key_exists(Rule::class, class_parents($key))) {
            $rule = $key;
        } else {
            $rule = $this->getAvailableRule($key);

            if (is_null($rule)) {
                throw new InvalidArgumentException("Could not find rule {$key} in validators list");
            }

            if (is_array($rule) && count($rule) === 2) {
                list($rule, $implicit) = $rule;

                if (is_string($rule)) {
                    if (Str::contains($rule, '@')) {
                        $callback = explode('@', $rule);
                    } else {
                        $callback = [$rule, 'validate'];
                    }
                } else {
                    $callback = $rule;
                }

                $rule = ClosureRule::class;
            }
        }

        // $instance = $this->container->make($rule);
        $instance = app()->make($rule);

        if ($instance instanceof ClosureRule) {
            $instance->setClosure($callback);
            $instance->setImplicit($implicit);
        }

        return $instance;
    }

    /**
     * Parses indexed array into associative array.
     *
     * @param  Rule $rule Rule instance
     * @param  array $parameters Parsed or unparsed parameters (only indexed array will be parsed)
     * @return array parsed parameters
     */
    protected function parseParameters(Rule $rule, array $parameters)
    {
        if (Arr::isAssoc($parameters)) {
            return $parameters;
        }

        if ($rule->allowNamedParameters()) {
            $parameters = $this->parseNamedParameters($parameters);
        }

        if (count($parameters) < $rule->getRequiredParametersCount()) {
            $ruleKey = array_search(get_class($rule), $this->availableRules);

            throw new InvalidArgumentException("Failed to parse parameters. Rule {$ruleKey} requires {$rule->getRequiredParametersCount()} parameters");
        }

        return Arr::isAssoc($parameters) ? $parameters : $rule->mapParameters($parameters);
    }

    /**
     * Parse named parameters (`{key}={value}`) to $key => $value items array.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function parseNamedParameters($parameters)
    {
        foreach ($parameters as $param) {
            if (! Str::contains($param, '=')) {
                return $parameters;
            }
        }

        $result = array_reduce($parameters, function ($result, $item) {
            dimp($result, $item);
            list($key, $value) = array_pad(explode('=', $item, 2), 2, null);

            $result[$key] = $value;

            return $result;
        });

        if (is_null($result)) {
            return [];
        }
    }

    /**
     * Validates given rule.
     *
     * @param  string|\Illuminate\Validation\Rule $rule rule to be validated
     * @param  string $attribute attribute that is under validation
     * @param  array $parameters array of parameters
     * @return bool wheter rule passes or not
     */
    public function validateRule($rule, $attribute, array $parameters = [])
    {
        if (! $rule instanceof Rule) {
            $rule = $this->createRuleInstance($rule);
        }

        $value = $this->getValue($attribute);
        $parameters = $this->parseParameters($rule, $parameters);

        return $rule->passes($attribute, $value, $parameters, $this);
    }

    /**
     * Searches for rule definition in available rules list.
     *
     * @param  string $rule rule to be matched
     * @return string|array string with classname or array containing closure and implicit flag
     */
    public function getAvailableRule($rule)
    {
        if (array_key_exists($rule, $this->availableRules)) {
            return $this->availableRules[$rule];
        }

        return;
    }

    /**
     * Get the value of a given attribute.
     *
     * @param  string  $attribute
     * @return mixed
     */
    public function getValue($attribute)
    {
        if (! is_null($value = Arr::get($this->data, $attribute))) {
            return $value;
        } elseif (! is_null($value = Arr::get($this->files, $attribute))) {
            return $value;
        }
    }

    /**
     * Parse the data array.
     *
     * @param  array  $data
     * @return array
     */
    protected function parseData(array $data)
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);
            }

            if (Str::contains($key, '.')) {
                $newData[str_replace('.', '->', $key)] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
    }

    /**
     * Hydrate the files array.
     *
     * @param  array   $data
     * @param  string  $arrayKey
     * @return array
     */
    protected function hydrateFiles(array $data, $arrayKey = null)
    {
        if (is_null($arrayKey)) {
            $this->files = [];
        }

        foreach ($data as $key => $value) {
            $key = $arrayKey ? "$arrayKey.$key" : $key;

            // If this value is an instance of the HttpFoundation File class we will
            // remove it from the data array and add it to the files array, which
            // we use to conveniently separate out these files from other data.
            if ($value instanceof File) {
                $this->files[$key] = $value;

                unset($data[$key]);
            } elseif (is_array($value)) {
                $this->hydrateFiles($value, $key);
            }
        }

        return $data;
    }

    public function setRuleSets(array $rules)
    {
        $this->ruleSets = [];
        $this->attributesMap = [];

        foreach ($rules as $attribute => $ruleSet) {
            $attribute = str_replace('\.', '->', $attribute);

            $this->ruleSets[$attribute] = new RulesCollection($ruleSet);
            $this->attributesMap[$attribute] = $this->getExplicitAttributes($attribute, $this->rawData);

            foreach ($this->attributesMap[$attribute] as $explicit) {
                $this->attributesExplicitMap[$explicit] = $attribute;
            }
        }
    }

    /**
     * @param  string $attribute
     * @param  array $data
     * @return array
     */
    public function getExplicitAttributes($attribute, array $data = [])
    {
        // Prepare search
        $search = implode("\n", array_keys(Arr::dot($data)));

        // Prepare regex rule
        $regex = '/^'.str_replace('\*', '[^\.\n]+', preg_quote($attribute)).'(?:\.|$)/im';

        // Match rule
        preg_match_all($regex, $search, $matches);

        // Use collection for more fluent array parsing
        $matches = new Collection($matches[0]);
        $matches = $matches->map(function ($value) {
            return rtrim($value, '.');
        });

        return $matches->unique()->toArray();
    }

    /**
     * Get attribute matching another attribute
     * For example `foo.1.bar.2`, `foo.*.baz.*` => `foo.1.baz.2`.
     * @param  string $attribute Explicit attribute
     * @param  string $match     Implicit attribute
     * @return string
     */
    public function getMatchingAttribute($attribute, $match)
    {
        $keys = $this->getExplicitKeys($attribute);

        return $this->replaceAsterisksWithKeys($match, $keys);
    }

    public function getPrimaryAttribute($attribute)
    {
        if (array_key_exists($attribute, $this->attributesExplicitMap)) {
            return $this->attributesExplicitMap[$attribute];
        }

        return;
    }

    public function getRuleParameters($rule, $attribute)
    {
        $instance = $this->createRuleInstance($rule);
        $parameters = $this->getRules($attribute)->get($rule);

        return $this->parseParameters($instance, $parameters);
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
        $attribute = $this->getPrimaryAttribute($attribute);

        if (is_null($attribute) || ! Str::contains($attribute, '*')) {
            return [];
        }

        $implicit = str_replace('\*', '([^\.]+)', preg_quote($attribute));

        if (preg_match('/^'.$implicit.'$/', $attribute, $keys)) {
            array_shift($keys);

            return $keys;
        }

        return [];
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
     * Stop on error if "bail" rule is assigned and attribute has a message.
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function shouldStopValidating($attribute)
    {
        if (! $this->getRules($attribute)->has('bail')) {
            return false;
        }

        return $this->messages->has($attribute);
    }

    /**
     * Determine if the attribute is validatable.
     *
     * @param  \Illuminate\Validation\Rule  $rule
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function isValidatable(Rule $rule, $attribute, $value)
    {
        return (
            $this->presentOrIsImplicit($rule, $attribute, $value) &&
            $this->passesOptionalCheck($attribute) &&
            $this->isNotNullIfMarkedAsNullable($attribute, $value) &&
            $this->hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute)
        );
    }

    /**
     * Determine if the field is present, or the rule implies required.
     *
     * @param  \Illuminate\Validation\Rule  $rule
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function presentOrIsImplicit(Rule $rule, $attribute, $value)
    {
        if (is_string($value) && trim($value) == '') {
            return $rule->isImplicit();
        }

        return $this->validateRule('present', $attribute) || $rule->isImplicit();
    }

    /**
     * Determine if the attribute passes any optional check.
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function passesOptionalCheck($attribute)
    {
        if ($this->getRules($attribute)->has('sometimes')) {
            return array_key_exists($attribute, Arr::dot($this->data))
                || in_array($attribute, array_keys($this->data))
                || array_key_exists($attribute, $this->files);
        }

        return true;
    }

    /**
     * Determine if the attribute fails the nullable check.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    protected function isNotNullIfMarkedAsNullable($attribute, $value)
    {
        if (! $this->getRules($attribute)->has('nullable')) {
            return true;
        }

        return ! is_null($value);
    }

    /**
     * Determine if it's a necessary presence validation.
     *
     * This is to avoid possible database type comparison errors.
     *
     * @param  \Illuminate\Validation\Rule  $rule
     * @param  string  $attribute
     * @return bool
     */
    protected function hasNotFailedPreviousRuleIfPresenceRule(Rule $rule, $attribute)
    {
        $keys = [
            $this->getAvailableRule('unique'),
            $this->getAvailableRule('exists'),
        ];

        return in_array(get_class($rule), $keys) ? ! $this->messages->has($attribute) : true;
    }

    /**
     * Get RulesCollection for given attribute or all rules.
     *
     * @param  string|null $attribute
     * @return RulesCollection|array
     */
    public function getRules($attribute = null)
    {
        if ($attribute === null) {
            return $this->ruleSets;
        }

        $attribute = $this->getPrimaryAttribute($attribute);

        if (array_key_exists($attribute, $this->ruleSets)) {
            return $this->ruleSets[$attribute];
        }

        // Return empty collection set
        return new RulesCollection([]);
    }

    /**
     * Get list of numeric rules.
     *
     * @return array
     */
    public function getNumericRules()
    {
        return $this->numericRules;
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

    public function fails()
    {
        return ! $this->passes();
    }

    protected function getAttributeType($attribute)
    {
        $rules = $this->getRules($attribute);

        if ($rules->has($this->numericRules)) {
            return 'numeric';
        } elseif ($rules->has('array')) {
            return 'array';
        } elseif (array_key_exists($attribute, $this->files)) {
            return 'file';
        }

        return 'string';
    }

    protected function addError($rule, $attribute, array $parameters)
    {
        $parameters = $parameters + [
            'attribute' => $attribute,
            'Attribute' => ucfirst($attribute),
            'ATTRIBUTE' => strtoupper($attribute),
        ];

        $this->messages->add($attribute, $rule);
    }

    protected function addFailure($rule, $attribute, array $parameters)
    {
        $this->addError($rule, $attribute, $parameters);

        $this->failedRules[$attribute][$rule] = $parameters;
    }

    public function addExtensions()
    {
    }

    public function addImplicitExtensions()
    {
    }

    public function setFallbackMessages()
    {
    }

    public function failed()
    {
        return $this->failedRules;
    }

    public function sometimes($attribute, $rules, callable $callback)
    {
    }

    public function after($callback)
    {
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
     * Determine if the data passes the validation rules.
     *
     * @return bool
     */
    public function passes()
    {
        $this->messages = new MessageBag;

        foreach ($this->getRules() as $implicit => $ruleSet) {
            $attributes = $this->attributesMap[$implicit];

            foreach ($attributes as $attribute) {
                $rules = $ruleSet->get();

                foreach ($rules as $ruleKey => $parameters) {
                    $rule = $this->createRuleInstance($ruleKey);
                    $validatable = $this->isValidatable($rule, $attribute, $this->getValue($attribute));

                    if ($validatable && ! $this->validateRule($rule, $attribute, $parameters)) {
                        $this->addFailure($ruleKey, $attribute, $parameters);
                    }

                    if ($this->shouldStopValidating($attribute)) {
                        break;
                    }
                }
            }
        }

        return $this->messages->isEmpty();
    }

    /**
     * Run the validator's rules against its data.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate()
    {
        if ($this->fails()) {
            throw new ValidationException($this);
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

        return array_diff_key(
            $this->data, $this->attributesThatHaveMessages()
        );
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

        return array_intersect_key(
            $this->data, $this->attributesThatHaveMessages()
        );
    }

    /**
     * Generate an array of all attributes that have messages.
     *
     * @return array
     */
    protected function attributesThatHaveMessages()
    {
        $results = [];

        foreach ($this->messages()->toArray() as $key => $message) {
            $results[] = explode('.', $key)[0];
        }

        return array_flip(array_unique($results));
    }
}
