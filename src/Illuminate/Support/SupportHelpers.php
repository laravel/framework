<?php

namespace Illuminate\Support;

use Closure;
use Laravel;
use Exception;
use PhpOption\Option;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Optional;
use Illuminate\Support\Collection;
use Dotenv\Environment\DotenvFactory;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HigherOrderTapProxy;
use Dotenv\Environment\Adapter\PutenvAdapter;
use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;

trait SupportHelpers
{
    /**
     * Assign high numeric IDs to a config item to force appending.
     *
     * @param  array  $array
     * @return array
     */
    public static function appendConfig(array $array)
    {
        $start = 9999;

        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $start++;

                $array[$start] = Arr::pull($array, $key);
            }
        }

        return $array;
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
    public static function arrayAdd($array, $key, $value)
    {
        return Arr::add($array, $key, $value);
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     *
     * @deprecated Arr::collapse() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function arrayCollapse($array)
    {
        return Arr::collapse($array);
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param  array  $array
     * @return array
     *
     * @deprecated Arr::divide() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function arrayDivide($array)
    {
        return Arr::divide($array);
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
    public static function arrayDot($array, $prepend = '')
    {
        return Arr::dot($array, $prepend);
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
    public static function arrayExcept($array, $keys)
    {
        return Arr::except($array, $keys);
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
    public static function arrayFirst($array, callable $callback = null, $default = null)
    {
        return Arr::first($array, $callback, $default);
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
    public static function arrayFlatten($array, $depth = INF)
    {
        return Arr::flatten($array, $depth);
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
    public static function arrayForget(&$array, $keys)
    {
        return Arr::forget($array, $keys);
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
    public static function arrayGet($array, $key, $default = null)
    {
        return Arr::get($array, $key, $default);
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
    public static function arrayHas($array, $keys)
    {
        return Arr::has($array, $keys);
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
    public static function arrayLast($array, callable $callback = null, $default = null)
    {
        return Arr::last($array, $callback, $default);
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
    public static function arrayOnly($array, $keys)
    {
        return Arr::only($array, $keys);
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
    public static function arrayPluck($array, $value, $key = null)
    {
        return Arr::pluck($array, $value, $key);
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
    public static function arrayPrepend($array, $value, $key = null)
    {
        return Arr::prepend($array, $value, $key);
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
    public static function arrayPull(&$array, $key, $default = null)
    {
        return Arr::pull($array, $key, $default);
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
    public static function arrayRandom($array, $num = null)
    {
        return Arr::random($array, $num);
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
    public static function arraySet(&$array, $key, $value)
    {
        return Arr::set($array, $key, $value);
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
    public static function arraySort($array, $callback = null)
    {
        return Arr::sort($array, $callback);
    }

    /**
     * Recursively sort an array by keys and values.
     *
     * @param  array  $array
     * @return array
     *
     * @deprecated Arr::sortRecursive() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function arraySortRecursive($array)
    {
        return Arr::sortRecursive($array);
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
    public static function arrayWhere($array, callable $callback)
    {
        return Arr::where($array, $callback);
    }

    /**
     * If the given value is not an array, wrap it in one.
     *
     * @param  mixed  $value
     * @return array
     *
     * @deprecated Arr::wrap() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function arrayWrap($value)
    {
        return Arr::wrap($value);
    }

    /**
     * Determine if the given value is "blank".
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function blank($value)
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }

    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     *
     * @deprecated Str::camel() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function camelCase($value)
    {
        return Str::camel($value);
    }

    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object  $class
     * @return string
     */
    public static function classBasename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param  object|string  $class
     * @return array
     */
    public static function classUsesRecursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += static::traitUsesRecursive($class);
        }

        return array_unique($results);
    }

    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection
     */
    public static function collect($value = null)
    {
        return new Collection($value);
    }

    /**
     * Fill in data where it's missing.
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @return mixed
     */
    public static function dataFill(&$target, $key, $value)
    {
        return static::dataSet($target, $key, $value, false);
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array|int  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function dataGet($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
                    return static::value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = static::dataGet($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return static::value($default);
            }
        }

        return $target;
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
    public static function dataSet(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (! Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    static::dataSet($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (! Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                static::dataSet($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                static::dataSet($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                static::dataSet($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }

    /**
     * Encode HTML special characters in a string.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string  $value
     * @param  bool  $doubleEncode
     * @return string
     */
    public static function e($value, $doubleEncode = true)
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
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
    public static function endsWith($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function env($key, $default = null)
    {
        static $variables;

        if ($variables === null) {
            $variables = (new DotenvFactory([new EnvConstAdapter, new PutenvAdapter, new ServerConstAdapter]))->createImmutable();
        }

        return Option::fromValue($variables->get($key))
            ->map(function ($value) {
                switch (strtolower($value)) {
                    case 'true':
                    case '(true)':
                        return true;
                    case 'false':
                    case '(false)':
                        return false;
                    case 'empty':
                    case '(empty)':
                        return '';
                    case 'null':
                    case '(null)':
                        return;
                }

                if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
                    return $matches[2];
                }

                return $value;
            })
            ->getOrCall(function () use ($default) {
                return static::value($default);
            });
    }

    /**
     * Determine if a value is "filled".
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function filled($value)
    {
        return ! static::blank($value);
    }

    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * @param  array  $array
     * @return mixed
     */
    public static function head($array)
    {
        return reset($array);
    }

    /**
     * Convert a string to kebab case.
     *
     * @param  string  $value
     * @return string
     *
     * @deprecated Str::kebab() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function kebabCase($value)
    {
        return Str::kebab($value);
    }

    /**
     * Get the last element from an array.
     *
     * @param  array  $array
     * @return mixed
     */
    public static function last($array)
    {
        return end($array);
    }

    /**
     * Get an item from an object using "dot" notation.
     *
     * @param  object  $object
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function objectGet($object, $key, $default = null)
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_object($object) || ! isset($object->{$segment})) {
                return static::value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }

    /**
     * Provide access to optional objects.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    public static function optional($value = null, callable $callback = null)
    {
        if (is_null($callback)) {
            return new Optional($value);
        } elseif (! is_null($value)) {
            return $callback($value);
        }
    }

    /**
     * Replace a given pattern with each value in the array in sequentially.
     *
     * @param  string  $pattern
     * @param  array   $replacements
     * @param  string  $subject
     * @return string
     */
    public static function pregReplaceArray($pattern, array $replacements, $subject)
    {
        return preg_replace_callback($pattern, function () use (&$replacements) {
            foreach ($replacements as $key => $value) {
                return array_shift($replacements);
            }
        }, $subject);
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
    public static function retry($times, callable $callback, $sleep = 0)
    {
        $attempts = 0;
        $times--;

        beginning:
        $attempts++;

        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if (! $times) {
                throw $e;
            }

            $times--;

            if ($sleep) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
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
    public static function snakeCase($value, $delimiter = '_')
    {
        return Str::snake($value, $delimiter);
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
    public static function startsWith($haystack, $needles)
    {
        return Str::startsWith($haystack, $needles);
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
    public static function strAfter($subject, $search)
    {
        return Str::after($subject, $search);
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
    public static function strBefore($subject, $search)
    {
        return Str::before($subject, $search);
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
    public static function strContains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
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
    public static function strFinish($value, $cap)
    {
        return Str::finish($value, $cap);
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
    public static function strIs($pattern, $value)
    {
        return Str::is($pattern, $value);
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
    public static function strLimit($value, $limit = 100, $end = '...')
    {
        return Str::limit($value, $limit, $end);
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
    public static function strPlural($value, $count = 2)
    {
        return Str::plural($value, $count);
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
    public static function strRandom($length = 16)
    {
        return Str::random($length);
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
    public static function strReplaceArray($search, array $replace, $subject)
    {
        return Str::replaceArray($search, $replace, $subject);
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
    public static function strReplaceFirst($search, $replace, $subject)
    {
        return Str::replaceFirst($search, $replace, $subject);
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
    public static function strReplaceLast($search, $replace, $subject)
    {
        return Str::replaceLast($search, $replace, $subject);
    }

    /**
     * Get the singular form of an English word.
     *
     * @param  string  $value
     * @return string
     *
     * @deprecated Str::singular() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function strSingular($value)
    {
        return Str::singular($value);
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
    public static function strSlug($title, $separator = '-', $language = 'en')
    {
        return Str::slug($title, $separator, $language);
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
    public static function strStart($value, $prefix)
    {
        return Str::start($value, $prefix);
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     *
     * @deprecated Str::studly() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function studlyCase($value)
    {
        return Str::studly($value);
    }

    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    public static function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
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
    public static function throwIf($condition, $exception, ...$parameters)
    {
        if ($condition) {
            throw (is_string($exception) ? new $exception(...$parameters) : $exception);
        }

        return $condition;
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
    public static function throwUnless($condition, $exception, ...$parameters)
    {
        if (! $condition) {
            throw (is_string($exception) ? new $exception(...$parameters) : $exception);
        }

        return $condition;
    }

    /**
     * Convert a value to title case.
     *
     * @param  string  $value
     * @return string
     *
     * @deprecated Str::title() should be used directly instead. Will be removed in Laravel 5.9.
     */
    public static function titleCase($value)
    {
        return Str::title($value);
    }

    /**
     * Returns all traits used by a trait and its traits.
     *
     * @param  string  $trait
     * @return array
     */
    public static function traitUsesRecursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += static::traitUsesRecursive($trait);
        }

        return $traits;
    }

    /**
     * Transform the given value if it is present.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed|null
     */
    public static function transform($value, callable $callback, $default = null)
    {
        if (static::filled($value)) {
            return $callback($value);
        }

        if (is_callable($default)) {
            return $default($value);
        }

        return $default;
    }

    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    public static function windowsOs()
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }

    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    public static function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }

}