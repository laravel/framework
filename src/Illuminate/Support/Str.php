<?php namespace Illuminate\Support;

class Str {

	/**
	 * Transliterate a UTF-8 value to ASCII.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function ascii($value)
	{
		return \Patchwork\Utf8::toAscii($value);
	}

	/**
	 * Convert a value to camel case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function camel($value)
	{
		$value = ucwords(str_replace(array('-', '_'), ' ', $value));

		return str_replace(' ', '', $value);
	}

	/**
	 * Determine if a given string contains a given sub-string.
	 *
	 * @param  string        $haystack
	 * @param  string|array  $needle
	 * @return bool
	 */
	public static function contains($haystack, $needle)
	{
		foreach ((array) $needle as $n)
		{
			if (strpos($haystack, $n) !== false) return true;
		}

		return false;
	}

	/**
	 * Determine if a given string ends with a given needle.
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function endsWith($haystack, $needle)
	{
		return $needle == substr($haystack, strlen($haystack) - strlen($needle));
	}

	/**
	 * Cap a string with a single instance of a given value.
	 *
	 * @param  string  $value
	 * @param  string  $cap
	 * @return string
	 */
	public static function finish($value, $cap)
	{
		return rtrim($value, $cap).$cap;
	}

	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param  string  $pattern
	 * @param  string  $value
	 * @return bool
	 */
	public static function is($pattern, $value)
	{
		// Asterisks are translated into zero-or-more regular expression wildcards
		// to make it convenient to check if the strings starts with the given
		// pattern such as "library/*", making any string check convenient.
		if ($pattern !== '/')
		{
			$pattern = str_replace('*', '(.*)', $pattern).'\z';
		}
		else
		{
			$pattern = '/$';
		}

		return (bool) preg_match('#^'.$pattern.'#', $value);
	}

	/**
	 * Limit the number of characters in a string.
	 *
	 * @param  string  $value
	 * @param  int     $limit
	 * @param  string  $end
	 * @return string
	 */
	public static function limit($value, $limit = 100, $end = '...')
	{
		if (static::length($value) <= $limit) return $value;

		return mb_substr($value, 0, $limit, 'UTF-8').$end;
	}

	/**
	 * Get the plural form of an English word.
	 *
	 * @param  string  $value
	 * @param  int  $count
	 * @return string
	 */
	public static function plural($value, $count = 2)
	{
		return Pluralizer::plural($value, $count);
	}

	/**
	 * Get the singular form of an English word.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public static function singular($value)
	{
		return Pluralizer::singular($value);
	}

	/**
	 * Generate a URL friendly "slug" from a given string.
	 *
	 * @param  string  $title
	 * @param  string  $separator
	 * @return string
	 */
	public static function slug($title, $separator = '-')
	{
		$title = static::ascii($title);

		// Remove all characters that are not the separator, letters, numbers, or whitespace.
		$title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));

		// Replace all separator characters and whitespace by a single separator
		$title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

		return trim($title, $separator);
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param  string  $value
	 * @param  string  $delimiter
	 * @return string
	 */
	public static function snake($value, $delimiter = '_')
	{
		return trim(preg_replace_callback('/[A-Z]/', function($match) use ($delimiter)
		{
			return $delimiter.strtolower($match[0]);

		}, $value), $delimiter);
	}

	/**
	 * Determine if a string starts with a given needle.
	 *
	 * @param  string  $haystack
	 * @param  string|array  $needle
	 * @return bool
	 */
	public static function startsWith($haystack, $needles)
	{
		foreach ((array) $needles as $needle)
		{
			if (strpos($haystack, $needle) === 0) return true;
		}

		return false;
	}

}