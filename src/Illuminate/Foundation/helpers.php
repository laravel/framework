<?php

/**
 * Throw an HttpException with the given data.
 *
 * @param  \Symfony\Component\HttpFoundation\Response|\Illuminate\Contracts\Support\Responsable|int     $code
 * @param  string  $message
 * @param  array   $headers
 * @return void
 *
 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 */
function abort($code, $message = '', array $headers = [])
{
    Laravel::abort($code, $message, $headers);
}

/**
 * Throw an HttpException with the given data if the given condition is true.
 *
 * @param  bool    $boolean
 * @param  int     $code
 * @param  string  $message
 * @param  array   $headers
 * @return void
 *
 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 */
function abort_if($boolean, $code, $message = '', array $headers = [])
{
    Laravel::abortIf($boolean, $code, $message, $headers);
}

/**
 * Throw an HttpException with the given data unless the given condition is true.
 *
 * @param  bool    $boolean
 * @param  int     $code
 * @param  string  $message
 * @param  array   $headers
 * @return void
 *
 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 */
function abort_unless($boolean, $code, $message = '', array $headers = [])
{
    Laravel::abortUnless($boolean, $code, $message, $headers);
}

/**
 * Generate the URL to a controller action.
 *
 * @param  string|array  $name
 * @param  mixed   $parameters
 * @param  bool    $absolute
 * @return string
 */
function action($name, $parameters = [], $absolute = true)
{
    return Laravel::action($name, $parameters, $absolute);
}

/**
 * Get the available container instance.
 *
 * @param  string  $abstract
 * @param  array   $parameters
 * @return mixed|\Illuminate\Contracts\Foundation\Application
 */
function app($abstract = null, array $parameters = [])
{
    return Laravel::app($abstract, $parameters);
}

/**
 * Get the path to the application folder.
 *
 * @param  string  $path
 * @return string
 */
function app_path($path = '')
{
    return Laravel::appPath($path);
}

/**
 * Generate an asset path for the application.
 *
 * @param  string  $path
 * @param  bool    $secure
 * @return string
 */
function asset($path, $secure = null)
{
    return Laravel::asset($path, $secure);
}

/**
 * Get the available auth instance.
 *
 * @param  string|null  $guard
 * @return \Illuminate\Contracts\Auth\Factory|\Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
 */
function auth($guard = null)
{
    return Laravel::auth($guard);
}

/**
 * Create a new redirect response to the previous location.
 *
 * @param  int    $status
 * @param  array  $headers
 * @param  mixed  $fallback
 * @return \Illuminate\Http\RedirectResponse
 */
function back($status = 302, $headers = [], $fallback = false)
{
    return Laravel::back($status, $headers, $fallback);
}

/**
 * Get the path to the base of the install.
 *
 * @param  string  $path
 * @return string
 */
function base_path($path = '')
{
    return Laravel::basePath($path);
}

/**
 * Hash the given value against the bcrypt algorithm.
 *
 * @param  string  $value
 * @param  array  $options
 * @return string
 */
function bcrypt($value, $options = [])
{
    return Laravel::bcrypt($value, $options);
}

/**
 * Begin broadcasting an event.
 *
 * @param  mixed|null  $event
 * @return \Illuminate\Broadcasting\PendingBroadcast
 */
function broadcast($event = null)
{
    return Laravel::broadcast($event);
}

/**
 * Get / set the specified cache value.
 *
 * If an array is passed, we'll assume you want to put to the cache.
 *
 * @param  dynamic  key|key,default|data,expiration|null
 * @return mixed|\Illuminate\Cache\CacheManager
 *
 * @throws \Exception
 */
function cache()
{
    return Laravel::cache(...func_get_args());
}

/**
 * Get / set the specified configuration value.
 *
 * If an array is passed as the key, we will assume you want to set an array of values.
 *
 * @param  array|string  $key
 * @param  mixed  $default
 * @return mixed|\Illuminate\Config\Repository
 */
function config($key = null, $default = null)
{
    return Laravel::config($key, $default);
}

/**
 * Get the configuration path.
 *
 * @param  string  $path
 * @return string
 */
function config_path($path = '')
{
    return Laravel::configPath($path);
}

/**
 * Create a new cookie instance.
 *
 * @param  string  $name
 * @param  string  $value
 * @param  int  $minutes
 * @param  string  $path
 * @param  string  $domain
 * @param  bool  $secure
 * @param  bool  $httpOnly
 * @param  bool  $raw
 * @param  string|null  $sameSite
 * @return \Illuminate\Cookie\CookieJar|\Symfony\Component\HttpFoundation\Cookie
 */
function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true, $raw = false, $sameSite = null)
{
    return Laravel::cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
}

/**
 * Generate a CSRF token form field.
 *
 * @return \Illuminate\Support\HtmlString
 */
function csrf_field()
{
    return Laravel::csrfField();
}

/**
 * Get the CSRF token value.
 *
 * @return string
 *
 * @throws \RuntimeException
 */
function csrf_token()
{
    return Laravel::csrfToken();
}

/**
 * Get the database path.
 *
 * @param  string  $path
 * @return string
 */
function database_path($path = '')
{
    return Laravel::databasePath($path);
}

/**
 * Decrypt the given value.
 *
 * @param  string  $value
 * @param  bool   $unserialize
 * @return mixed
 */
function decrypt($value, $unserialize = true)
{
    return Laravel::decrypt($value, $unserialize);
}

/**
 * Dispatch a job to its appropriate handler.
 *
 * @param  mixed  $job
 * @return \Illuminate\Foundation\Bus\PendingDispatch
 */
function dispatch($job)
{
    return Laravel::dispatch($job);
}

/**
 * Dispatch a command to its appropriate handler in the current process.
 *
 * @param  mixed  $job
 * @param  mixed  $handler
 * @return mixed
 */
function dispatch_now($job, $handler = null)
{
    return Laravel::dispatchNow($job, $handler);
}

/**
 * Get the path to a versioned Elixir file.
 *
 * @param  string  $file
 * @param  string  $buildDirectory
 * @return string
 *
 * @throws \InvalidArgumentException
 */
function elixir($file, $buildDirectory = 'build')
{
    return Laravel::elixir($file, $buildDirectory);
}

/**
 * Encrypt the given value.
 *
 * @param  mixed  $value
 * @param  bool   $serialize
 * @return string
 */
function encrypt($value, $serialize = true)
{
    return Laravel::encrypt($value, $serialize);
}

/**
 * Dispatch an event and call the listeners.
 *
 * @param  string|object  $event
 * @param  mixed  $payload
 * @param  bool  $halt
 * @return array|null
 */
function event(...$args)
{
    return Laravel::event(...$args);
}

/**
 * Create a model factory builder for a given class, name, and amount.
 *
 * @param  dynamic  class|class,name|class,amount|class,name,amount
 * @return \Illuminate\Database\Eloquent\FactoryBuilder
 */
function factory()
{
    return Laravel::factory(...func_get_args());
}

/**
 * Write some information to the log.
 *
 * @param  string  $message
 * @param  array   $context
 * @return void
 */
function info($message, $context = [])
{
    Laravel::info($message, $context);
}

/**
 * Log a debug message to the logs.
 *
 * @param  string  $message
 * @param  array  $context
 * @return \Illuminate\Log\LogManager|null
 */
function logger($message = null, array $context = [])
{
    return Laravel::logger($message, $context);
}

/**
 * Get a log driver instance.
 *
 * @param  string  $driver
 * @return \Illuminate\Log\LogManager|\Psr\Log\LoggerInterface
 */
function logs($driver = null)
{
    return Laravel::logs($driver);
}

/**
 * Generate a form field to spoof the HTTP verb used by forms.
 *
 * @param  string  $method
 * @return \Illuminate\Support\HtmlString
 */
function method_field($method)
{
    return Laravel::methodField($method);
}

/**
 * Get the path to a versioned Mix file.
 *
 * @param  string  $path
 * @param  string  $manifestDirectory
 * @return \Illuminate\Support\HtmlString|string
 *
 * @throws \Exception
 */
function mix($path, $manifestDirectory = '')
{
    return Laravel::mix($path, $manifestDirectory);
}

/**
 * Create a new Carbon instance for the current time.
 *
 * @param  \DateTimeZone|string|null $tz
 * @return \Illuminate\Support\Carbon
 */
function now($tz = null)
{
    return Laravel::now($tz);
}

/**
 * Retrieve an old input item.
 *
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function old($key = null, $default = null)
{
    return Laravel::old($key, $default);
}

/**
 * Get a policy instance for a given class.
 *
 * @param  object|string  $class
 * @return mixed
 *
 * @throws \InvalidArgumentException
 */
function policy($class)
{
    return Laravel::policy($class);
}

/**
 * Get the path to the public folder.
 *
 * @param  string  $path
 * @return string
 */
function public_path($path = '')
{
    return Laravel::publicPath($path);
}

/**
 * Get an instance of the redirector.
 *
 * @param  string|null  $to
 * @param  int     $status
 * @param  array   $headers
 * @param  bool    $secure
 * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
 */
function redirect($to = null, $status = 302, $headers = [], $secure = null)
{
    return Laravel::redirect($to, $status, $headers, $secure);
}

/**
 * Report an exception.
 *
 * @param  \Throwable  $exception
 * @return void
 */
function report($exception)
{
    return Laravel::report($exception);
}

/**
 * Get an instance of the current request or an input item from the request.
 *
 * @param  array|string  $key
 * @param  mixed   $default
 * @return \Illuminate\Http\Request|string|array
 */
function request($key = null, $default = null)
{
    return Laravel::request($key, $default);
}

/**
 * Catch a potential exception and return a default value.
 *
 * @param  callable  $callback
 * @param  mixed  $rescue
 * @return mixed
 */
function rescue(callable $callback, $rescue = null)
{
    return Laravel::rescue($callback, $rescue);
}

/**
 * Resolve a service from the container.
 *
 * @param  string  $name
 * @param  array  $parameters
 * @return mixed
 */
function resolve($name, array $parameters = [])
{
    return Laravel::resolve($name, $parameters);
}

/**
 * Get the path to the resources folder.
 *
 * @param  string  $path
 * @return string
 */
function resource_path($path = '')
{
    return Laravel::resourcePath($path);
}

/**
 * Return a new response from the application.
 *
 * @param  \Illuminate\View\View|string|array|null  $content
 * @param  int     $status
 * @param  array   $headers
 * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
 */
function response($content = '', $status = 200, array $headers = [])
{
    if (func_num_args() === 0) {
        return Laravel::response();
    }

    return Laravel::response($content, $status, $headers);
}

/**
 * Generate the URL to a named route.
 *
 * @param  array|string  $name
 * @param  mixed  $parameters
 * @param  bool  $absolute
 * @return string
 */
function route($name, $parameters = [], $absolute = true)
{
    return Laravel::route($name, $parameters, $absolute);
}

/**
 * Generate an asset path for the application.
 *
 * @param  string  $path
 * @return string
 */
function secure_asset($path)
{
    return Laravel::secureAsset($path);
}

/**
 * Generate a HTTPS url for the application.
 *
 * @param  string  $path
 * @param  mixed   $parameters
 * @return string
 */
function secure_url($path, $parameters = [])
{
    return Laravel::secureUrl($path, $parameters);
}

/**
 * Get / set the specified session value.
 *
 * If an array is passed as the key, we will assume you want to set an array of values.
 *
 * @param  array|string  $key
 * @param  mixed  $default
 * @return mixed|\Illuminate\Session\Store|\Illuminate\Session\SessionManager
 */
function session($key = null, $default = null)
{
    return Laravel::session($key, $default);
}

/**
 * Get the path to the storage folder.
 *
 * @param  string  $path
 * @return string
 */
function storage_path($path = '')
{
    return Laravel::storagePath($path);
}

/**
 * Create a new Carbon instance for the current date.
 *
 * @param  \DateTimeZone|string|null $tz
 * @return \Illuminate\Support\Carbon
 */
function today($tz = null)
{
    return Laravel::today($tz);
}

/**
 * Translate the given message.
 *
 * @param  string  $key
 * @param  array   $replace
 * @param  string  $locale
 * @return \Illuminate\Contracts\Translation\Translator|string|array|null
 */
function trans($key = null, $replace = [], $locale = null)
{
    return Laravel::trans($key, $replace, $locale);
}

/**
 * Translates the given message based on a count.
 *
 * @param  string  $key
 * @param  int|array|\Countable  $number
 * @param  array   $replace
 * @param  string  $locale
 * @return string
 */
function trans_choice($key, $number, array $replace = [], $locale = null)
{
    return Laravel::transChoice($key, $number, $replace, $locale);
}

/**
 * Translate the given message.
 *
 * @param  string  $key
 * @param  array  $replace
 * @param  string  $locale
 * @return string|array|null
 */
function __($key, $replace = [], $locale = null)
{
    return Laravel::__($key, $replace, $locale);
}

/**
 * Generate a url for the application.
 *
 * @param  string  $path
 * @param  mixed   $parameters
 * @param  bool    $secure
 * @return \Illuminate\Contracts\Routing\UrlGenerator|string
 */
function url($path = null, $parameters = [], $secure = null)
{
    return Laravel::url($path, $parameters, $secure);
}

/**
 * Create a new Validator instance.
 *
 * @param  array  $data
 * @param  array  $rules
 * @param  array  $messages
 * @param  array  $customAttributes
 * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Contracts\Validation\Factory
 */
function validator(array $data = [], array $rules = [], array $messages = [], array $customAttributes = [])
{
    return Laravel::validator($data, $rules, $messages, $customAttributes);
}

/**
 * Get the evaluated view contents for the given view.
 *
 * @param  string  $view
 * @param  \Illuminate\Contracts\Support\Arrayable|array   $data
 * @param  array   $mergeData
 * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
 */
function view($view = null, $data = [], $mergeData = [])
{
    if (func_num_args() === 0) {
        return Laravel::view();
    }

    return Laravel::view($view, $data, $mergeData);
}
