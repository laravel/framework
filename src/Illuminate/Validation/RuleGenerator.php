<?php

namespace Illuminate\Validation;

trait RuleGenerator
{
    /**
     * Generate validation rule string
     */
    private static function generate(string $rule, array $options = []): string
    {
        $args = $options ? ':' . implode(',', $options) : '';
        return $rule . $args;
    }

    /**
     * The field under validation must be "yes", "on", 1, or true.
     * This is useful for validating "Terms of Service" acceptance or similar fields.
     */
    public static function accepted(): string
    {
        return self::generate('accepted');
    }

    /**
     * The field under validation must have a valid A or AAAA record according to the dns_get_record PHP function.
     * The hostname of the provided URL is extracted using the parse_url PHP function before being passed to dns_get_record.
     */
    public static function activeUrl(): string
    {
        return self::generate('active_url');
    }

    /**
     * The field under validation must be a value after a given date.
     * passed into the strtotime PHP function in order to be converted to a valid DateTime instance:
     * 'start_date' => 'required|date|after:tomorrow'
     * Instead of passing a date string to be evaluated by strtotime, you may specify another field to compare against the date:
     * 'finish_date' => 'required|date|after:start_date'
     */
    public static function after(string $date): string
    {
        return self::generate('after', [$date]);
    }

    /**
     * The field under validation must be a value after or equal to the given date.
     * For more information, see the after rule.
     */
    public static function afterOrEqual(string $date): string
    {
        return self::generate('after_or_equal', [$date]);
    }

    /**
     * The field under validation must be entirely alphabetic characters.
     */
    public static function alpha(): string
    {
        return self::generate('alpha');
    }

    /**
     * The field under validation may have alpha-numeric characters, as well as dashes and underscores.
     */
    public static function alphaDash(): string
    {
        return self::generate('alpha_dash');
    }

    /**
     * The field under validation must be entirely alpha-numeric characters.
     */
    public static function alphaNum(): string
    {
        return self::generate('alpha_num');
    }

    /**
     * The field under validation must be a PHP array.
     * When additional values are provided to the array rule, each key in the input array must be present within the list of values provided to the rule.
     * In the following example, the admin key in the input array is invalid since it is not contained in the list of values provided to the array rule:
     * ```
     * use Illuminate\Support\Facades\Validator;
     *
     * $input = [
     *   'user' => [
     *     'name' => 'Taylor Otwell',
     *     'username' => 'taylorotwell',
     *     'admin' => true,
     *   ],
     * ];
     *
     * Validator::make($input, [
     *   'user' => 'array:username,locale',
     * ]);
     * ```
     */
    public static function array(array $keys = []): string
    {
        return self::generate('array', $keys);
    }

    /**
     * Stop running validation rules for the field after the first validation failure.
     * While the bail rule will only stop validating a specific field when it encounters a validation failure,
     * The stopOnFirstFailure method will inform the validator that it should stop validating all attributes once a single validation failure has occurred:
     * ```
     * if ($validator->stopOnFirstFailure()->fails()) {
     *   // ...
     * }
     * ```
     */
    public static function bail(): string
    {
        return self::generate('bail');
    }

    /**
     * The field under validation must be a value preceding the given date.
     * The dates will be passed into the PHP strtotime function in order to be converted into a valid DateTime instance.
     * In addition, like the after rule, the name of another field under validation may be supplied as the value of date.
     */
    public static function before(string $date): string
    {
        return self::generate('before', [$date]);
    }

    /**
     * The field under validation must be a value preceding or equal to the given date.
     * The dates will be passed into the PHP strtotime function in order to be converted into a valid DateTime instance.
     * In addition, like the after rule, the name of another field under validation may be supplied as the value of date.
     */
    public static function beforeOrEqual(string $date): string
    {
        return self::generate('before_or_equal', [$date]);
    }

    /**
     * The field under validation must have a size between the given min and max.
     * Strings, numerics, arrays, and files are evaluated in the same fashion as the size rule.
     */
    public static function between(float $min, float $max): string
    {
        return self::generate('between', [$min, $max]);
    }

    /**
     * The field under validation must be able to be cast as a boolean.
     * Accepted input are true, false, 1, 0, "1", and "0".
     */
    public static function boolean(): string
    {
        return self::generate('boolean');
    }

    /**
     * The field under validation must have a matching field of {field}_confirmation.
     * For example, if the field under validation is password, a matching password_confirmation field must be present in the input.
     */
    public static function confirmed(): string
    {
        return self::generate('confirmed');
    }

    /**
     * The field under validation must be a valid, non-relative date according to the strtotime PHP function.
     */
    public static function date(): string
    {
        return self::generate('date');
    }

    /**
     * The field under validation must be equal to the given date.
     * The dates will be passed into the PHP strtotime function in order to be converted into a valid DateTime instance.
     */
    public static function dateEquals(string $date): string
    {
        return self::generate('date_equals', [$date]);
    }

    /**
     * The field under validation must match the given format.
     * You should use either date or date_format when validating a field, not both.
     * This validation rule supports all formats supported by PHP's DateTime class.
     */
    public static function dateFormat(string $format): string
    {
        return self::generate('date_format', [$format]);
    }

    /**
     * The field under validation must have a different value than field.
     */
    public static function different(string $field): string
    {
        return self::generate('different', [$field]);
    }

    /**
     * The field under validation must be numeric and must have an exact length of value.
     */
    public static function digits(int $value): string
    {
        return self::generate('digits', [$value]);
    }

    /**
     * The field under validation must be numeric and must have a length between the given min and max.
     */
    public static function digitsBetween(int $min, int $max): string
    {
        return self::generate('digits_between', [$min, $max]);
    }

    /**
     * When validating arrays, the field under validation must not have any duplicate values:
     * 'foo.*.id' => 'distinct'
     * Distinct uses loose variable comparisons by default. To use strict comparisons, you may add the strict parameter to your validation rule definition:
     * 'foo.*.id' => 'distinct:strict'
     * You may add ignore_case to the validation rule's arguments to make the rule ignore capitalization differences:
     * 'foo.*.id' => 'distinct:ignore_case'
     */
    public static function distinct(array $options = []): string
    {
        return self::generate('distinct', $options);
    }

    /**
     * The field under validation must be formatted as an email address.
     * rule utilizes the egulias/email-validator package for validating the email address.
     * By default, the RFCValidation validator is applied, but you can apply other validation styles as well:
     * 'email' => 'email:rfc,dns'
     * The example above will apply the RFCValidation and DNSCheckValidation validations.
     * Here's a full list of validation styles you can apply:
     *
     * rfc: RFCValidation
     * strict: NoRFCWarningsValidation
     * dns: DNSCheckValidation
     * spoof: SpoofCheckValidation
     * filter: FilterEmailValidation
     *
     * The filter validator, which uses PHP's filter_var function, ships with Laravel and was Laravel's default email validation behavior prior to Laravel version 5.8.
     * The dns and spoof validators require the PHP intl extension.
     */
    public static function email(array $options = []): string
    {
        return self::generate('email', $options);
    }

    /**
     * The field under validation must end with one of the given values.
     */
    public static function endsWith(array $values): string
    {
        return self::generate('ends_with', $values);
    }

    /**
     * The field under validation will be excluded from the request data returned by the validate and validated methods if the anotherfield field is equal to value.
     */
    public static function excludeIf(string $anotherField, $value): string
    {
        return self::generate('exclude_if', [$anotherField, $value]);
    }

    /**
     * The field under validation will be excluded from the request data returned by the validate and validated methods unless anotherfield's field is equal to value.
     * If value is null (exclude_unless:name,null), the field under validation will be excluded unless the comparison field is null or the comparison field is missing from the request data.
     */
    public static function excludeUnless(string $anotherField, $value): string
    {
        return self::generate('exclude_unless', [$anotherField, $value]);
    }

    /**
     * The field under validation must be a successfully uploaded file.
     */
    public static function file(): string
    {
        return self::generate('file');
    }

    /**
     * The field under validation must not be empty when it is present.
     */
    public static function filled(): string
    {
        return self::generate('filled');
    }

    /**
     * The field under validation must be greater than the given field.
     * The two fields must be of the same type.
     * Strings, numerics, arrays, and files are evaluated using the same conventions as the size rule.
     */
    public static function gt(string $field): string
    {
        return self::generate('gt', [$field]);
    }

    /**
     * The field under validation must be greater than or equal to the given field.
     * The two fields must be of the same type.
     * Strings, numerics, arrays, and files are evaluated using the same conventions as the size rule.
     */
    public static function gte(string $field): string
    {
        return self::generate('gte', [$field]);
    }

    /**
     * The file under validation must be an image (jpg, jpeg, png, bmp, gif, svg, or webp).
     */
    public static function image(): string
    {
        return self::generate('image');
    }

    /**
     * The field under validation must exist in anotherfield's values.
     */
    public static function inArray(string $anotherField): string
    {
        return self::generate('in_array', [$anotherField]);
    }

    /**
     * The field under validation must be an integer.
     * This validation rule does not verify that the input is of the "integer" variable type, only that the input is of a type accepted by PHP's FILTER_VALIDATE_INT rule.
     * If you need to validate the input as being a number please use this rule in combination with the numeric validation rule.
     */
    public static function integer(): string
    {
        return self::generate('integer');
    }

    /**
     * The field under validation must be an IP address.
     */
    public static function ip(): string
    {
        return self::generate('ip');
    }

    /**
     * The field under validation must be an IPv4 address.
     */
    public static function ipv4(): string
    {
        return self::generate('ipv4');
    }

    /**
     * The field under validation must be an IPv6 address.
     */
    public static function ipv6(): string
    {
        return self::generate('ipv6');
    }

    /**
     * The field under validation must be a valid JSON string.
     */
    public static function json(): string
    {
        return self::generate('json');
    }

    /**
     * The field under validation must be less than the given field.
     * The two fields must be of the same type.
     * Strings, numerics, arrays, and files are evaluated using the same conventions as the size rule.
     */
    public static function lt(string $field): string
    {
        return self::generate('lt', [$field]);
    }

    /**
     * The field under validation must be less than or equal to the given field.
     * The two fields must be of the same type.
     * Strings, numerics, arrays, and files are evaluated using the same conventions as the size rule.
     */
    public static function lte(string $field): string
    {
        return self::generate('lte', [$field]);
    }

    /**
     * The field under validation must be less than or equal to a maximum value.
     * The two fields must be of the same type.
     * Strings, numerics, arrays, and files are evaluated using the same conventions as the size rule.
     */
    public static function max(float $value): string
    {
        return self::generate('max', [$value]);
    }

    /**
     * The file under validation must match one of the given MIME types:
     * 'video' => 'mimetypes:video/avi,video/mpeg,video/quicktime'
     * To determine the MIME type of the uploaded file, the file's contents will be read and the framework will attempt to guess the MIME type,
     * which may be different from the client's provided MIME type.
     */
    public static function mimeTypes(array $types): string
    {
        return self::generate('mimetypes', $types);
    }

    /**
     * The file under validation must have a MIME type corresponding to one of the listed extensions.
     * Basic Usage Of MIME Rule:
     * 'photo' => 'mimes:jpg,bmp,png'
     * Even though you only need to specify the extensions, this rule actually validates the MIME type of the file by reading the file's contents and guessing its MIME type.
     * A full listing of MIME types and their corresponding extensions may be found at the following location:
     * https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     */
    public static function mimes(array $extensions): string
    {
        return self::generate('mimes', $extensions);
    }

    /**
     * The field under validation must have a minimum value.
     * The two fields must be of the same type.
     * Strings, numerics, arrays, and files are evaluated using the same conventions as the size rule.
     */
    public static function min(float $value): string
    {
        return self::generate('min', [$value]);
    }

    /**
     * The field under validation must be a multiple of value.
     */
    public static function multipleOf(float $value): string
    {
        return self::generate('multiple_of', [$value]);
    }

    /**
     * The field under validation must not match the given regular expression.
     * Internally, this rule uses the PHP preg_match function.
     * The pattern specified should obey the same formatting required by preg_match and thus also include valid delimiters.
     * For example: 'email' => 'not_regex:/^.+$/i'.
     * When using the regex / not_regex patterns,
     * it may be necessary to specify your validation rules using an array instead of using | delimiters,
     * especially if the regular expression contains a | character.
     */
    public static function notRegex(string $pattern): string
    {
        return self::generate('not_regex', [$pattern]);
    }

    /**
     * The field under validation may be null.
     */
    public static function nullable(): string
    {
        return self::generate('nullable');
    }

    /**
     * The field under validation must be numeric.
     */
    public static function numeric(): string
    {
        return self::generate('numeric');
    }

    /**
     * The field under validation must match the authenticated user's password.
     * You may specify an authentication guard using the rule's first parameter:
     * 'password' => 'password:api'
     */
    public static function password(?string $guard = null): string
    {
        return self::generate('password', $guard ? [$guard] : []);
    }

    /**
     * The field under validation must be present in the input data but can be empty.
     */
    public static function present(): string
    {
        return self::generate('present');
    }

    /**
     * The field under validation must be empty or not present.
     */
    public static function prohibited(): string
    {
        return self::generate('prohibited');
    }

    /**
     * The field under validation must be empty or not present if the anotherfield field is equal to any value.
     */
    public static function prohibitedIf(string $anotherField, $value): string
    {
        return self::generate('prohibited_if', [$anotherField, $value]);
    }

    /**
     * The field under validation must be empty or not present unless the anotherfield field is equal to any value.
     */
    public static function prohibitedUnless(string $anotherField, $value): string
    {
        return self::generate('prohibited_unless', [$anotherField, $value]);
    }

    /**
     * The field under validation must match the given regular expression.
     * Internally, this rule uses the PHP preg_match function.
     * The pattern specified should obey the same formatting required by preg_match and thus also include valid delimiters.
     * For example: 'email' => 'regex:/^.+@.+$/i'.
     * When using the regex / not_regex patterns,
     * it may be necessary to specify your validation rules using an array instead of using | delimiters,
     * especially if the regular expression contains a | character.
     */
    public static function regex(string $pattern): string
    {
        return self::generate('regex', [$pattern]);
    }

    /**
     * The field under validation must be present in the input data and not empty.
     * A field is considered "empty" if one of the following conditions are true:
     * The value is null.
     * The value is an empty string.
     * The value is an empty array or empty Countable object.
     * The value is an uploaded file with no path.
     */
    public static function required(): string
    {
        return self::generate('required');
    }

    /**
     * The field under validation must be present and not empty unless the anotherfield field is equal to any value.
     * This also means anotherfield must be present in the request data unless value is null.
     * If value is null (required_unless:name,null), the field under validation will be required unless the comparison field is null or the comparison field is missing from the request data.
     */
    public static function requiredUnless(string $anotherField, $value): string
    {
        return self::generate('required_unless', [$anotherField, $value]);
    }

    /**
     * The field under validation must be present and not empty only if any of the other specified fields are present and not empty.
     */
    public static function requiredWith(array $fields): string
    {
        return self::generate('required_with', $fields);
    }

    /**
     * The field under validation must be present and not empty only if all of the other specified fields are present and not empty.
     */
    public static function requiredWithAll(array $fields): string
    {
        return self::generate('required_with_all', $fields);
    }

    /**
     * The field under validation must be present and not empty only when any of the other specified fields are empty or not present.
     */
    public static function requiredWithout(array $fields): string
    {
        return self::generate('required_without', $fields);
    }

    /**
     * The field under validation must be present and not empty only when all of the other specified fields are empty or not present.
     */
    public static function requiredWithoutAll(array $fields): string
    {
        return self::generate('required_without_all', $fields);
    }

    /**
     * The given field must match the field under validation.
     */
    public static function same(string $field): string
    {
        return self::generate('same', [$field]);
    }

    /**
     * The field under validation must have a size matching the given value.
     * For string data, value corresponds to the number of characters.
     * For numeric data, value corresponds to a given integer value (the attribute must also have the numeric or integer rule).
     * For an array, size corresponds to the count of the array.
     * For files, size corresponds to the file size in kilobytes.
     * Let's look at some examples:
     * ```
     * // Validate that a string is exactly 12 characters long...
     * 'title' => 'size:12';
     *
     * // Validate that a provided integer equals 10...
     * 'seats' => 'integer|size:10';
     *
     * // Validate that an array has exactly 5 elements...
     * 'tags' => 'array|size:5';
     *
     * // Validate that an uploaded file is exactly 512 kilobytes...
     * 'image' => 'file|size:512';
     * ```
     */
    public static function size(float $value): string
    {
        return self::generate('size', [$value]);
    }

    /**
     * The field under validation must start with one of the given values.
     */
    public static function startsWith(array $values): string
    {
        return self::generate('starts_with', $values);
    }

    /**
     * The field under validation must be a string.
     * If you would like to allow the field to also be null, you should assign the nullable rule to the field.
     */
    public static function string(): string
    {
        return self::generate('string');
    }

    /**
     * The field under validation must be a valid timezone identifier according to the timezone_identifiers_list PHP function.
     */
    public static function timezone(): string
    {
        return self::generate('timezone');
    }

    /**
     * The field under validation must be a valid URL.
     */
    public static function url(): string
    {
        return self::generate('url');
    }

    /**
     * The field under validation must be a valid RFC 4122 (version 1, 3, 4, or 5) universally unique identifier (UUID).
     */
    public static function uuid(): string
    {
        return self::generate('uuid');
    }
}
