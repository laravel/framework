<?php

namespace Illuminate\Validation;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FormatsMessages
{
    use ReplacesAttributes;

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
            if ($shortKey === $key || (Str::contains($key, ['*']) && Str::is($key, $shortKey))) {
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
        } elseif ($this->getValue($attribute) instanceof UploadedFile) {
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
            [':attribute', ':ATTRIBUTE', ':Attribute'],
            [$value, Str::upper($value), Str::ucfirst($value)],
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

            $line = Arr::get(
                $this->translator->trans('validation.attributes'),
                $expectedAttributeName
            );

            // We allow for the developer to specify language lines for each of the
            // attributes allowing for more displayable counterparts of each of
            // the attributes. This provides the ability for simple formats.
            if ($line) {
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
        if (Str::contains($callback, '@')) {
            list($class, $method) = explode('@', $callback);
        } else {
            list($class, $method) = [$callback, 'replace'];
        }

        return call_user_func_array([$this->container->make($class), $method], array_slice(func_get_args(), 1));
    }
}
