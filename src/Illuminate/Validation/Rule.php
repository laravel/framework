<?php

namespace Illuminate\Validation;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\AcceptedIf;
use Illuminate\Validation\Rules\AnyOf;
use Illuminate\Validation\Rules\ArrayKeys;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Can;
use Illuminate\Validation\Rules\Confirmed;
use Illuminate\Validation\Rules\CurrentPassword;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\DeclinedIf;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\Distinct;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\ExcludeIf;
use Illuminate\Validation\Rules\ExcludeUnless;
use Illuminate\Validation\Rules\ExcludeWith;
use Illuminate\Validation\Rules\ExcludeWithout;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\ImageFile;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\InArray;
use Illuminate\Validation\Rules\InArrayKeys;
use Illuminate\Validation\Rules\IpAddress;
use Illuminate\Validation\Rules\MissingIf;
use Illuminate\Validation\Rules\MissingUnless;
use Illuminate\Validation\Rules\MissingWith;
use Illuminate\Validation\Rules\MissingWithAll;
use Illuminate\Validation\Rules\NotIn;
use Illuminate\Validation\Rules\NotRegex;
use Illuminate\Validation\Rules\Numeric;
use Illuminate\Validation\Rules\PresentIf;
use Illuminate\Validation\Rules\PresentUnless;
use Illuminate\Validation\Rules\PresentWith;
use Illuminate\Validation\Rules\PresentWithAll;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\ProhibitedIfAccepted;
use Illuminate\Validation\Rules\ProhibitedIfDeclined;
use Illuminate\Validation\Rules\ProhibitedUnless;
use Illuminate\Validation\Rules\Prohibits;
use Illuminate\Validation\Rules\Regex;
use Illuminate\Validation\Rules\RequiredArrayKeys;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\RequiredIfAccepted;
use Illuminate\Validation\Rules\RequiredIfDeclined;
use Illuminate\Validation\Rules\RequiredUnless;
use Illuminate\Validation\Rules\RequiredWith;
use Illuminate\Validation\Rules\RequiredWithAll;
use Illuminate\Validation\Rules\RequiredWithout;
use Illuminate\Validation\Rules\RequiredWithoutAll;
use Illuminate\Validation\Rules\StringRule;
use Illuminate\Validation\Rules\Timezone;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Rules\Url;
use Illuminate\Validation\Rules\Uuid;

class Rule
{
    use Macroable;

    /**
     * Get a can constraint builder instance.
     *
     * @param  string  $ability
     * @param  mixed  ...$arguments
     * @return \Illuminate\Validation\Rules\Can
     */
    public static function can($ability, ...$arguments)
    {
        return new Can($ability, $arguments);
    }

    /**
     * Apply the given rules if the given condition is truthy.
     *
     * @param  callable|bool  $condition
     * @param  \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule|\Illuminate\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule|\Illuminate\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \Illuminate\Validation\ConditionalRules
     */
    public static function when($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $rules, $defaultRules);
    }

    /**
     * Apply the given rules if the given condition is falsy.
     *
     * @param  callable|bool  $condition
     * @param  \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule|\Illuminate\Contracts\Validation\Rule|\Closure|array|string  $rules
     * @param  \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Contracts\Validation\InvokableRule|\Illuminate\Contracts\Validation\Rule|\Closure|array|string  $defaultRules
     * @return \Illuminate\Validation\ConditionalRules
     */
    public static function unless($condition, $rules, $defaultRules = [])
    {
        return new ConditionalRules($condition, $defaultRules, $rules);
    }

    /**
     * Get an array rule builder instance.
     *
     * @param  array|null  $keys
     * @return \Illuminate\Validation\Rules\ArrayRule
     */
    public static function array($keys = null)
    {
        return new ArrayRule(...func_get_args());
    }

    /**
     * Get an array keys rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array|string  $keys
     * @return \Illuminate\Validation\Rules\ArrayKeys
     */
    public static function arrayKeys($keys)
    {
        return new ArrayKeys(...func_get_args());
    }

    /**
     * Create a new nested rule set.
     *
     * @param  callable  $callback
     * @return \Illuminate\Validation\NestedRules
     */
    public static function forEach($callback)
    {
        return new NestedRules($callback);
    }

    /**
     * Get a unique constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Unique
     */
    public static function unique($table, $column = 'NULL')
    {
        return new Unique($table, $column);
    }

    /**
     * Get an exists constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Exists($table, $column);
    }

    /**
     * Get an in rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Illuminate\Validation\Rules\In
     */
    public static function in($values)
    {
        return new In(...func_get_args());
    }

    /**
     * Get a not_in rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Illuminate\Validation\Rules\NotIn
     */
    public static function notIn($values)
    {
        return new NotIn(...func_get_args());
    }

    /**
     * Get an accepted validation rule.
     *
     * @return string
     */
    public static function accepted()
    {
        return 'accepted';
    }

    /**
     * Get an active_url validation rule.
     *
     * @return string
     */
    public static function activeUrl()
    {
        return 'active_url';
    }

    /**
     * Get a base64 validation rule.
     *
     * @return string
     */
    public static function base64()
    {
        return 'base64';
    }

    /**
     * Get a bail validation rule.
     *
     * @return string
     */
    public static function bail()
    {
        return 'bail';
    }

    /**
     * Get a boolean validation rule.
     *
     * @return string
     */
    public static function boolean()
    {
        return 'boolean';
    }

    /**
     * Get a declined validation rule.
     *
     * @return string
     */
    public static function declined()
    {
        return 'declined';
    }

    /**
     * Get an exclude validation rule.
     *
     * @return string
     */
    public static function exclude()
    {
        return 'exclude';
    }

    /**
     * Get a filled validation rule.
     *
     * @return string
     */
    public static function filled()
    {
        return 'filled';
    }

    /**
     * Get a hex_color validation rule.
     *
     * @return string
     */
    public static function hexColor()
    {
        return 'hex_color';
    }

    /**
     * Get a json validation rule.
     *
     * @return string
     */
    public static function json()
    {
        return 'json';
    }

    /**
     * Get a list validation rule.
     *
     * @return string
     */
    public static function list()
    {
        return 'list';
    }

    /**
     * Get a mac_address validation rule.
     *
     * @return string
     */
    public static function macAddress()
    {
        return 'mac_address';
    }

    /**
     * Get a missing validation rule.
     *
     * @return string
     */
    public static function missing()
    {
        return 'missing';
    }

    /**
     * Get a nullable validation rule.
     *
     * @return string
     */
    public static function nullable()
    {
        return 'nullable';
    }

    /**
     * Get a present validation rule.
     *
     * @return string
     */
    public static function present()
    {
        return 'present';
    }

    /**
     * Get a prohibited validation rule.
     *
     * @return string
     */
    public static function prohibited()
    {
        return 'prohibited';
    }

    /**
     * Get a required validation rule.
     *
     * @return string
     */
    public static function required()
    {
        return 'required';
    }

    /**
     * Get a sometimes validation rule.
     *
     * @return string
     */
    public static function sometimes()
    {
        return 'sometimes';
    }

    /**
     * Get an ulid validation rule.
     *
     * @return string
     */
    public static function ulid()
    {
        return 'ulid';
    }

    /**
     * Get an accepted_if rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\AcceptedIf
     */
    public static function acceptedIf($anotherField, $value)
    {
        return new AcceptedIf(...func_get_args());
    }

    /**
     * Get a declined_if rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\DeclinedIf
     */
    public static function declinedIf($anotherField, $value)
    {
        return new DeclinedIf(...func_get_args());
    }

    /**
     * Get a missing_if rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\MissingIf
     */
    public static function missingIf($anotherField, $value)
    {
        return new MissingIf(...func_get_args());
    }

    /**
     * Get a missing_unless rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\MissingUnless
     */
    public static function missingUnless($anotherField, $value)
    {
        return new MissingUnless(...func_get_args());
    }

    /**
     * Get a present_if rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\PresentIf
     */
    public static function presentIf($anotherField, $value)
    {
        return new PresentIf(...func_get_args());
    }

    /**
     * Get a present_unless rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\PresentUnless
     */
    public static function presentUnless($anotherField, $value)
    {
        return new PresentUnless(...func_get_args());
    }

    /**
     * Get a confirmed rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Confirmed
     */
    public static function confirmed()
    {
        return new Confirmed;
    }

    /**
     * Get a current_password rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\CurrentPassword
     */
    public static function currentPassword()
    {
        return new CurrentPassword;
    }

    /**
     * Get a distinct rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Distinct
     */
    public static function distinct()
    {
        return new Distinct;
    }

    /**
     * Get an exclude_with rule builder instance.
     *
     * @param  string  $anotherField
     * @return \Illuminate\Validation\Rules\ExcludeWith
     */
    public static function excludeWith($anotherField)
    {
        return new ExcludeWith($anotherField);
    }

    /**
     * Get an exclude_without rule builder instance.
     *
     * @param  array|string  $anotherField
     * @return \Illuminate\Validation\Rules\ExcludeWithout
     */
    public static function excludeWithout($anotherField)
    {
        return new ExcludeWithout(...func_get_args());
    }

    /**
     * Get an in_array rule builder instance.
     *
     * @param  string  $otherField
     * @return \Illuminate\Validation\Rules\InArray
     */
    public static function inArray($otherField)
    {
        return new InArray($otherField);
    }

    /**
     * Get an in_array_keys rule builder instance.
     *
     * @param  array|string  $keys
     * @return \Illuminate\Validation\Rules\InArrayKeys
     */
    public static function inArrayKeys($keys)
    {
        return new InArrayKeys(...func_get_args());
    }

    /**
     * Get an IP address rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\IpAddress
     */
    public static function ip()
    {
        return new IpAddress;
    }

    /**
     * Get a missing_with rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\MissingWith
     */
    public static function missingWith($fields)
    {
        return new MissingWith(...func_get_args());
    }

    /**
     * Get a missing_with_all rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\MissingWithAll
     */
    public static function missingWithAll($fields)
    {
        return new MissingWithAll(...func_get_args());
    }

    /**
     * Get a present_with rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\PresentWith
     */
    public static function presentWith($fields)
    {
        return new PresentWith(...func_get_args());
    }

    /**
     * Get a present_with_all rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\PresentWithAll
     */
    public static function presentWithAll($fields)
    {
        return new PresentWithAll(...func_get_args());
    }

    /**
     * Get a prohibited_if_accepted rule builder instance.
     *
     * @param  string  $field
     * @return \Illuminate\Validation\Rules\ProhibitedIfAccepted
     */
    public static function prohibitedIfAccepted($field)
    {
        return new ProhibitedIfAccepted($field);
    }

    /**
     * Get a prohibited_if_declined rule builder instance.
     *
     * @param  string  $field
     * @return \Illuminate\Validation\Rules\ProhibitedIfDeclined
     */
    public static function prohibitedIfDeclined($field)
    {
        return new ProhibitedIfDeclined($field);
    }

    /**
     * Get a prohibits rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\Prohibits
     */
    public static function prohibits($fields)
    {
        return new Prohibits(...func_get_args());
    }

    /**
     * Get a regex rule builder instance.
     *
     * @param  string  $pattern
     * @return \Illuminate\Validation\Rules\Regex
     */
    public static function regex($pattern)
    {
        return new Regex($pattern);
    }

    /**
     * Get a not_regex rule builder instance.
     *
     * @param  string  $pattern
     * @return \Illuminate\Validation\Rules\NotRegex
     */
    public static function notRegex($pattern)
    {
        return new NotRegex($pattern);
    }

    /**
     * Get a required_array_keys rule builder instance.
     *
     * @param  array|string  $keys
     * @return \Illuminate\Validation\Rules\RequiredArrayKeys
     */
    public static function requiredArrayKeys($keys)
    {
        return new RequiredArrayKeys(...func_get_args());
    }

    /**
     * Get a required_if_accepted rule builder instance.
     *
     * @param  string  $field
     * @return \Illuminate\Validation\Rules\RequiredIfAccepted
     */
    public static function requiredIfAccepted($field)
    {
        return new RequiredIfAccepted($field);
    }

    /**
     * Get a required_if_declined rule builder instance.
     *
     * @param  string  $field
     * @return \Illuminate\Validation\Rules\RequiredIfDeclined
     */
    public static function requiredIfDeclined($field)
    {
        return new RequiredIfDeclined($field);
    }

    /**
     * Get a required_with rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\RequiredWith
     */
    public static function requiredWith($fields)
    {
        return new RequiredWith(...func_get_args());
    }

    /**
     * Get a required_with_all rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\RequiredWithAll
     */
    public static function requiredWithAll($fields)
    {
        return new RequiredWithAll(...func_get_args());
    }

    /**
     * Get a required_without rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\RequiredWithout
     */
    public static function requiredWithout($fields)
    {
        return new RequiredWithout(...func_get_args());
    }

    /**
     * Get a required_without_all rule builder instance.
     *
     * @param  array|string  $fields
     * @return \Illuminate\Validation\Rules\RequiredWithoutAll
     */
    public static function requiredWithoutAll($fields)
    {
        return new RequiredWithoutAll(...func_get_args());
    }

    /**
     * Get a timezone rule builder instance.
     *
     * @param  array|null  $arguments
     * @return \Illuminate\Validation\Rules\Timezone
     */
    public static function timezone($arguments = null)
    {
        return new Timezone($arguments);
    }

    /**
     * Get a URL rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Url
     */
    public static function url()
    {
        return new Url;
    }

    /**
     * Get a UUID rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Uuid
     */
    public static function uuid()
    {
        return new Uuid;
    }

    /**
     * Get a required_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Illuminate\Validation\Rules\RequiredIf
     */
    public static function requiredIf($callback)
    {
        return new RequiredIf($callback);
    }

    /**
     * Get a required_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool|null  $callback
     * @return \Illuminate\Validation\Rules\RequiredUnless
     */
    public static function requiredUnless($callback)
    {
        return new RequiredUnless($callback);
    }

    /**
     * Get an exclude_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Illuminate\Validation\Rules\ExcludeIf
     */
    public static function excludeIf($callback)
    {
        return new ExcludeIf($callback);
    }

    /**
     * Get an exclude_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Illuminate\Validation\Rules\ExcludeUnless
     */
    public static function excludeUnless($callback)
    {
        return new ExcludeUnless($callback);
    }

    /**
     * Get a prohibited_if rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Illuminate\Validation\Rules\ProhibitedIf
     */
    public static function prohibitedIf($callback)
    {
        return new ProhibitedIf($callback);
    }

    /**
     * Get a prohibited_unless rule builder instance.
     *
     * @param  (\Closure(): bool)|bool  $callback
     * @return \Illuminate\Validation\Rules\ProhibitedUnless
     */
    public static function prohibitedUnless($callback)
    {
        return new ProhibitedUnless($callback);
    }

    /**
     * Get a date rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Date
     */
    public static function date()
    {
        return new Date;
    }

    /**
     * Get a datetime rule builder instance.
     */
    public static function dateTime(): Date
    {
        return (new Date)->format('Y-m-d H:i:s');
    }

    /**
     * Get an email rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Email
     */
    public static function email()
    {
        return new Email;
    }

    /**
     * Get an enum rule builder instance.
     *
     * @param  class-string  $type
     * @return \Illuminate\Validation\Rules\Enum
     */
    public static function enum($type)
    {
        return new Enum($type);
    }

    /**
     * Get a file rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\File
     */
    public static function file()
    {
        return new File;
    }

    /**
     * Get an image file rule builder instance.
     *
     * @param  bool  $allowSvg
     * @return \Illuminate\Validation\Rules\ImageFile
     */
    public static function imageFile($allowSvg = false)
    {
        return new ImageFile($allowSvg);
    }

    /**
     * Get a dimensions rule builder instance.
     *
     * @param  array  $constraints
     * @return \Illuminate\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Dimensions($constraints);
    }

    /**
     * Get a string rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\StringRule
     */
    public static function string()
    {
        return new StringRule;
    }

    /**
     * Get a numeric rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Numeric
     */
    public static function numeric()
    {
        return new Numeric;
    }

    /**
     * Get an "any of" rule builder instance.
     *
     * @param  array  $rules
     * @return \Illuminate\Validation\Rules\AnyOf
     *
     * @throws \InvalidArgumentException
     */
    public static function anyOf($rules)
    {
        return new AnyOf($rules);
    }

    /**
     * Get a contains rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Illuminate\Validation\Rules\Contains
     */
    public static function contains($values)
    {
        return new Rules\Contains(...func_get_args());
    }

    /**
     * Get a "does not contain" rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\UnitEnum|array|string  $values
     * @return \Illuminate\Validation\Rules\DoesntContain
     */
    public static function doesntContain($values)
    {
        return new Rules\DoesntContain(...func_get_args());
    }

    /**
     * Compile a set of rules for an attribute.
     *
     * @param  string  $attribute
     * @param  array  $rules
     * @param  array|null  $data
     * @return object|\stdClass
     */
    public static function compile($attribute, $rules, $data = null)
    {
        $parser = new ValidationRuleParser(
            Arr::undot(Arr::wrap($data))
        );

        if (is_array($rules) && ! array_is_list($rules)) {
            $nested = [];

            foreach ($rules as $key => $rule) {
                $nested[$attribute.'.'.$key] = $rule;
            }

            $rules = $nested;
        } else {
            $rules = [$attribute => $rules];
        }

        return $parser->explode(ValidationRuleParser::filterConditionalRules($rules, $data));
    }
}
