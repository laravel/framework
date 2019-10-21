<?php

namespace Illuminate\Support;

/**
 * Class StrProxy
 *
 * @method \Illuminate\Support\StrProxy after(string $search)
 * @method \Illuminate\Support\StrProxy ascii(string $language = 'en')
 * @method \Illuminate\Support\StrProxy before(string $search)
 * @method \Illuminate\Support\StrProxy camel()
 * @method \Illuminate\Support\StrProxy finish(string $cap)
 * @method \Illuminate\Support\StrProxy kebab()
 * @method \Illuminate\Support\StrProxy limit(int $limit = 100, string $end = '...')
 * @method \Illuminate\Support\StrProxy lower()
 * @method \Illuminate\Support\StrProxy plural(int $count = 2)
 * @method \Illuminate\Support\StrProxy random(int $length = 16)
 * @method \Illuminate\Support\StrProxy replaceArray(string $search, array $replace)
 * @method \Illuminate\Support\StrProxy replaceFirst(string $search, string $replace)
 * @method \Illuminate\Support\StrProxy replaceLast(string $search, string $replace)
 * @method \Illuminate\Support\StrProxy singular()
 * @method \Illuminate\Support\StrProxy slug(string $separator = '-', string $language = 'en')
 * @method \Illuminate\Support\StrProxy snake(string $delimiter = '_')
 * @method \Illuminate\Support\StrProxy start(string $prefix)
 * @method \Illuminate\Support\StrProxy studly()
 * @method \Illuminate\Support\StrProxy substr(int $start, int $length = null)
 * @method \Illuminate\Support\StrProxy title()
 * @method \Illuminate\Support\StrProxy ucfirst()
 * @method \Illuminate\Support\StrProxy upper()
 * @method \Illuminate\Support\StrProxy words(int $words = 100, string $end = '...')
 * @method \Ramsey\Uuid\UuidInterface orderedUuid()
 * @method \Ramsey\Uuid\UuidInterface uuid()
 * @method array parseCallback(string $default = null)
 * @method bool contains(array|string $needles)
 * @method bool containsAll(array $needles)
 * @method bool endsWith(array|string $needles)
 * @method bool is(array|string $pattern)
 * @method bool startsWith(array|string $needles)
 * @method int length(string $encoding = null)
 */
class StrProxy
{
    protected const METHOD_IS = 'is';
    protected const METHOD_RANDOM = 'random';
    protected const METHOD_REPLACE_ARRAY = 'replaceArray';
    protected const METHOD_REPLACE_FIRST = 'replaceFirst';
    protected const METHOD_REPLACE_LAST = 'replaceLast';

    protected $text = '';

    /**
     * Create a string proxy instance.
     *
     * @param  string  $text
     * @return void
     */
    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    /**
     * Dynamically pass a method to the \Illuminate\Support\Str object.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return array|bool|int|self
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $parameters = $this->constructorParameterShouldBeOmitted($name)
            ? $arguments
            : array_merge([$this->text], $arguments);

        if ($this->parametersShouldBeReversed($name)) {
            $parameters = array_reverse($parameters);
        }

        if ($this->constructorParameterShouldBePushedToEnd($name)) {
            $parameters[] = $this->text;
        }

        $this->text = Str::{$name}(...$parameters);

        if ($this->shouldReturnTextValue()) {
            return $this->text;
        }

        if (! is_string($this->text)) {
            throw new \Exception('Invalid change made to the text.');
        }

        return $this;
    }

    /**
     * Convert the object to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->text;
    }

    /**
     * Die and dump the current text state.
     *
     * @return void
     */
    public function dd(): void
    {
        dd($this->text);
    }

    /**
     * Get the current text state.
     *
     * @return string
     */
    public function get(): string
    {
        return $this->text;
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return Str::{$method}(...$parameters);
    }

    /**
     * Check if constructor parameter should be omitted in order to
     * match the underlying \Illuminate\Support\Str implementation.
     *
     * @param  string  $name
     * @return bool
     */
    protected function constructorParameterShouldBeOmitted(string $name): bool
    {
        return in_array($name, [
            static::METHOD_RANDOM,
            static::METHOD_REPLACE_ARRAY,
            static::METHOD_REPLACE_FIRST,
            static::METHOD_REPLACE_LAST,
        ]);
    }

    /**
     * Check if constructor parameter should be pushed to the end in order
     * to match the underlying \Illuminate\Support\Str implementation.
     *
     * @param string $name
     * @return bool
     */
    protected function constructorParameterShouldBePushedToEnd(string $name): bool
    {
        return in_array($name, [
            static::METHOD_REPLACE_ARRAY,
            static::METHOD_REPLACE_FIRST,
            static::METHOD_REPLACE_LAST,
        ]);
    }

    /**
     * Check if constructor parameter should be reversed in order to
     * match the underlying \Illuminate\Support\Str implementation.
     *
     * @param string $name
     * @return bool
     */
    protected function parametersShouldBeReversed(string $name): bool
    {
        return in_array($name, [
            static::METHOD_IS,
        ]);
    }

    /**
     * Check if text property should be returned.
     *
     * @return bool
     */
    protected function shouldReturnTextValue(): bool
    {
        if (is_array($this->text)) {
            return true;
        }

        if (is_bool($this->text)) {
            return true;
        }

        if (is_int($this->text)) {
            return true;
        }

        if (is_object($this->text)) {
            return true;
        }

        return false;
    }
}
