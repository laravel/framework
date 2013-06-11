<?php

if ( ! function_exists('action'))
{
	/**
	 * Generate a URL to a controller action.
	 *
	 * @param  string  $name
	 * @param  string  $parameters
	 * @param  bool    $absolute
	 * @return string
	 */
	function action($name, $parameters = array(), $absolute = true)
	{
		return app('url')->action($name, $parameters, $absolute);
	}
}

if ( ! function_exists('app'))
{
	/**
	 * Get the root Facade application instance.
	 *
	 * @param  string  $make
	 * @return mixed
	 */
	function app($make = null)
	{
		if ( ! is_null($make))
		{
			return app()->make($make);
		}

		return Illuminate\Support\Facades\Facade::getFacadeApplication();
	}
}

if ( ! function_exists('app_path'))
{
	/**
	 * Get the path to the application folder.
	 *
	 * @return  string
	 */
	function app_path()
	{
		return app('path');
	}
}

if ( ! function_exists('array_add'))
{
	/**
	 * Add an element to an array if it doesn't exist.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	function array_add($array, $key, $value)
	{
		if ( ! isset($array[$key])) $array[$key] = $value;

		return $array;
	}
}

if ( ! function_exists('array_divide'))
{
	/**
	 * Divide an array into two arrays. One with keys and the other with values.
	 *
	 * @param  array  $array
	 * @return array
	 */
	function array_divide($array)
	{
		return array(array_keys($array), array_values($array));
	}
}

if ( ! function_exists('array_dot'))
{
	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @param  array   $array
	 * @param  string  $prepend
	 * @return array
	 */
	function array_dot($array, $prepend = '')
	{
		$results = array();

		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				$results = array_merge($results, array_dot($value, $prepend.$key.'.'));
			}
			else
			{
				$results[$prepend.$key] = $value;
			}
		}

		return $results;
	}
}

if ( ! function_exists('array_except'))
{
	/**
	 * Get all of the given array except for a specified array of items.
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	function array_except($array, $keys)
	{
		return array_diff_key($array, array_flip((array) $keys));
	}
}

if ( ! function_exists('array_fetch'))
{
	/**
	 * Fetch a flattened array of a nested array element.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return array
	 */
	function array_fetch($array, $key)
	{
		foreach (explode('.', $key) as $segment)
		{
			$results = array();

			foreach ($array as $value)
			{
				$value = (array) $value;

				$results[] = $value[$segment];
			}

			$array = array_values($results);
		}

		return array_values($results);
	}
}

if ( ! function_exists('array_first'))
{
	/**
	 * Return the first element in an array passing a given truth test.
	 *
	 * @param  array    $array
	 * @param  Closure  $callback
	 * @param  mixed    $default
	 * @return mixed
	 */
	function array_first($array, $callback, $default = null)
	{
		foreach ($array as $key => $value)
		{
			if (call_user_func($callback, $key, $value)) return $value;
		}

		return value($default);
	}
}

if ( ! function_exists('array_flatten'))
{
	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param  array  $array
	 * @return array
	 */
	function array_flatten($array)
	{
		$return = array();

		array_walk_recursive($array, function($x) use (&$return) { $return[] = $x; });

		return $return;
	}
}

if ( ! function_exists('array_forget'))
{
	/**
	 * Remove an array item from a given array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return void
	 */
	function array_forget(&$array, $key)
	{
		$keys = explode('.', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			if ( ! isset($array[$key]) or ! is_array($array[$key]))
			{
				return;
			}

			$array =& $array[$key];
		}

		unset($array[array_shift($keys)]);
	}
}

if ( ! function_exists('array_get'))
{
	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function array_get($array, $key, $default = null)
	{
		if (is_null($key)) return $array;
		
		if (isset($array[$key])) return $array[$key];

		foreach (explode('.', $key) as $segment)
		{
			if ( ! is_array($array) or ! array_key_exists($segment, $array))
			{
				return value($default);
			}

			$array = $array[$segment];
		}

		return $array;
	}
}

if ( ! function_exists('array_only'))
{
	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	function array_only($array, $keys)
	{
		return array_intersect_key($array, array_flip((array) $keys));
	}
}

if ( ! function_exists('array_pluck'))
{
	/**
	 * Pluck an array of values from an array.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return array
	 */
	function array_pluck($array, $key)
	{
		return array_map(function($value) use ($key)
		{
			return is_object($value) ? $value->$key : $value[$key];

		}, $array);
	}
}

if ( ! function_exists('array_pull'))
{
	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return mixed
	 */
	function array_pull(&$array, $key)
	{
		$value = array_get($array, $key);

		array_forget($array, $key);

		return $value;
	}
}

if ( ! function_exists('array_set'))
{
	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	function array_set(&$array, $key, $value)
	{
		if (is_null($key)) return $array = $value;

		$keys = explode('.', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if ( ! isset($array[$key]) or ! is_array($array[$key]))
			{
				$array[$key] = array();
			}

			$array =& $array[$key];
		}

		$array[array_shift($keys)] = $value;
	}
}

if ( ! function_exists('array_sort'))
{
	/**
	 * Sort the array using the given Closure.
	 *
	 * @param  array  $array
	 * @param  \Closure  $callback
	 * @return array
	 */
	function array_sort($array, Closure $callback)
	{
		return Illuminate\Support\Collection::make($array)->sortBy($callback)->all();
	}
}

if ( ! function_exists('asset'))
{
	/**
	 * Generate an asset path for the application.
	 *
	 * @param  string  $path
	 * @param  bool    $secure
	 * @return string
	 */
	function asset($path, $secure = null)
	{
		return app('url')->asset($path, $secure);
	}
}

if ( ! function_exists('base_path'))
{
	/**
	 * Get the path to the base of the install.
	 *
	 * @return string
	 */
	function base_path()
	{
		return app()->make('path.base');
	}
}

if ( ! function_exists('camel_case'))
{
	/**
	 * Convert a value to camel case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function camel_case($value)
	{
		return Illuminate\Support\Str::camel($value);
	}
}

if ( ! function_exists('class_basename'))
{
	/**
	 * Get the class "basename" of the given object / class.
	 *
	 * @param  string|object  $class
	 * @return string
	 */
	function class_basename($class)
	{
		$class = is_object($class) ? get_class($class) : $class;

		return basename(str_replace('\\', '/', $class));
	}
}

if ( ! function_exists('csrf_token'))
{
	/**
	 * Get the CSRF token value.
	 *
	 * @return string
	 */
	function csrf_token()
	{
		$session = app('session');

		if (isset($session))
		{
			return $session->getToken();
		}
		else
		{
			throw new RuntimeException("Application session store not set.");
		}
	}
}

if ( ! function_exists('dd'))
{
	/**
	 * Dump the passed variables and end the script.
	 *
	 * @param  dynamic  mixed
	 * @return void
	 */
	function dd()
	{
		array_map(function($x) { var_dump($x); }, func_get_args()); die;
	}
}

if ( ! function_exists('e'))
{
	/**
	 * Escape HTML entities in a string.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function e($value)
	{
		return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
	}
}

if ( ! function_exists('ends_with'))
{
	/**
	 * Determine if a given string ends with a given needle.
	 *
	 * @param string $haystack
	 * @param string|array $needle
	 * @return bool
	 */
	function ends_with($haystack, $needle)
	{
		return Illuminate\Support\Str::endsWith($haystack, $needle);
	}
}

if ( ! function_exists('head'))
{
	/**
	 * Get the first element of an array. Useful for method chaining.
	 *
	 * @param  array  $array
	 * @return mixed
	 */
	function head($array)
	{
		return reset($array);
	}
}

if ( ! function_exists('link_to'))
{
	/**
	 * Generate a HTML link.
	 *
	 * @param  string  $url
	 * @param  string  $title
	 * @param  array   $attributes
	 * @param  bool    $secure
	 * @return string
	 */
	function link_to($url, $title = null, $attributes = array(), $secure = null)
	{
		return app('html')->link($url, $title, $attributes, $secure);
	}
}

if ( ! function_exists('last'))
{
	/**
	 * Get the last element from an array.
	 *
	 * @param  array  $array
	 * @return mixed
	 */
	function last($array)
	{
		return end($array);
	}
}

if ( ! function_exists('link_to_asset'))
{
	/**
	 * Generate a HTML link to an asset.
	 *
	 * @param  string  $url
	 * @param  string  $title
	 * @param  array   $attributes
	 * @param  bool    $secure
	 * @return string
	 */
	function link_to_asset($url, $title = null, $attributes = array(), $secure = null)
	{
		return app('html')->linkAsset($url, $title, $attributes, $secure);
	}
}

if ( ! function_exists('link_to_route'))
{
	/**
	 * Generate a HTML link to a named route.
	 *
	 * @param  string  $name
	 * @param  string  $title
	 * @param  array   $parameters
	 * @param  array   $attributes
	 * @return string
	 */
	function link_to_route($name, $title = null, $parameters = array(), $attributes = array())
	{
		return app('html')->linkRoute($name, $title, $parameters, $attributes);
	}
}

if ( ! function_exists('link_to_action'))
{
	/**
	 * Generate a HTML link to a controller action.
	 *
	 * @param  string  $action
	 * @param  string  $title
	 * @param  array   $parameters
	 * @param  array   $attributes
	 * @return string
	 */
	function link_to_action($action, $title = null, $parameters = array(), $attributes = array())
	{
		return app('html')->linkAction($action, $title, $parameters, $attributes);
	}
}

if ( ! function_exists('object_get'))
{
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
		if (is_null($key)) return $object;
		
		foreach (explode('.', $key) as $segment)
		{
			if ( ! is_object($object) or ! isset($object->{$segment}))
			{
				return value($default);
			}

			$object = $object->{$segment};
		}

		return $object;
	}
}

if ( ! function_exists('public_path'))
{
	/**
	 * Get the path to the public folder.
	 *
	 * @return string
	 */
	function public_path()
	{
		return app()->make('path.public');
	}
}

if ( ! function_exists('route'))
{
	/**
	 * Generate a URL to a named route.
	 *
	 * @param  string  $route
	 * @param  string  $parameters
	 * @param  bool    $absolute
	 * @return string
	 */
	function route($route, $parameters = array(), $absolute = true)
	{
		return app('url')->route($route, $parameters, $absolute);
	}
}

if ( ! function_exists('secure_asset'))
{
	/**
	 * Generate an asset path for the application.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function secure_asset($path)
	{
		return asset($path, true);
	}
}

if ( ! function_exists('secure_url'))
{
	/**
	 * Generate a HTTPS url for the application.
	 *
	 * @param  string  $path
	 * @param  mixed   $parameters
	 * @return string
	 */
	function secure_url($path, $parameters = array())
	{
		return url($path, $parameters, true);
	}
}

if ( ! function_exists('snake_case'))
{
	/**
	 * Convert a string to snake case.
	 *
	 * @param  string  $value
	 * @param  string  $delimiter
	 * @return string
	 */
	function snake_case($value, $delimiter = '_')
	{
		return Illuminate\Support\Str::snake($value, $delimiter);
	}
}

if ( ! function_exists('starts_with'))
{
	/**
	 * Determine if a string starts with a given needle.
	 *
	 * @param  string  $haystack
	 * @param  string|array  $needle
	 * @return bool
	 */
	function starts_with($haystack, $needle)
	{
		return Illuminate\Support\Str::startsWith($haystack, $needle);
	}
}

if ( ! function_exists('storage_path'))
{
	/**
	 * Get the path to the storage folder.
	 *
	 * @return  string
	 */
	function storage_path()
	{
		return app('path.storage');
	}
}

if ( ! function_exists('str_contains'))
{
	/**
	 * Determine if a given string contains a given sub-string.
	 *
	 * @param  string        $haystack
	 * @param  string|array  $needle
	 * @return bool
	 */
	function str_contains($haystack, $needle)
	{
		return Illuminate\Support\Str::contains($haystack, $needle);
	}
}

if ( ! function_exists('str_finish'))
{
	/**
	 * Cap a string with a single instance of a given value.
	 *
	 * @param  string  $value
	 * @param  string  $cap
	 * @return string
	 */
	function str_finish($value, $cap)
	{
		return Illuminate\Support\Str::finish($value, $cap);
	}
}

if ( ! function_exists('str_is'))
{
	/**
	 * Determine if a given string matches a given pattern.
	 *
	 * @param  string  $pattern
	 * @param  string  $value
	 * @return bool
	 */
	function str_is($pattern, $value)
	{
		return Illuminate\Support\Str::is($pattern, $value);
	}
}

if ( ! function_exists('str_plural'))
{
	/**
	 * Get the plural form of an English word.
	 *
	 * @param  string  $value
	 * @param  int  $count
	 * @return string
	 */
	function str_plural($value, $count = 2)
	{
		return Illuminate\Support\Str::plural($value, $count);
	}
}

if ( ! function_exists('str_random'))
{
	/**
	 * Generate a "random" alpha-numeric string.
	 *
	 * Should not be considered sufficient for cryptography, etc.
	 *
	 * @param  int     $length
	 * @return string
	 */
	function str_random($length = 16)
	{
		return Illuminate\Support\Str::random($length);
	}
}

if ( ! function_exists('str_singular'))
{
	/**
	 * Get the singular form of an English word.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function str_singular($value)
	{
		return Illuminate\Support\Str::singular($value);
	}
}

if ( ! function_exists('studly_case'))
{
	/**
	 * Convert a value to studly caps case.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function studly_case($value)
	{
		return Illuminate\Support\Str::studly($value);
	}
}

if ( ! function_exists('trans'))
{
	/**
	 * Translate the given message.
	 *
	 * @param  string  $id
	 * @param  array   $parameters
	 * @param  string  $domain
	 * @param  string  $locale
	 * @return string
	 */
	function trans($id, $parameters = array(), $domain = 'messages', $locale = null)
	{
		return app('translator')->trans($id, $parameters, $domain, $locale);
	}
}

if ( ! function_exists('trans_choice'))
{
	/**
	 * Translates the given message based on a count.
	 *
	 * @param  string  $id
	 * @param  int     $number
	 * @param  array   $parameters
	 * @param  string  $domain
	 * @param  string  $locale
	 * @return string
	 */
	function trans_choice($id, $number, array $parameters = array(), $domain = 'messages', $locale = null)
	{
		return app('translator')->transChoice($id, $number, $parameters, $domain, $locale);
	}
}

if ( ! function_exists('url'))
{
	/**
	 * Generate a url for the application.
	 *
	 * @param  string  $path
	 * @param  mixed   $parameters
	 * @param  bool    $secure
	 * @return string
	 */
	function url($path = null, $parameters = array(), $secure = null)
	{
		return app('url')->to($path, $parameters, $secure);
	}
}

if ( ! function_exists('value'))
{
	/**
	 * Return the default value of the given value.
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	function value($value)
	{
		return $value instanceof Closure ? $value() : $value;
	}
}

if ( ! function_exists('with'))
{
	/**
	 * Return the given object. Useful for chaining.
	 *
	 * @param  mixed  $object
	 * @return mixed
	 */
	function with($object)
	{
		return $object;
	}
}
