<?php

/**
 * Assign high numeric IDs to a config item to force appending.
 *
 * @param  array  $array
 * @return array
 */
function append_config(array $array)
{
    return Laravel::appendConfig($array);
}

/**
 * Add an element to an array using "dot" notation if it doesn't exist.
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $value
 * @return array
 *
 * @deprecated Arr::add() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_add($array, $key, $value)
{
    return Laravel::arrayAdd($array, $key, $value);
}

/**
 * Collapse an array of arrays into a single array.
 *
 * @param  array  $array
 * @return array
 *
 * @deprecated Arr::collapse() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_collapse($array)
{
    return Laravel::arrayCollapse($array);
}

/**
 * Divide an array into two arrays. One with keys and the other with values.
 *
 * @param  array  $array
 * @return array
 *
 * @deprecated Arr::divide() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_divide($array)
{
    return Laravel::arrayDivide($array);
}

/**
 * Flatten a multi-dimensional associative array with dots.
 *
 * @param  array   $array
 * @param  string  $prepend
 * @return array
 *
 * @deprecated Arr::dot() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_dot($array, $prepend = '')
{
    return Laravel::arrayDot($array, $prepend);
}

/**
 * Get all of the given array except for a specified array of keys.
 *
 * @param  array  $array
 * @param  array|string  $keys
 * @return array
 *
 * @deprecated Arr::except() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_except($array, $keys)
{
    return Laravel::arrayExcept($array, $keys);
}

/**
 * Return the first element in an array passing a given truth test.
 *
 * @param  array  $array
 * @param  callable|null  $callback
 * @param  mixed  $default
 * @return mixed
 *
 * @deprecated Arr::first() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_first($array, callable $callback = null, $default = null)
{
    return Laravel::arrayFirst($array, $callback, $default);
}

/**
 * Flatten a multi-dimensional array into a single level.
 *
 * @param  array  $array
 * @param  int  $depth
 * @return array
 *
 * @deprecated Arr::flatten() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_flatten($array, $depth = INF)
{
    return Laravel::arrayFlatten($array, $depth);
}

/**
 * Remove one or many array items from a given array using "dot" notation.
 *
 * @param  array  $array
 * @param  array|string  $keys
 * @return void
 *
 * @deprecated Arr::forget() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_forget(&$array, $keys)
{
    return Laravel::arrayForget($array, $keys);
}

/**
 * Get an item from an array using "dot" notation.
 *
 * @param  \ArrayAccess|array  $array
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 *
 * @deprecated Arr::get() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_get($array, $key, $default = null)
{
    return Laravel::arrayGet($array, $key, $default);
}

/**
 * Check if an item or items exist in an array using "dot" notation.
 *
 * @param  \ArrayAccess|array  $array
 * @param  string|array  $keys
 * @return bool
 *
 * @deprecated Arr::has() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_has($array, $keys)
{
    return Laravel::arrayHas($array, $keys);
}

/**
 * Return the last element in an array passing a given truth test.
 *
 * @param  array  $array
 * @param  callable|null  $callback
 * @param  mixed  $default
 * @return mixed
 *
 * @deprecated Arr::last() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_last($array, callable $callback = null, $default = null)
{
    return Laravel::arrayLast($array, $callback, $default);
}

/**
 * Get a subset of the items from the given array.
 *
 * @param  array  $array
 * @param  array|string  $keys
 * @return array
 *
 * @deprecated Arr::only() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_only($array, $keys)
{
    return Laravel::arrayOnly($array, $keys);
}

/**
 * Pluck an array of values from an array.
 *
 * @param  array   $array
 * @param  string|array  $value
 * @param  string|array|null  $key
 * @return array
 *
 * @deprecated Arr::pluck() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_pluck($array, $value, $key = null)
{
    return Laravel::arrayPluck($array, $value, $key);
}

/**
 * Push an item onto the beginning of an array.
 *
 * @param  array  $array
 * @param  mixed  $value
 * @param  mixed  $key
 * @return array
 *
 * @deprecated Arr::prepend() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_prepend($array, $value, $key = null)
{
    return Laravel::arrayPrepend($array, $value, $key);
}

/**
 * Get a value from the array, and remove it.
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 *
 * @deprecated Arr::pull() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_pull(&$array, $key, $default = null)
{
    return Laravel::arrayPull($array, $key, $default);
}

/**
 * Get a random value from an array.
 *
 * @param  array  $array
 * @param  int|null  $num
 * @return mixed
 *
 * @deprecated Arr::random() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_random($array, $num = null)
{
    return Laravel::arrayRandom($array, $num);
}

/**
 * Set an array item to a given value using "dot" notation.
 *
 * If no key is given to the method, the entire array will be replaced.
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $value
 * @return array
 *
 * @deprecated Arr::set() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_set(&$array, $key, $value)
{
    return Laravel::arraySet($array, $key, $value);
}

/**
 * Sort the array by the given callback or attribute name.
 *
 * @param  array  $array
 * @param  callable|string|null  $callback
 * @return array
 *
 * @deprecated Arr::sort() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_sort($array, $callback = null)
{
    return Laravel::arraySort($array, $callback);
}

/**
 * Recursively sort an array by keys and values.
 *
 * @param  array  $array
 * @return array
 *
 * @deprecated Arr::sortRecursive() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_sort_recursive($array)
{
    return Laravel::arraySortRecursive($array);
}

/**
 * Filter the array using the given callback.
 *
 * @param  array  $array
 * @param  callable  $callback
 * @return array
 *
 * @deprecated Arr::where() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_where($array, callable $callback)
{
    return Laravel::arrayWhere($array, $callback);
}

/**
 * If the given value is not an array, wrap it in one.
 *
 * @param  mixed  $value
 * @return array
 *
 * @deprecated Arr::wrap() should be used directly instead. Will be removed in Laravel 5.9.
 */
function array_wrap($value)
{
    return Laravel::arrayWrap($value);
}

/**
 * Determine if the given value is "blank".
 *
 * @param  mixed  $value
 * @return bool
 */
function blank($value)
{
    return Laravel::blank($value);
}

/**
 * Convert a value to camel case.
 *
 * @param  string  $value
 * @return string
 *
 * @deprecated Str::camel() should be used directly instead. Will be removed in Laravel 5.9.
 */
function camel_case($value)
{
    return Laravel::camelCase($value);
}

/**
 * Get the class "basename" of the given object / class.
 *
 * @param  string|object  $class
 * @return string
 */
function class_basename($class)
{
    return Laravel::classBasename($class);
}

/**
 * Returns all traits used by a class, its parent classes and trait of their traits.
 *
 * @param  object|string  $class
 * @return array
 */
function class_uses_recursive($class)
{
    return Laravel::classUsesRecursive($class);
}

/**
 * Create a collection from the given value.
 *
 * @param  mixed  $value
 * @return \Illuminate\Support\Collection
 */
function collect($value = null)
{
    return Laravel::collect($value);
}

/**
 * Fill in data where it's missing.
 *
 * @param  mixed   $target
 * @param  string|array  $key
 * @param  mixed  $value
 * @return mixed
 */
function data_fill(&$target, $key, $value)
{
    return Laravel::dataFill($target, $key, $value);
}

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param  mixed   $target
 * @param  string|array|int  $key
 * @param  mixed   $default
 * @return mixed
 */
function data_get($target, $key, $default = null)
{
    return Laravel::dataGet($target, $key, $default);
}

/**
 * Set an item on an array or object using dot notation.
 *
 * @param  mixed  $target
 * @param  string|array  $key
 * @param  mixed  $value
 * @param  bool  $overwrite
 * @return mixed
 */
function data_set(&$target, $key, $value, $overwrite = true)
{
    return Laravel::dataSet($target, $key, $value, $overwrite);
}

/**
 * Encode HTML special characters in a string.
 *
 * @param  \Illuminate\Contracts\Support\Htmlable|string  $value
 * @param  bool  $doubleEncode
 * @return string
 */
function e($value, $doubleEncode = true)
{
    return Laravel::e($value, $doubleEncode);
}

/**
 * Determine if a given string ends with a given substring.
 *
 * @param  string  $haystack
 * @param  string|array  $needles
 * @return bool
 *
 * @deprecated Str::endsWith() should be used directly instead. Will be removed in Laravel 5.9.
 */
function ends_with($haystack, $needles)
{
    return Laravel::endsWith($haystack, $needles);
}

/**
 * Gets the value of an environment variable.
 *
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function env($key, $default = null)
{
    return Laravel::env($key, $default);
}

/**
 * Determine if a value is "filled".
 *
 * @param  mixed  $value
 * @return bool
 */
function filled($value)
{
    return Laravel::filled($value);
}

/**
 * Get the first element of an array. Useful for method chaining.
 *
 * @param  array  $array
 * @return mixed
 */
function head($array)
{
    return Laravel::head($array);
}

/**
 * Convert a string to kebab case.
 *
 * @param  string  $value
 * @return string
 *
 * @deprecated Str::kebab() should be used directly instead. Will be removed in Laravel 5.9.
 */
function kebab_case($value)
{
    return Laravel::kebabCase($value);
}

/**
 * Get the last element from an array.
 *
 * @param  array  $array
 * @return mixed
 */
function last($array)
{
    return Laravel::last($array);
}

/**
 * Get an item from an object using "dot" notation.
 *
 * @param  object  $object
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function object_get($object, $key, $default = null)
{
    return Laravel::objectGet($object, $key, $default);
}

/**
 * Provide access to optional objects.
 *
 * @param  mixed  $value
 * @param  callable|null  $callback
 * @return mixed
 */
function optional($value = null, callable $callback = null)
{
    return Laravel::optional($value, $callback);
}

/**
 * Replace a given pattern with each value in the array in sequentially.
 *
 * @param  string  $pattern
 * @param  array   $replacements
 * @param  string  $subject
 * @return string
 */
function preg_replace_array($pattern, array $replacements, $subject)
{
    return Laravel::pregReplaceArray($pattern, $replacements, $subject);
}

/**
 * Retry an operation a given number of times.
 *
 * @param  int  $times
 * @param  callable  $callback
 * @param  int  $sleep
 * @return mixed
 *
 * @throws \Exception
 */
function retry($times, callable $callback, $sleep = 0)
{
    return Laravel::retry($times, $callback, $sleep);
}

/**
 * Convert a string to snake case.
 *
 * @param  string  $value
 * @param  string  $delimiter
 * @return string
 *
 * @deprecated Str::snake() should be used directly instead. Will be removed in Laravel 5.9.
 */
function snake_case($value, $delimiter = '_')
{
    return Laravel::snakeCase($value, $delimiter);
}

/**
 * Determine if a given string starts with a given substring.
 *
 * @param  string  $haystack
 * @param  string|array  $needles
 * @return bool
 *
 * @deprecated Str::startsWith() should be used directly instead. Will be removed in Laravel 5.9.
 */
function starts_with($haystack, $needles)
{
    return Laravel::startsWith($haystack, $needles);
}

/**
 * Return the remainder of a string after a given value.
 *
 * @param  string  $subject
 * @param  string  $search
 * @return string
 *
 * @deprecated Str::after() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_after($subject, $search)
{
    return Laravel::strAfter($subject, $search);
}

/**
 * Get the portion of a string before a given value.
 *
 * @param  string  $subject
 * @param  string  $search
 * @return string
 *
 * @deprecated Str::before() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_before($subject, $search)
{
    return Laravel::strBefore($subject, $search);
}

/**
 * Determine if a given string contains a given substring.
 *
 * @param  string  $haystack
 * @param  string|array  $needles
 * @return bool
 *
 * @deprecated Str::contains() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_contains($haystack, $needles)
{
    return Laravel::strContains($haystack, $needles);
}

/**
 * Cap a string with a single instance of a given value.
 *
 * @param  string  $value
 * @param  string  $cap
 * @return string
 *
 * @deprecated Str::finish() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_finish($value, $cap)
{
    return Laravel::strFinish($value, $cap);
}

/**
 * Determine if a given string matches a given pattern.
 *
 * @param  string|array  $pattern
 * @param  string  $value
 * @return bool
 *
 * @deprecated Str::is() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_is($pattern, $value)
{
    return Laravel::strIs($pattern, $value);
}

/**
 * Limit the number of characters in a string.
 *
 * @param  string  $value
 * @param  int     $limit
 * @param  string  $end
 * @return string
 *
 * @deprecated Str::limit() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_limit($value, $limit = 100, $end = '...')
{
    return Laravel::strLimit($value, $limit, $end);
}

/**
 * Get the plural form of an English word.
 *
 * @param  string  $value
 * @param  int     $count
 * @return string
 *
 * @deprecated Str::plural() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_plural($value, $count = 2)
{
    return Laravel::strPlural($value, $count);
}

/**
 * Generate a more truly "random" alpha-numeric string.
 *
 * @param  int  $length
 * @return string
 *
 * @throws \RuntimeException
 *
 * @deprecated Str::random() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_random($length = 16)
{
    return Laravel::strRandom($length);
}

/**
 * Replace a given value in the string sequentially with an array.
 *
 * @param  string  $search
 * @param  array   $replace
 * @param  string  $subject
 * @return string
 *
 * @deprecated Str::replaceArray() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_replace_array($search, array $replace, $subject)
{
    return Laravel::strReplaceArray($search, $replace, $subject);
}

/**
 * Replace the first occurrence of a given value in the string.
 *
 * @param  string  $search
 * @param  string  $replace
 * @param  string  $subject
 * @return string
 *
 * @deprecated Str::replaceFirst() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_replace_first($search, $replace, $subject)
{
    return Laravel::strReplaceFirst($search, $replace, $subject);
}

/**
 * Replace the last occurrence of a given value in the string.
 *
 * @param  string  $search
 * @param  string  $replace
 * @param  string  $subject
 * @return string
 *
 * @deprecated Str::replaceLast() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_replace_last($search, $replace, $subject)
{
    return Laravel::strReplaceLast($search, $replace, $subject);
}

/**
 * Get the singular form of an English word.
 *
 * @param  string  $value
 * @return string
 *
 * @deprecated Str::singular() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_singular($value)
{
    return Laravel::strSingular($value);
}

/**
 * Generate a URL friendly "slug" from a given string.
 *
 * @param  string  $title
 * @param  string  $separator
 * @param  string  $language
 * @return string
 *
 * @deprecated Str::slug() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_slug($title, $separator = '-', $language = 'en')
{
    return Laravel::strSlug($title, $separator, $language);
}

/**
 * Begin a string with a single instance of a given value.
 *
 * @param  string  $value
 * @param  string  $prefix
 * @return string
 *
 * @deprecated Str::start() should be used directly instead. Will be removed in Laravel 5.9.
 */
function str_start($value, $prefix)
{
    return Laravel::strStart($value, $prefix);
}

/**
 * Convert a value to studly caps case.
 *
 * @param  string  $value
 * @return string
 *
 * @deprecated Str::studly() should be used directly instead. Will be removed in Laravel 5.9.
 */
function studly_case($value)
{
    return Laravel::studlyCase($value);
}

/**
 * Call the given Closure with the given value then return the value.
 *
 * @param  mixed  $value
 * @param  callable|null  $callback
 * @return mixed
 */
function tap($value, $callback = null)
{
    return Laravel::tap($value, $callback);
}

/**
 * Throw the given exception if the given condition is true.
 *
 * @param  mixed  $condition
 * @param  \Throwable|string  $exception
 * @param  array  ...$parameters
 * @return mixed
 *
 * @throws \Throwable
 */
function throw_if($condition, $exception, ...$parameters)
{
    return Laravel::throwIf($condition, $exception, ...$parameters);
}

/**
 * Throw the given exception unless the given condition is true.
 *
 * @param  mixed  $condition
 * @param  \Throwable|string  $exception
 * @param  array  ...$parameters
 * @return mixed
 * @throws \Throwable
 */
function throw_unless($condition, $exception, ...$parameters)
{
    return Laravel::throwUnless($condition, $exception, ...$parameters);
}

/**
 * Convert a value to title case.
 *
 * @param  string  $value
 * @return string
 *
 * @deprecated Str::title() should be used directly instead. Will be removed in Laravel 5.9.
 */
function title_case($value)
{
    return Laravel::titleCase($value);
}

/**
 * Returns all traits used by a trait and its traits.
 *
 * @param  string  $trait
 * @return array
 */
function trait_uses_recursive($trait)
{
    return Laravel::traitUsesRecursive($trait);
}

/**
 * Transform the given value if it is present.
 *
 * @param  mixed  $value
 * @param  callable  $callback
 * @param  mixed  $default
 * @return mixed|null
 */
function transform($value, callable $callback, $default = null)
{
    return Laravel::transform($value, $callback, $default);
}

/**
 * Return the default value of the given value.
 *
 * @param  mixed  $value
 * @return mixed
 */
function value($value)
{
    return Laravel::value($value);
}

/**
 * Determine whether the current environment is Windows based.
 *
 * @return bool
 */
function windows_os()
{
    return Laravel::windowsOs();
}

/**
 * Return the given value, optionally passed through the given callback.
 *
 * @param  mixed  $value
 * @param  callable|null  $callback
 * @return mixed
 */
function with($value, callable $callback = null)
{
    return Laravel::with($value, $callback);
}
