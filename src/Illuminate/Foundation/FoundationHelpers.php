<?php

namespace Illuminate\Foundation;

use Laravel;
use Exception;
use Throwable;
use Illuminate\Foundation\Mix;
use Illuminate\Support\HtmlString;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Date;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Queue\SerializableClosure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Bus\PendingDispatch;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Cookie\Factory as CookieFactory;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;

trait FoundationHelpers
{
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
    public static function abort($code, $message = '', array $headers = [])
    {
        if ($code instanceof Response) {
            throw new HttpResponseException($code);
        } elseif ($code instanceof Responsable) {
            throw new HttpResponseException($code->toResponse(static::request()));
        }

        static::app()->abort($code, $message, $headers);
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
    public static function abortIf($boolean, $code, $message = '', array $headers = [])
    {
        if ($boolean) {
            static::abort($code, $message, $headers);
        }
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
    public static function abortUnless($boolean, $code, $message = '', array $headers = [])
    {
        if (! $boolean) {
            static::abort($code, $message, $headers);
        }
    }

    /**
     * Generate the URL to a controller action.
     *
     * @param  string|array  $name
     * @param  mixed   $parameters
     * @param  bool    $absolute
     * @return string
     */
    public static function action($name, $parameters = [], $absolute = true)
    {
        return static::app('url')->action($name, $parameters, $absolute);
    }

    /**
     * Get the available container instance.
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed|\Illuminate\Contracts\Foundation\Application
     */
    public static function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }
        return Container::getInstance()->make($abstract, $parameters);
    }

    /**
     * Get the path to the application folder.
     *
     * @param  string  $path
     * @return string
     */
    public static function appPath($path = '')
    {
        return static::app('path').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     * @param  bool    $secure
     * @return string
     */
    public static function asset($path, $secure = null)
    {
        return static::app('url')->asset($path, $secure);
    }

    /**
     * Get the available auth instance.
     *
     * @param  string|null  $guard
     * @return \Illuminate\Contracts\Auth\Factory|\Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    public static function auth($guard = null)
    {
        if (is_null($guard)) {
            return static::app(AuthFactory::class);
        }
        return static::app(AuthFactory::class)->guard($guard);
    }

    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int    $status
     * @param  array  $headers
     * @param  mixed  $fallback
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function back($status = 302, $headers = [], $fallback = false)
    {
        return static::app('redirect')->back($status, $headers, $fallback);
    }

    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    public static function basePath($path = '')
    {
        return static::app()->basePath().($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Hash the given value against the bcrypt algorithm.
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     */
    public static function bcrypt($value, $options = [])
    {
        return static::app('hash')->driver('bcrypt')->make($value, $options);
    }

    /**
     * Begin broadcasting an event.
     *
     * @param  mixed|null  $event
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public static function broadcast($event = null)
    {
        return static::app(BroadcastFactory::class)->event($event);
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
    public static function cache()
    {
        $arguments = func_get_args();

        if (empty($arguments)) {
            return static::app('cache');
        }

        if (is_string($arguments[0])) {
            return static::app('cache')->get(...$arguments);
        }

        if (! is_array($arguments[0])) {
            throw new Exception(
                'When setting a value in the cache, you must pass an array of key / value pairs.'
            );
        }

        if (! isset($arguments[1])) {
            throw new Exception(
                'You must specify an expiration time when setting a value in the cache.'
            );
        }

        return static::app('cache')->put(key($arguments[0]), reset($arguments[0]), $arguments[1]);
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
    public static function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return static::app('config');
        }

        if (is_array($key)) {
            return static::app('config')->set($key);
        }

        return static::app('config')->get($key, $default);
    }

    /**
     * Get the configuration path.
     *
     * @param  string  $path
     * @return string
     */
    public static function configPath($path = '')
    {
        return static::app()->make('path.config').($path ? DIRECTORY_SEPARATOR.$path : $path);
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
    public static function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true, $raw = false, $sameSite = null)
    {
        $cookie = static::app(CookieFactory::class);

        if (is_null($name)) {
            return $cookie;
        }

        return $cookie->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    /**
     * Generate a CSRF token form field.
     *
     * @return \Illuminate\Support\HtmlString
     */
    public static function csrfField()
    {
        return new HtmlString('<input type="hidden" name="_token" value="'.static::csrfToken().'">');
    }

    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function csrfToken()
    {
        $session = static::app('session');

        if (isset($session)) {
            return $session->token();
        }

        throw new RuntimeException('Application session store not set.');
    }

    /**
     * Get the database path.
     *
     * @param  string  $path
     * @return string
     */
    public static function databasePath($path = '')
    {
        return static::app()->databasePath($path);
    }

    /**
     * Decrypt the given value.
     *
     * @param  string  $value
     * @param  bool   $unserialize
     * @return mixed
     */
    public static function decrypt($value, $unserialize = true)
    {
        return static::app('encrypter')->decrypt($value, $unserialize);
    }

    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param  mixed  $job
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatch($job)
    {
        if ($job instanceof Closure) {
            $job = new CallQueuedClosure(new SerializableClosure($job));
        }

        return new PendingDispatch($job);
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $job
     * @param  mixed  $handler
     * @return mixed
     */
    public static function dispatchNow($job, $handler = null)
    {
        return static::app(Dispatcher::class)->dispatchNow($job, $handler);
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
    public static function elixir($file, $buildDirectory = 'build')
    {
        static $manifest = [];
        static $manifestPath;

        if (empty($manifest) || $manifestPath !== $buildDirectory) {
            $path = static::publicPath($buildDirectory.'/rev-manifest.json');

            if (file_exists($path)) {
                $manifest = json_decode(file_get_contents($path), true);
                $manifestPath = $buildDirectory;
            }
        }

        $file = ltrim($file, '/');

        if (isset($manifest[$file])) {
            return '/'.trim($buildDirectory.'/'.$manifest[$file], '/');
        }

        $unversioned = static::publicPath($file);

        if (file_exists($unversioned)) {
            return '/'.trim($file, '/');
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }

    /**
     * Encrypt the given value.
     *
     * @param  mixed  $value
     * @param  bool   $serialize
     * @return string
     */
    public static function encrypt($value, $serialize = true)
    {
        return static::app('encrypter')->encrypt($value, $serialize);
    }

    /**
     * Dispatch an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public static function event(...$args)
    {
        return static::app('events')->dispatch(...$args);
    }

    /**
     * Create a model factory builder for a given class, name, and amount.
     *
     * @param  dynamic  class|class,name|class,amount|class,name,amount
     * @return \Illuminate\Database\Eloquent\FactoryBuilder
     */
    public static function factory()
    {
        $factory = static::app(EloquentFactory::class);

        $arguments = func_get_args();

        if (isset($arguments[1]) && is_string($arguments[1])) {
            return $factory->of($arguments[0], $arguments[1])->times($arguments[2] ?? null);
        } elseif (isset($arguments[1])) {
            return $factory->of($arguments[0])->times($arguments[1]);
        }

        return $factory->of($arguments[0]);
    }

    /**
     * Write some information to the log.
     *
     * @param  string  $message
     * @param  array   $context
     * @return void
     */
    public static function info($message, $context = [])
    {
        static::app('log')->info($message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return \Illuminate\Log\LogManager|null
     */
    public static function logger($message = null, array $context = [])
    {
        if (is_null($message)) {
            return static::app('log');
        }

        return static::app('log')->debug($message, $context);
    }

    /**
     * Get a log driver instance.
     *
     * @param  string  $driver
     * @return \Illuminate\Log\LogManager|\Psr\Log\LoggerInterface
     */
    public static function logs($driver = null)
    {
        return $driver ? static::app('log')->driver($driver) : static::app('log');
    }

    /**
     * Generate a form field to spoof the HTTP verb used by forms.
     *
     * @param  string  $method
     * @return \Illuminate\Support\HtmlString
     */
    public static function methodField($method)
    {
        return new HtmlString('<input type="hidden" name="_method" value="'.$method.'">');
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
    public static function mix($path, $manifestDirectory = '')
    {
        return static::app(Mix::class)(...func_get_args());
    }

    /**
     * Create a new Carbon instance for the current time.
     *
     * @param  \DateTimeZone|string|null $tz
     * @return \Illuminate\Support\Carbon
     */
    public static function now($tz = null)
    {
        return Date::now($tz);
    }

    /**
     * Retrieve an old input item.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function old($key = null, $default = null)
    {
        return static::app('request')->old($key, $default);
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function policy($class)
    {
        return static::app(Gate::class)->getPolicyFor($class);
    }

    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    public static function publicPath($path = '')
    {
        return static::app()->make('path.public').($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
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
    public static function redirect($to = null, $status = 302, $headers = [], $secure = null)
    {
        if (is_null($to)) {
            return static::app('redirect');
        }

        return static::app('redirect')->to($to, $status, $headers, $secure);
    }

    /**
     * Report an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public static function report($exception)
    {
        if ($exception instanceof Throwable &&
            ! $exception instanceof Exception) {
            $exception = new FatalThrowableError($exception);
        }

        static::app(ExceptionHandler::class)->report($exception);
    }

    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  array|string  $key
     * @param  mixed   $default
     * @return \Illuminate\Http\Request|string|array
     */
    public static function request($key = null, $default = null)
    {
        if (is_null($key)) {
            return static::app('request');
        }

        if (is_array($key)) {
            return static::app('request')->only($key);
        }

        $value = static::app('request')->__get($key);

        return is_null($value) ? static::value($default) : $value;
    }

    /**
     * Catch a potential exception and return a default value.
     *
     * @param  callable  $callback
     * @param  mixed  $rescue
     * @return mixed
     */
    public static function rescue(callable $callback, $rescue = null)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            static::report($e);

            return static::value($rescue);
        }
    }

    /**
     * Resolve a service from the container.
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return mixed
     */
    public static function resolve($name, array $parameters = [])
    {
        return static::app($name, $parameters);
    }

    /**
     * Get the path to the resources folder.
     *
     * @param  string  $path
     * @return string
     */
    public static function resourcePath($path = '')
    {
        return static::app()->resourcePath($path);
    }

    /**
     * Return a new response from the application.
     *
     * @param  \Illuminate\View\View|string|array|null  $content
     * @param  int     $status
     * @param  array   $headers
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public static function response($content = '', $status = 200, array $headers = [])
    {
        $factory = static::app(ResponseFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($content, $status, $headers);
    }

    /**
     * Generate the URL to a named route.
     *
     * @param  array|string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string
     */
    public static function route($name, $parameters = [], $absolute = true)
    {
        return static::app('url')->route($name, $parameters, $absolute);
    }

    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     * @return string
     */
    public static function secureAsset($path)
    {
        return static::asset($path, true);
    }

    /**
     * Generate a HTTPS url for the application.
     *
     * @param  string  $path
     * @param  mixed   $parameters
     * @return string
     */
    public static function secureUrl($path, $parameters = [])
    {
        return static::url($path, $parameters, true);
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
    public static function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return static::app('session');
        }

        if (is_array($key)) {
            return static::app('session')->put($key);
        }

        return static::app('session')->get($key, $default);
    }

    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    public static function storagePath($path = '')
    {
        return static::app('path.storage').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Create a new Carbon instance for the current date.
     *
     * @param  \DateTimeZone|string|null $tz
     * @return \Illuminate\Support\Carbon
     */
    public static function today($tz = null)
    {
        return Date::today($tz);
    }

    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    public static function trans($key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return static::app('translator');
        }

        return static::app('translator')->trans($key, $replace, $locale);
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
    public static function transChoice($key, $number, array $replace = [], $locale = null)
    {
        return static::app('translator')->transChoice($key, $number, $replace, $locale);
    }

    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string  $locale
     * @return string|array|null
     */
    public static function __($key, $replace = [], $locale = null)
    {
        return static::app('translator')->getFromJson($key, $replace, $locale);
    }

    /**
     * Generate a url for the application.
     *
     * @param  string  $path
     * @param  mixed   $parameters
     * @param  bool    $secure
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public static function url($path = null, $parameters = [], $secure = null)
    {
        if (is_null($path)) {
            return static::app(UrlGenerator::class);
        }

        return static::app(UrlGenerator::class)->to($path, $parameters, $secure);
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
    public static function validator(array $data = [], array $rules = [], array $messages = [], array $customAttributes = [])
    {
        $factory = static::app(ValidationFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($data, $rules, $messages, $customAttributes);
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array   $data
     * @param  array   $mergeData
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public static function view($view = null, $data = [], $mergeData = [])
    {
        $factory = static::app(ViewFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }
}
