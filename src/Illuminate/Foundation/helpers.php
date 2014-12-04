<?php

if ( ! function_exists('abort'))
{
	/**
	 * Throw an HttpException with the given data.
	 *
	 * @param  int     $code
	 * @param  string  $message
	 * @param  array   $headers
	 * @return void
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	function abort($code, $message = '', array $headers = array())
	{
		return app()->abort($code, $message, $headers);
	}
}

if ( ! function_exists('action'))
{
	/**
	 * Generate a URL to a controller action.
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return string
	 */
	function action($name, $parameters = array())
	{
		return app('url')->action($name, $parameters);
	}
}

if ( ! function_exists('app'))
{
	/**
	 * Get the available container instance.
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

		return Illuminate\Container\Container::getInstance();
	}
}

if ( ! function_exists('app_path'))
{
	/**
	 * Get the path to the application folder.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function app_path($path = '')
	{
		return app('path').($path ? '/'.$path : $path);
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
	 * @param  string  $path
	 * @return string
	 */
	function base_path($path = '')
	{
		return app()->make('path.base').($path ? '/'.$path : $path);
	}
}

if ( ! function_exists('bcrypt'))
{
	/**
	 * Hash the given value.
	 *
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	function bcrypt($value, $options = array())
	{
		return app('hash')->make($value, $options);
	}
}

if ( ! function_exists('config'))
{
	/**
	 * Get / set the specified configuration value.
	 *
	 * If an array as passed as the key, we will assume you want to set an array of values.
	 *
	 * @param  array|string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function config($key, $default = null)
	{
		if (is_array($key))
		{
			return app('config')->set($key);
		}
		else
		{
			return app('config')->get($key, $default);
		}
	}
}

if ( ! function_exists('cookie'))
{
	/**
	 * Create a new cookie instance.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  int     $minutes
	 * @param  string  $path
	 * @param  string  $domain
	 * @param  bool    $secure
	 * @param  bool    $httpOnly
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
	{
		$cookie = app('Illuminate\Contracts\Cookie\Factory');

		if (is_null($name))
		{
			return $cookie;
		}

		return $cookie->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly);
	}
}

if ( ! function_exists('csrf_token'))
{
	/**
	 * Get the CSRF token value.
	 *
	 * @return string
	 *
	 * @throws RuntimeException
	 */
	function csrf_token()
	{
		$session = app('session');

		if (isset($session))
		{
			return $session->getToken();
		}

		throw new RuntimeException("Application session store not set.");
	}
}

if ( ! function_exists('delete'))
{
	/**
	 * Register a new DELETE route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 * @return \Illuminate\Routing\Route
	 */
	function delete($uri, $action)
	{
		return app('router')->delete($uri, $action);
	}
}

if ( ! function_exists('get'))
{
	/**
	 * Register a new GET route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 * @return \Illuminate\Routing\Route
	 */
	function get($uri, $action)
	{
		return app('router')->get($uri, $action);
	}
}

if ( ! function_exists('info'))
{
	/**
	 * Write some information to the log.
	 *
	 * @param  string  $message
	 * @param  array   $context
	 * @return void
	 */
	function info($message, $context = array())
	{
		return app('log')->info($message, $context);
	}
}

if ( ! function_exists('logger'))
{
	/**
	 * Log a debug message to the logs.
	 *
	 * @param  string  $message
	 * @param  array  $context
	 * @return void
	 */
	function logger($message, array $context = array())
	{
		return app('log')->debug($message, $context);
	}
}

if ( ! function_exists('old'))
{
	/**
	 * Retrieve an old input item.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function old($key = null, $default = null)
	{
		return app('request')->old($key, $default);
	}
}

if ( ! function_exists('patch'))
{
	/**
	 * Register a new PATCH route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 * @return \Illuminate\Routing\Route
	 */
	function patch($uri, $action)
	{
		return app('router')->patch($uri, $action);
	}
}

if ( ! function_exists('post'))
{
	/**
	 * Register a new POST route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 * @return \Illuminate\Routing\Route
	 */
	function post($uri, $action)
	{
		return app('router')->post($uri, $action);
	}
}

if ( ! function_exists('put'))
{
	/**
	 * Register a new PUT route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 * @return \Illuminate\Routing\Route
	 */
	function put($uri, $action)
	{
		return app('router')->put($uri, $action);
	}
}

if ( ! function_exists('public_path'))
{
	/**
	 * Get the path to the public folder.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function public_path($path = '')
	{
		return app()->make('path.public').($path ? '/'.$path : $path);
	}
}

if ( ! function_exists('redirect'))
{
	/**
	 * Get an instance of the redirector.
	 *
	 * @param  string|null  $to
	 * @param  int     $status
	 * @param  array   $headers
	 * @param  bool    $secure
	 * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
	 */
	function redirect($to = null, $status = 302, $headers = array(), $secure = null)
	{
		if ( ! is_null($to))
		{
			return app('redirect')->to($to, $status, $headers, $secure);
		}
		else
		{
			return app('redirect');
		}
	}
}

if ( ! function_exists('response'))
{
	/**
	 * Return a new response from the application.
	 *
	 * @param  string  $content
	 * @param  int     $status
	 * @param  array   $headers
	 * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Contracts\Routing\ResponseFactory
	 */
	function response($content = '', $status = 200, array $headers = array())
	{
		$factory = app('Illuminate\Contracts\Routing\ResponseFactory');

		if (func_num_args() === 0)
		{
			return $factory;
		}

		return $factory->make($content, $status, $headers);
	}
}

if ( ! function_exists('route'))
{
	/**
	 * Generate a URL to a named route.
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @param  bool    $absolute
	 * @param  \Illuminate\Routing\Route  $route
	 * @return string
	 */
	function route($name, $parameters = array(), $absolute = true, $route = null)
	{
		return app('url')->route($name, $parameters, $absolute, $route);
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

if ( ! function_exists('storage_path'))
{
	/**
	 * Get the path to the storage folder.
	 *
	 * @param  string  $path
	 * @return string
	 */
	function storage_path($path = '')
	{
		return app('path.storage').($path ? '/'.$path : $path);
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
		return app('Illuminate\Contracts\Routing\UrlGenerator')->to($path, $parameters, $secure);
	}
}

if ( ! function_exists('view'))
{
	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @param  array   $mergeData
	 * @return \Illuminate\View\View
	 */
	function view($view = null, $data = array(), $mergeData = array())
	{
		$factory = app('Illuminate\Contracts\View\Factory');

		if (func_num_args() === 0)
		{
			return $factory;
		}

		return $factory->make($view, $data, $mergeData);
	}
}

if ( ! function_exists('elixir'))
{
	/**
	* Get the path to a versioned Elixir file.
	*
	* @param  string  $file
	* @return string
	*/
	function elixir($file)
	{
		static $manifest = null;

		if (is_null($manifest))
		{
			$manifest = json_decode(file_get_contents(public_path().'/build/rev-manifest.json'), true);
		}

		if (isset($manifest[$file]))
		{
			return '/build/'.$manifest[$file];
		}

		throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
	}

}
