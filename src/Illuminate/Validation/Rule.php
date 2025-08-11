<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;

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
        return new Rules\Can($ability, $arguments);
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
     * Get an "active_url" validation rule.
     *
     * @return string
     */
    public static function activeUrl()
    {
        return 'active_url';
    }

    /**
     * Get an "accepted" validation rule.
     *
     * @return string
     */
    public static function accepted()
    {
        return 'accepted';
    }

    /**
     * Get a "accepted_if" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\AcceptedIf
     */
    public static function acceptedIf($anotherField, $value)
    {
        return new Rules\AcceptedIf($anotherField, $value);
    }

    /**
     * Get an "alpha" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Alpha
     */
    public static function alpha()
    {
        return new Rules\Alpha;
    }

    /**
     * Get a "mac_address" validation rule.
     *
     * @return string
     */
    public static function macAddress()
    {
        return 'mac_address';
    }

    /**
     * Get an "alpha_dash" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\AlphaDash
     */
    public static function alphaDash()
    {
        return new Rules\AlphaDash;
    }

    /**
     * Get an "alpha_num" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\AlphaNum
     */
    public static function alphaNum()
    {
        return new Rules\AlphaNum;
    }

    /**
     * Get an "ascii" validation rule.
     *
     * @return string
     */
    public static function ascii()
    {
        return 'ascii';
    }

    /**
     * Get a "boolean" validation rule.
     *
     * @return string
     */
    public static function boolean()
    {
        return 'boolean';
    }

    /**
     * Get a "declined_if" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\DeclinedIf
     */
    public static function declinedIf($anotherField, $value)
    {
        return new Rules\DeclinedIf($anotherField, $value);
    }

    /**
     * Get a "present_if" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\PresentIf
     */
    public static function presentIf($anotherField, $value)
    {
        return new Rules\PresentIf($anotherField, $value);
    }

    /**
     * Get a "declined" validation rule.
     *
     * @return string
     */
    public static function declined()
    {
        return 'declined';
    }

    /**
     * Get a "confirmed" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Confirmed
     */
    public static function confirmed()
    {
        return new Rules\Confirmed;
    }

    /**
     * Get a "current_password" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\CurrentPassword
     */
    public static function currentPassword()
    {
        return new Rules\CurrentPassword;
    }

    /**
     * Get a "doesnt_start_with" rule builder instance.
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\DoesntStartWith
     */
    public static function doesntStartWith($values)
    {
        return new Rules\DoesntStartWith($values);
    }

    /**
     * Get a "timezone" rule builder instance.
     *
     * @param  array|null  $values
     * @return \Illuminate\Validation\Rules\Timezone
     */
    public static function timezone($values = null)
    {
        return new Rules\Timezone($values);
    }

    /**
     * Get an "starts_with" rule builder instance.
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\StartsWith
     */
    public static function startsWith($values)
    {
        return new Rules\StartsWith($values);
    }

    /**
     * Get a "doesnt_end_with" rule builder instance.
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\DoesntEndWith
     */
    public static function doesntEndWith($values)
    {
        return new Rules\DoesntEndWith($values);
    }

    /**
     * Get an "ends_with" rule builder instance.
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\EndsWith
     */
    public static function endsWith($values)
    {
        return new Rules\EndsWith($values);
    }

    /**
     * Get a "different" rule builder instance.
     *
     * @param  string  $field
     * @return \Illuminate\Validation\Rules\Different
     */
    public static function different($field)
    {
        return new Rules\Different($field);
    }

    /**
     * Get a "size" rule builder instance.
     *
     * @param  int|float  $value
     * @return \Illuminate\Validation\Rules\Size
     */
    public static function size($value)
    {
        return new Rules\Size($value);
    }

    /**
     * Get a "same" rule builder instance.
     *
     * @param  string  $field
     * @return \Illuminate\Validation\Rules\Same
     */
    public static function same($field)
    {
        return new Rules\Same($field);
    }

    /**
     * Get an "array" rule builder instance.
     *
     * @param  array|null  $keys
     * @return \Illuminate\Validation\Rules\Arr
     */
    public static function array($keys = null)
    {
        return new Rules\Arr(...func_get_args());
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
        return new Rules\Unique($table, $column);
    }

    /**
     * Get an "exists" constraint builder instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Rules\Exists($table, $column);
    }

    /**
     * Get an "in" rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\BackedEnum|\UnitEnum|array|string  $values
     * @return \Illuminate\Validation\Rules\In
     */
    public static function in($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new Rules\In(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a "not_in" rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\BackedEnum|\UnitEnum|array|string  $values
     * @return \Illuminate\Validation\Rules\NotIn
     */
    public static function notIn($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new Rules\NotIn(is_array($values) ? $values : func_get_args());
    }

    /**
     * Get a "max" rule builder instance.
     *
     * @param  callable|int  $value
     * @return \Illuminate\Validation\Rules\Max
     */
    public static function max($value)
    {
        return new Rules\Max($value);
    }

    /**
     * Get a "regex" rule builder instance.
     *
     * @param  string  $value
     * @return \Illuminate\Validation\Rules\Regex
     */
    public static function regex($value)
    {
        return new Rules\Regex($value);
    }

    /**
     * Get a "not_regex" rule builder instance.
     *
     * @param  string  $value
     * @return \Illuminate\Validation\Rules\NotRegex
     */
    public static function notRegex($value)
    {
        return new Rules\NotRegex($value);
    }

    /**
     * Get an "ip" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\IpAddress
     */
    public static function ip()
    {
        return new Rules\IpAddress;
    }

    /**
     * Get an "uuid" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Uuid
     */
    public static function uuid()
    {
        return new Rules\Uuid;
    }

    /**
     * Get a "json" validation rule.
     *
     * @return string
     */
    public static function json()
    {
        return 'json';
    }

    /**
     * Get a "sometimes" validation rule.
     *
     * @return string
     */
    public static function sometimes()
    {
        return 'sometimes';
    }

    /**
     * Get a "nullable" validation rule.
     *
     * @return string
     */
    public static function nullable()
    {
        return 'nullable';
    }

    /**
     * Get a "min" rule builder instance.
     *
     * @param  callable|int  $value
     * @return \Illuminate\Validation\Rules\Min
     */
    public static function min($value)
    {
        return new Rules\Min($value);
    }

    /**
     * Get a "decimal" rule builder instance.
     *
     * @param  int  $minPlaces
     * @param  int|null  $maxPlaces
     * @return \Illuminate\Validation\Rules\Decimal
     */
    public static function decimal($minPlaces, $maxPlaces = null)
    {
        return new Rules\Decimal($minPlaces, $maxPlaces);
    }

    /**
     * Get a "between" rule builder instance.
     *
     * @param  int|float  $min
     * @param  int|float  $max
     * @return \Illuminate\Validation\Rules\Between
     */
    public static function between($min, $max)
    {
        return new Rules\Between($min, $max);
    }

    /**
     * Get a "exclude_unless" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\ExcludeUnless
     */
    public static function excludeUnless($anotherField, $value)
    {
        return new Rules\ExcludeUnless($anotherField, $value);
    }

    /**
     * Get a "missing_if" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\MissingIf
     */
    public static function missingIf($anotherField, $value)
    {
        return new Rules\MissingIf($anotherField, $value);
    }

    /**
     * Get a "missing_unless" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\MissingUnless
     */
    public static function missingUnless($anotherField, $value)
    {
        return new Rules\MissingUnless($anotherField, $value);
    }

    /**
     * Get a "exclude_with" rule builder instance.
     *
     * @param  string  $anotherField
     * @return \Illuminate\Validation\Rules\ExcludeWith
     */
    public static function excludeWith($anotherField)
    {
        return new Rules\ExcludeWith($anotherField);
    }

    /**
     * Get a "exclude_without" rule builder instance.
     *
     * @param  string  $anotherField
     * @return \Illuminate\Validation\Rules\ExcludeWithout
     */
    public static function excludeWithout($anotherField)
    {
        return new Rules\ExcludeWithout($anotherField);
    }

    /**
     * Get a "mimetypes" rule builder instance.
     *
     * @param  array  $types
     * @return \Illuminate\Validation\Rules\MimeTypes
     */
    public static function mimeTypes($types)
    {
        return new Rules\MimeTypes($types);
    }

    /**
     * Get a "mimes" rule builder instance.
     *
     * @param  array  $types
     * @return \Illuminate\Validation\Rules\Mimes
     */
    public static function mimes($types)
    {
        return new Rules\Mimes($types);
    }

    /**
     * Get a "prohibits" rule builder instance.
     *
     * @param  array  $types
     * @return \Illuminate\Validation\Rules\Prohibits
     */
    public static function prohibits($types)
    {
        return new Rules\Prohibits($types);
    }

    /**
     * Get a "required_array_keys" rule builder instance.
     *
     * @param  array  $keys
     * @return \Illuminate\Validation\Rules\RequiredArrayKeys
     */
    public static function requiredArrayKeys($keys)
    {
        return new Rules\RequiredArrayKeys($keys);
    }

    /**
     * Get a "required" validation rule.
     *
     * @return string
     */
    public static function required()
    {
        return 'required';
    }

    /**
     * Get a "prohibited" validation rule.
     *
     * @return string
     */
    public static function prohibited()
    {
        return 'prohibited';
    }

    /**
     * Get a "present" validation rule.
     *
     * @return string
     */
    public static function present()
    {
        return 'present';
    }

    /**
     * Get an "exclude" validation rule.
     *
     * @return string
     */
    public static function exclude()
    {
        return 'exclude';
    }

    /**
     * Get a "missing" validation rule.
     *
     * @return string
     */
    public static function missing()
    {
        return 'missing';
    }

    /**
     * Get a "list" validation rule.
     *
     * @return string
     */
    public static function list()
    {
        return 'list';
    }

    /**
     * Get a "bail" validation rule.
     *
     * @return string
     */
    public static function bail()
    {
        return 'bail';
    }

    /**
     * Get a "filled" validation rule.
     *
     * @return string
     */
    public static function filled()
    {
        return 'filled';
    }

    /**
     * Get an "integer" validation rule.
     *
     * @return string
     */
    public static function integer()
    {
        return 'integer';
    }

    /**
     * Get a "url" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Url
     */
    public static function url()
    {
        return new Rules\Url;
    }

    /**
     * Get a "digits" rule builder instance.
     *
     * @param  int  $digits
     * @return \Illuminate\Validation\Rules\Digits
     */
    public static function digits($digits)
    {
        return new Rules\Digits($digits);
    }

    /**
     * Get a "max_digits" rule builder instance.
     *
     * @param  int  $value
     * @return \Illuminate\Validation\Rules\MaxDigits
     */
    public static function maxDigits($value)
    {
        return new Rules\MaxDigits($value);
    }

    /**
     * Get a "gt" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\GreaterThan
     */
    public static function greaterThan($value)
    {
        return new Rules\GreaterThan($value);
    }

    /**
     * Get a "gt" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\GreaterThan
     */
    public static function gt($value)
    {
        return new Rules\GreaterThan($value);
    }

    /**
     * Get a "gte" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\GreaterThanOrEqual
     */
    public static function greaterThanOrEqual($value)
    {
        return new Rules\GreaterThanOrEqual($value);
    }

    /**
     * Get a "gte" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\GreaterThanOrEqual
     */
    public static function gte($value)
    {
        return new Rules\GreaterThanOrEqual($value);
    }

    /**
     * Get a "lt" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\LessThan
     */
    public static function lessThan($value)
    {
        return new Rules\LessThan($value);
    }

    /**
     * Get a "lt" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\LessThan
     */
    public static function lt($value)
    {
        return new Rules\LessThan($value);
    }

    /**
     * Get a "lte" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\LessThanOrEqual
     */
    public static function lessThanOrEqual($value)
    {
        return new Rules\LessThanOrEqual($value);
    }

    /**
     * Get a "lte" rule builder instance.
     *
     * @param  string|int  $value
     * @return \Illuminate\Validation\Rules\LessThanOrEqual
     */
    public static function lte($value)
    {
        return new Rules\LessThanOrEqual($value);
    }

    /**
     * Get a "multiple_of" rule builder instance.
     *
     * @param  int|float  $value
     * @return \Illuminate\Validation\Rules\MultipleOf
     */
    public static function multipleOf($value)
    {
        return new Rules\MultipleOf($value);
    }

    /**
     * Get an "in_array" rule builder instance.
     *
     * @param  string  $value
     * @return \Illuminate\Validation\Rules\InArray
     */
    public static function inArray($value)
    {
        return new Rules\InArray($value);
    }

    /**
     * Get an "in_array_keys" rule builder instance.
     *
     * @param  array  $value
     * @return \Illuminate\Validation\Rules\InArrayKeys
     */
    public static function inArrayKeys($value)
    {
        return new Rules\InArrayKeys($value);
    }

    /**
     * Get a "distinct" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Distinct
     */
    public static function distinct()
    {
        return new Rules\Distinct();
    }

    /**
     * Get a "max_digits" rule builder instance.
     *
     * @param  int  $value
     * @return \Illuminate\Validation\Rules\MinDigits
     */
    public static function minDigits($value)
    {
        return new Rules\MinDigits($value);
    }

    /**
     * Get a "digits_between" rule builder instance.
     *
     * @param  int  $min
     * @param  int  $max
     * @return \Illuminate\Validation\Rules\DigitsBetween
     */
    public static function digitsBetween($min, $max)
    {
        return new Rules\DigitsBetween($min, $max);
    }

    /**
     * Get an "extensions" rule builder instance.
     *
     * @param  array  $extensions
     * @return \Illuminate\Validation\Rules\Extensions
     */
    public static function extensions($extensions = [])
    {
        return new Rules\Extensions($extensions);
    }

    /**
     * Get a "lowercase" validation rule.
     *
     * @return string
     */
    public static function lowercase()
    {
        return 'lowercase';
    }

    /**
     * Get an "uppercase" validation rule.
     *
     * @return string
     */
    public static function uppercase()
    {
        return 'uppercase';
    }

    /**
     * Get a "ulid" validation rule.
     *
     * @return string
     */
    public static function ulid()
    {
        return 'ulid';
    }

    /**
     * Get a required_if rule builder instance.
     *
     * @param  callable|bool  $callback
     * @return \Illuminate\Validation\Rules\RequiredIf
     */
    public static function requiredIf($callback)
    {
        return new Rules\RequiredIf($callback);
    }

    /**
     * Get a "string" validation rule.
     *
     * @return string
     */
    public static function string()
    {
        return 'string';
    }

    /**
     * Get a "hex_color" validation rule.
     *
     * @return string
     */
    public static function hexColor()
    {
        return 'hex_color';
    }

    /**
     * Get an "exclude_if" rule builder instance.
     *
     * @param  callable|bool  $callback
     * @return \Illuminate\Validation\Rules\ExcludeIf
     */
    public static function excludeIf($callback)
    {
        return new Rules\ExcludeIf($callback);
    }

    /**
     * Get a "missing_with" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\MissingWith
     */
    public static function missingWith($fields)
    {
        return new Rules\MissingWith($fields);
    }

    /**
     * Get a "missing_with_all" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\MissingWithAll
     */
    public static function missingWithAll($fields)
    {
        return new Rules\MissingWithAll($fields);
    }

    /**
     * Get a "present_with" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\PresentWith
     */
    public static function presentWith($fields)
    {
        return new Rules\PresentWith($fields);
    }

    /**
     * Get a "present_with_all" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\PresentWithAll
     */
    public static function presentWithAll($fields)
    {
        return new Rules\PresentWithAll($fields);
    }

    /**
     * Get a "required_with" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\RequiredWith
     */
    public static function requiredWith($fields)
    {
        return new Rules\RequiredWith($fields);
    }

    /**
     * Get a "prohibited_if_accepted" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\ProhibitedIfAccepted
     */
    public static function prohibitedIfAccepted($fields)
    {
        return new Rules\ProhibitedIfAccepted($fields);
    }

    /**
     * Get a "prohibited_if_declined" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\ProhibitedIfDeclined
     */
    public static function prohibitedIfDeclined($fields)
    {
        return new Rules\ProhibitedIfDeclined($fields);
    }

    /**
     * Get a "required_if_accepted" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\RequiredIfAccepted
     */
    public static function requiredIfAccepted($fields)
    {
        return new Rules\RequiredIfAccepted($fields);
    }

    /**
     * Get a "required_if_declined" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\RequiredIfDeclined
     */
    public static function requiredIfDeclined($fields)
    {
        return new Rules\RequiredIfDeclined($fields);
    }

    /**
     * Get a "required_with_all" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\RequiredWithAll
     */
    public static function requiredWithAll($fields)
    {
        return new Rules\RequiredWithAll($fields);
    }

    /**
     * Get a "required_without" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\RequiredWithout
     */
    public static function requiredWithout($fields)
    {
        return new Rules\RequiredWithout($fields);
    }

    /**
     * Get a "required_without_all" rule builder instance.
     *
     * @param  array  $fields
     * @return \Illuminate\Validation\Rules\RequiredWithoutAll
     */
    public static function requiredWithoutAll($fields)
    {
        return new Rules\RequiredWithoutAll($fields);
    }

    /**
     * Get a prohibited_if rule builder instance.
     *
     * @param  callable|bool  $callback
     * @return \Illuminate\Validation\Rules\ProhibitedIf
     */
    public static function prohibitedIf($callback)
    {
        return new Rules\ProhibitedIf($callback);
    }

    /**
     * Get a "prohibited_unless" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\ProhibitedUnless
     */
    public static function prohibitedUnless($anotherField, $value)
    {
        return new Rules\ProhibitedUnless($anotherField, $value);
    }

    /**
     * Get a "present_unless" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\PresentUnless
     */
    public static function presentUnless($anotherField, $value)
    {
        return new Rules\PresentUnless($anotherField, $value);
    }

    /**
     * Get a "required_unless" rule builder instance.
     *
     * @param  string  $anotherField
     * @param  string|null|int|float  $value
     * @return \Illuminate\Validation\Rules\RequiredUnless
     */
    public static function requiredUnless($anotherField, $value)
    {
        return new Rules\RequiredUnless($anotherField, $value);
    }

    /**
     * Get a "date" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Date
     */
    public static function date()
    {
        return new Rules\Date;
    }

    /**
     * Get an "email" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Email
     */
    public static function email()
    {
        return new Rules\Email;
    }

    /**
     * Get an "enum" rule builder instance.
     *
     * @param  class-string  $type
     * @return \Illuminate\Validation\Rules\Enum
     */
    public static function enum($type)
    {
        return new Rules\Enum($type);
    }

    /**
     * Get a "file" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\File
     */
    public static function file()
    {
        return new Rules\File;
    }

    /**
     * Get an "image" file rule builder instance.
     *
     * @param  bool  $allowSvg
     * @return \Illuminate\Validation\Rules\ImageFile
     */
    public static function imageFile($allowSvg = false)
    {
        return new Rules\ImageFile($allowSvg);
    }

    /**
     * Get a "dimensions" rule builder instance.
     *
     * @param  array  $constraints
     * @return \Illuminate\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Rules\Dimensions($constraints);
    }

    /**
     * Get a "numeric" rule builder instance.
     *
     * @return \Illuminate\Validation\Rules\Numeric
     */
    public static function numeric()
    {
        return new Rules\Numeric;
    }

    /**
     * Get an "any_of" rule builder instance.
     *
     * @param  array  $rules
     * @return \Illuminate\Validation\Rules\AnyOf
     *
     * @throws \InvalidArgumentException
     */
    public static function anyOf($rules)
    {
        return new Rules\AnyOf($rules);
    }

    /**
     * Get a "contains" rule builder instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|\BackedEnum|\UnitEnum|array|string  $values
     * @return \Illuminate\Validation\Rules\Contains
     */
    public static function contains($values)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return new Rules\Contains(is_array($values) ? $values : func_get_args());
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
