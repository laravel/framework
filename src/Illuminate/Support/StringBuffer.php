<?php

namespace Illuminate\Support;

use ArrayAccess;
use InvalidArgumentException;

class StringBuffer implements ArrayAccess
{
    /**
     * The string wrapped with the StringBuffer.
     *
     * @var string
     */
    protected $string;

    /**
     * Default encoding for multi-byte strings.
     *
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * Create a new StringBuffer.
     *
     * @param  mixed  $string
     */
    public function __construct($string)
    {
        if (is_array($string)) {
            throw new InvalidArgumentException('Cannot create a string from an array');
        }

        if (is_object($string) && ! method_exists($string, '__toString')) {
            throw new InvalidArgumentException('Cannot create string from an object that does not implement __toString');
        }

        $this->string = (string) $string;
    }

    /**
     * Capitalize the first letter of the string.
     *
     * @return $this|static
     */
    public function ucfirst()
    {
        return new static(Str::ucfirst($this->string));
    }

    /**
     * Lowercase the first letter of the string.
     *
     * @return $this|static
     */
    public function lcfirst()
    {
        if (! $this->length()) {
            return $this;
        }

        return new static(mb_strtolower($this[0], $this->encoding).$this->substring(1));
    }

    /**
     * Determine if the string starts with a given substring.
     *
     * @param  string|array  $needles
     * @return bool
     */
    public function startsWith($needles)
    {
        return Str::startsWith($this->string, $needles);
    }

    /**
     * Determine if the string ends with a given substring.
     *
     * @param  string|array  $needles
     * @return bool
     */
    public function endsWith($needles)
    {
        return Str::endsWith($this->string, $needles);
    }

    /**
     * Determine if the string contains a given substring.
     *
     * @param  string|array  $needles
     * @return bool
     */
    public function contains($needles)
    {
        return Str::contains($this->string, $needles);
    }

    /**
     * Determine if the string equals the given input.
     *
     * @param  string  $input
     * @return bool
     */
    public function equals($input)
    {
        return Str::equals($this->string, $input);
    }

    /**
     * Determine if the string matches a given pattern.
     *
     * @param  string  $pattern
     * @return bool
     */
    public function matches($pattern)
    {
        return Str::is($pattern, $this->string);
    }

    /**
     * Split the string with a given delimiter.
     *
     * @param  string|array  $delimiters
     * @return \Illuminate\Support\Collection
     */
    public function explode($delimiters)
    {
        $delimiters = array_map(function ($delimiter) {
            return preg_quote($delimiter, '/');
        }, (array) $delimiters);

        $strings = preg_split('/('.implode('|', $delimiters).')/', $this->string);

        return collect($strings);
    }

    /**
     * Find the first occurrence of a given needle in the string.
     *
     * @param  string $needle
     * @param  int  $offset
     * @return int
     */
    public function indexOf($needle, $offset = 0)
    {
        return mb_strpos($this->string, $needle, $offset, $this->encoding);
    }

    /**
     * Find the last occurrence of a given needle in the string.
     *
     * @param  string $needle
     * @param  int  $offset
     * @return int
     */
    public function lastIndexOf($needle, $offset = 0)
    {
        return mb_strrpos($this->string, $needle, $offset, $this->encoding);
    }

    /**
     * Replace all occurrences of the search string with the replacement string.
     *
     * @param  string|array  $search
     * @param  string|array  $replace
     * @param  int  $count
     * @param  int  $index
     * @return static
     */
    public function replace($search, $replace, &$count = 0, $index = 0)
    {
        $string = new static($this->string);

        if (is_array($search)) {
            foreach ($search as $char) {
                $string = $string->replace($char, $replace, $count, $index++);
            }

            return $string;
        }

        if (is_array($replace)) {
            $replace = isset($replace[$index]) ? $replace[$index] : $replace[count($replace) - 1];
        }

        while (($pos = $string->indexOf($search)) !== false) {
            $count++;

            $string = $string->substring(0, $pos)->append($replace)
                ->append($string->substring($pos + mb_strlen($search, $this->encoding)));
        }

        return $string;
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param  int  $start
     * @param  int|null  $length
     * @return static
     */
    public function substring($start, $length = null)
    {
        return new static(Str::substr($this->string, $start, $length));
    }

    /**
     * Transliterate a UTF-8 value to ASCII.
     *
     * @return static
     */
    public function toAscii()
    {
        return new static(Str::ascii($this->string));
    }

    /**
     * Convert a value to camel case.
     *
     * @return static
     */
    public function toCamel()
    {
        return new static(Str::camel($this->string));
    }

    /**
     * Convert the given string to lower-case.
     *
     * @return static
     */
    public function toLower()
    {
        return new static(Str::lower($this->string));
    }

    /**
     * Convert a string to snake case.
     *
     * @return static
     */
    public function toSnake()
    {
        return new static(Str::snake($this->string));
    }

    /**
     * Convert a value to studly caps case.
     *
     * @return static
     */
    public function toStudly()
    {
        return new static(Str::studly($this->string));
    }

    /**
     * Convert the given string to title case.
     *
     * @return static
     */
    public function toTitle()
    {
        return new static(Str::title($this->string));
    }

    /**
     * Convert the given string to upper-case.
     *
     * @return static
     */
    public function toUpper()
    {
        return new static(Str::upper($this->string));
    }

    /**
     * Get the plural form of an English word.
     *
     * @return static
     */
    public function toPlural()
    {
        return new static(Str::plural($this->string));
    }

    /**
     * Get the singular form of an English word.
     *
     * @return static
     */
    public function toSingular()
    {
        return new static(Str::singular($this->string));
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @return static
     */
    public function toSlug()
    {
        return new static(Str::slug($this->string));
    }

    /**
     * Return the length of the given string.
     *
     * @return int
     */
    public function length()
    {
        return Str::length($this->string);
    }

    /**
     * Return a Collection of individual words in the string.
     *
     * @return \Illuminate\Support\Collection
     */
    public function words()
    {
        $words = preg_split('/[\s,.;?!:-]+/', $this->string);

        return (new Collection($words))->filter()->values();
    }

    /**
     * Return a collection of individual lines in the string.
     *
     * @return \Illuminate\Support\Collection
     */
    public function lines()
    {
        $lines = preg_split('/\r\n|\n|\r/', $this->string);

        return (new Collection($lines));
    }

    /**
     * Prepend a given input to the string.
     *
     * @param  string  $string
     * @return static
     */
    public function prepend($string)
    {
        return new static($string.$this->string);
    }

    /**
     * Append a given input to the string.
     *
     * @param  string  $string
     * @return static
     */
    public function append($string)
    {
        return new static($this->string.$string);
    }

    /**
     * Trim given characters from both ends of the string.
     *
     * @param  string  $chars
     * @return static
     */
    public function trim($chars = null)
    {
        if (is_null($chars)) {
            return new static(trim($this->string));
        }

        $charList = preg_quote($chars, '/');

        return new static(preg_replace("/(^[$charList]+)|([$charList]+$)/us", '', $this->string));
    }

    /**
     * Trim given characters from the left end of the string.
     *
     * @param  string  $chars
     * @return static
     */
    public function ltrim($chars = null)
    {
        if (is_null($chars)) {
            return new static(ltrim($this->string));
        }

        $charList = preg_quote($chars, '/');

        return new static(preg_replace("/(^[$charList]+)/us", '', $this->string));
    }

    /**
     * Trim given characters from the left end of the string.
     *
     * @param  string  $chars
     * @return static
     */
    public function rtrim($chars = null)
    {
        if (is_null($chars)) {
            return new static(rtrim($this->string));
        }

        $charList = preg_quote($chars, '/');

        return new static(preg_replace("/([$charList]+$)/us", '', $this->string));
    }

    /**
     * Limit the number of characters in the string.
     *
     * @param  int  $limit
     * @param  string  $end
     * @return static
     */
    public function limit($limit = 100, $end = '...')
    {
        return new static(Str::limit($this->string, $limit, $end));
    }

    /**
     * Limit the number of words in the string.
     *
     * @param  int  $words
     * @param  string  $end
     * @return static
     */
    public function limitWords($words = 100, $end = '...')
    {
        return new static(Str::words($this->string, $words, $end));
    }

    /**
     * Return the word at the given index.
     *
     * @param  int  $index
     * @return static
     */
    public function wordAt($index)
    {
        $words = $this->words();

        return isset($words[$index]) ? new static($words[$index]) : new static('');
    }

    /**
     * Determine if a character exists at the given offset.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return is_numeric($offset) && ($offset < mb_strlen($this->string, $this->encoding));
    }

    /**
     * Return the character at the given offset.
     *
     * @param  mixed  $offset
     * @return string
     */
    public function offsetGet($offset)
    {
        return mb_substr($this->string, $offset, 1, $this->encoding);
    }

    /**
     * Replace a character at the given offset with the given value.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $start = mb_substr($this->string, 0, $offset, $this->encoding);
        $end = mb_substr($this->string, $offset + 1, null, $this->encoding);

        $this->string = $start.$value.$end;
    }

    /**
     * Remove the character at the given offset.
     *
     * @param  mixed  $offset
     */
    public function offsetUnset($offset)
    {
        $start = mb_substr($this->string, 0, $offset, $this->encoding);
        $end = mb_substr($this->string, $offset + 1, null, $this->encoding);

        $this->string = $start.$end;
    }

    /**
     * Return the unwrapped string.
     *
     * @return string
     */
    public function get()
    {
        return $this->string;
    }

    /**
     * Cast to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}
