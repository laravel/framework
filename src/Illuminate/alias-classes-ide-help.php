<?php



class App extends Illuminate\Foundation\Application
{

    public static function version()
    {
    }

    public static function bootstrapWith($bootstrappers)
    {
    }

    public static function afterLoadingEnvironment($callback)
    {
    }

    public static function beforeBootstrapping($bootstrapper, $callback)
    {
    }

    public static function afterBootstrapping($bootstrapper, $callback)
    {
    }

    public static function hasBeenBootstrapped()
    {
    }

    public static function setBasePath($basePath)
    {
    }

    public static function path()
    {
    }

    public static function basePath()
    {
    }

    public static function configPath()
    {
    }

    public static function databasePath()
    {
    }

    public static function useDatabasePath($path)
    {
    }

    public static function langPath()
    {
    }

    public static function publicPath()
    {
    }

    public static function storagePath()
    {
    }

    public static function useStoragePath($path)
    {
    }

    public static function loadEnvironmentFrom($file)
    {
    }

    public static function environmentFile()
    {
    }

    public static function environment()
    {
    }

    public static function isLocal()
    {
    }

    public static function detectEnvironment($callback)
    {
    }

    public static function runningInConsole()
    {
    }

    public static function runningUnitTests()
    {
    }

    public static function registerConfiguredProviders()
    {
    }

    public static function register($provider, $options = array (
    ), $force = false)
    {
    }

    public static function getProvider($provider)
    {
    }

    public static function resolveProviderClass($provider)
    {
    }

    public static function loadDeferredProviders()
    {
    }

    public static function loadDeferredProvider($service)
    {
    }

    public static function registerDeferredProvider($provider, $service = NULL)
    {
    }

    public static function make($abstract, $parameters = array (
    ))
    {
    }

    public static function bound($abstract)
    {
    }

    public static function isBooted()
    {
    }

    public static function boot()
    {
    }

    public static function booting($callback)
    {
    }

    public static function booted($callback)
    {
    }

    public static function handle($request, $type = 1, $catch = true)
    {
    }

    public static function configurationIsCached()
    {
    }

    public static function getCachedConfigPath()
    {
    }

    public static function routesAreCached()
    {
    }

    public static function getCachedRoutesPath()
    {
    }

    public static function getCachedCompilePath()
    {
    }

    public static function getCachedServicesPath()
    {
    }

    public static function isDownForMaintenance()
    {
    }

    public static function abort($code, $message = '', $headers = array (
    ))
    {
    }

    public static function terminating($callback)
    {
    }

    public static function terminate()
    {
    }

    public static function getLoadedProviders()
    {
    }

    public static function getDeferredServices()
    {
    }

    public static function setDeferredServices($services)
    {
    }

    public static function addDeferredServices($services)
    {
    }

    public static function isDeferredService($service)
    {
    }

    public static function configureMonologUsing($callback)
    {
    }

    public static function hasMonologConfigurator()
    {
    }

    public static function getMonologConfigurator()
    {
    }

    public static function getLocale()
    {
    }

    public static function setLocale($locale)
    {
    }

    public static function registerCoreContainerAliases()
    {
    }

    public static function flush()
    {
    }

    public static function getNamespace()
    {
    }

    public static function when($concrete)
    {
    }

    public static function resolved($abstract)
    {
    }

    public static function isAlias($name)
    {
    }

    public static function bind($abstract, $concrete = NULL, $shared = false)
    {
    }

    public static function addContextualBinding($concrete, $abstract, $implementation)
    {
    }

    public static function bindIf($abstract, $concrete = NULL, $shared = false)
    {
    }

    public static function singleton($abstract, $concrete = NULL)
    {
    }

    public static function share($closure)
    {
    }

    public static function bindShared($abstract, $closure)
    {
    }

    public static function extend($abstract, $closure)
    {
    }

    public static function instance($abstract, $instance)
    {
    }

    public static function tag($abstracts, $tags)
    {
    }

    public static function tagged($tag)
    {
    }

    public static function alias($abstract, $alias)
    {
    }

    public static function rebinding($abstract, $callback)
    {
    }

    public static function refresh($abstract, $target, $method)
    {
    }

    public static function wrap($callback, $parameters = array (
    ))
    {
    }

    public static function call($callback, $parameters = array (
    ), $defaultMethod = NULL)
    {
    }

    public static function build($concrete, $parameters = array (
    ))
    {
    }

    public static function resolving($abstract, $callback = NULL)
    {
    }

    public static function afterResolving($abstract, $callback = NULL)
    {
    }

    public static function isShared($abstract)
    {
    }

    public static function getBindings()
    {
    }

    public static function forgetInstance($abstract)
    {
    }

    public static function forgetInstances()
    {
    }

    public static function getInstance()
    {
    }

    public static function setInstance($container)
    {
    }

    public static function offsetExists($key)
    {
    }

    public static function offsetGet($key)
    {
    }

    public static function offsetSet($key, $value)
    {
    }

    public static function offsetUnset($key)
    {
    }
}

class Artisan extends App\Console\Kernel
{

    public static function handle($input, $output = NULL)
    {
    }

    public static function terminate($input, $status)
    {
    }

    public static function call($command, $parameters = array (
    ))
    {
    }

    public static function queue($command, $parameters = array (
    ))
    {
    }

    public static function all()
    {
    }

    public static function output()
    {
    }

    public static function bootstrap()
    {
    }
}

class Auth extends Illuminate\Auth\AuthManager
{

    public static function createDatabaseDriver()
    {
    }

    public static function createEloquentDriver()
    {
    }

    public static function getDefaultDriver()
    {
    }

    public static function setDefaultDriver($name)
    {
    }

    public static function driver($driver = NULL)
    {
    }

    public static function extend($driver, $callback)
    {
    }

    public static function getDrivers()
    {
    }
}

class Blade extends Illuminate\View\Compilers\BladeCompiler
{

    public static function compile($path = NULL)
    {
    }

    public static function getPath()
    {
    }

    public static function setPath($path)
    {
    }

    public static function compileString($value)
    {
    }

    public static function compileEchoDefaults($value)
    {
    }

    public static function getExtensions()
    {
    }

    public static function extend($compiler)
    {
    }

    public static function directive($name, $handler)
    {
    }

    public static function getRawTags()
    {
    }

    public static function setRawTags($openTag, $closeTag)
    {
    }

    public static function setContentTags($openTag, $closeTag, $escaped = false)
    {
    }

    public static function setEscapedContentTags($openTag, $closeTag)
    {
    }

    public static function getContentTags()
    {
    }

    public static function getEscapedContentTags()
    {
    }

    public static function setEchoFormat($format)
    {
    }

    public static function getCompiledPath($path)
    {
    }

    public static function isExpired($path)
    {
    }
}

class Bus extends Illuminate\Bus\Dispatcher
{

    public static function dispatchFromArray($command, $array)
    {
    }

    public static function dispatchFrom($command, $source, $extras = array (
    ))
    {
    }

    public static function dispatch($command, $afterResolving = NULL)
    {
    }

    public static function dispatchNow($command, $afterResolving = NULL)
    {
    }

    public static function dispatchToQueue($command)
    {
    }

    public static function resolveHandler($command)
    {
    }

    public static function getHandlerClass($command)
    {
    }

    public static function getHandlerMethod($command)
    {
    }

    public static function maps($commands)
    {
    }

    public static function mapUsing($mapper)
    {
    }

    public static function simpleMapping($command, $commandNamespace, $handlerNamespace)
    {
    }

    public static function pipeThrough($pipes)
    {
    }
}

class Cache extends Illuminate\Cache\CacheManager
{

    public static function store($name = NULL)
    {
    }

    public static function driver($driver = NULL)
    {
    }

    public static function repository($store)
    {
    }

    public static function getDefaultDriver()
    {
    }

    public static function setDefaultDriver($name)
    {
    }

    public static function extend($driver, $callback)
    {
    }
}

class Config extends Illuminate\Config\Repository
{

    public static function has($key)
    {
    }

    public static function get($key, $default = NULL)
    {
    }

    public static function set($key, $value = NULL)
    {
    }

    public static function prepend($key, $value)
    {
    }

    public static function push($key, $value)
    {
    }

    public static function all()
    {
    }

    public static function offsetExists($key)
    {
    }

    public static function offsetGet($key)
    {
    }

    public static function offsetSet($key, $value)
    {
    }

    public static function offsetUnset($key)
    {
    }
}

class Cookie extends Illuminate\Cookie\CookieJar
{

    public static function make($name, $value, $minutes = 0, $path = NULL, $domain = NULL, $secure = false, $httpOnly = true)
    {
    }

    public static function forever($name, $value, $path = NULL, $domain = NULL, $secure = false, $httpOnly = true)
    {
    }

    public static function forget($name, $path = NULL, $domain = NULL)
    {
    }

    public static function hasQueued($key)
    {
    }

    public static function queued($key, $default = NULL)
    {
    }

    public static function queue()
    {
    }

    public static function unqueue($name)
    {
    }

    public static function setDefaultPathAndDomain($path, $domain)
    {
    }

    public static function getQueuedCookies()
    {
    }
}

class Crypt extends Illuminate\Encryption\Encrypter
{

    public static function supported($key, $cipher)
    {
    }

    public static function encrypt($value)
    {
    }

    public static function decrypt($payload)
    {
    }
}

class DB extends Illuminate\Database\DatabaseManager
{

    public static function connection($name = NULL)
    {
    }

    public static function purge($name = NULL)
    {
    }

    public static function disconnect($name = NULL)
    {
    }

    public static function reconnect($name = NULL)
    {
    }

    public static function getDefaultConnection()
    {
    }

    public static function setDefaultConnection($name)
    {
    }

    public static function extend($name, $resolver)
    {
    }

    public static function getConnections()
    {
    }
}

class Event extends Illuminate\Events\Dispatcher
{

    public static function listen($events, $listener, $priority = 0)
    {
    }

    public static function hasListeners($eventName)
    {
    }

    public static function push($event, $payload = array (
    ))
    {
    }

    public static function subscribe($subscriber)
    {
    }

    public static function until($event, $payload = array (
    ))
    {
    }

    public static function flush($event)
    {
    }

    public static function firing()
    {
    }

    public static function fire($event, $payload = array (
    ), $halt = false)
    {
    }

    public static function getListeners($eventName)
    {
    }

    public static function makeListener($listener)
    {
    }

    public static function createClassListener($listener)
    {
    }

    public static function forget($event)
    {
    }

    public static function forgetPushed()
    {
    }

    public static function setQueueResolver($resolver)
    {
    }
}

class File extends Illuminate\Filesystem\Filesystem
{

    public static function exists($path)
    {
    }

    public static function get($path)
    {
    }

    public static function getRequire($path)
    {
    }

    public static function requireOnce($file)
    {
    }

    public static function put($path, $contents, $lock = false)
    {
    }

    public static function prepend($path, $data)
    {
    }

    public static function append($path, $data)
    {
    }

    public static function delete($paths)
    {
    }

    public static function move($path, $target)
    {
    }

    public static function copy($path, $target)
    {
    }

    public static function name($path)
    {
    }

    public static function extension($path)
    {
    }

    public static function type($path)
    {
    }

    public static function mimeType($path)
    {
    }

    public static function size($path)
    {
    }

    public static function lastModified($path)
    {
    }

    public static function isDirectory($directory)
    {
    }

    public static function isWritable($path)
    {
    }

    public static function isFile($file)
    {
    }

    public static function glob($pattern, $flags = 0)
    {
    }

    public static function files($directory)
    {
    }

    public static function allFiles($directory)
    {
    }

    public static function directories($directory)
    {
    }

    public static function makeDirectory($path, $mode = 493, $recursive = false, $force = false)
    {
    }

    public static function copyDirectory($directory, $destination, $options = NULL)
    {
    }

    public static function deleteDirectory($directory, $preserve = false)
    {
    }

    public static function cleanDirectory($directory)
    {
    }

    public static function macro($name, $macro)
    {
    }

    public static function hasMacro($name)
    {
    }
}

class Hash extends Illuminate\Hashing\BcryptHasher
{

    public static function make($value, $options = array (
    ))
    {
    }

    public static function check($value, $hashedValue, $options = array (
    ))
    {
    }

    public static function needsRehash($hashedValue, $options = array (
    ))
    {
    }

    public static function setRounds($rounds)
    {
    }
}

class Input extends Illuminate\Http\Request
{

    public static function capture()
    {
    }

    public static function instance()
    {
    }

    public static function method()
    {
    }

    public static function root()
    {
    }

    public static function url()
    {
    }

    public static function fullUrl()
    {
    }

    public static function path()
    {
    }

    public static function decodedPath()
    {
    }

    public static function segment($index, $default = NULL)
    {
    }

    public static function segments()
    {
    }

    public static function is()
    {
    }

    public static function ajax()
    {
    }

    public static function pjax()
    {
    }

    public static function secure()
    {
    }

    public static function ip()
    {
    }

    public static function ips()
    {
    }

    public static function exists($key)
    {
    }

    public static function has($key)
    {
    }

    public static function all()
    {
    }

    public static function input($key = NULL, $default = NULL)
    {
    }

    public static function only($keys)
    {
    }

    public static function except($keys)
    {
    }

    public static function query($key = NULL, $default = NULL)
    {
    }

    public static function hasCookie($key)
    {
    }

    public static function cookie($key = NULL, $default = NULL)
    {
    }

    public static function file($key = NULL, $default = NULL)
    {
    }

    public static function hasFile($key)
    {
    }

    public static function header($key = NULL, $default = NULL)
    {
    }

    public static function server($key = NULL, $default = NULL)
    {
    }

    public static function old($key = NULL, $default = NULL)
    {
    }

    public static function flash($filter = NULL, $keys = array (
    ))
    {
    }

    public static function flashOnly($keys)
    {
    }

    public static function flashExcept($keys)
    {
    }

    public static function flush()
    {
    }

    public static function merge($input)
    {
    }

    public static function replace($input)
    {
    }

    public static function json($key = NULL, $default = NULL)
    {
    }

    public static function isJson()
    {
    }

    public static function wantsJson()
    {
    }

    public static function accepts($contentTypes)
    {
    }

    public static function acceptsJson()
    {
    }

    public static function acceptsHtml()
    {
    }

    public static function format($default = 'html')
    {
    }

    public static function createFromBase($request)
    {
    }

    public static function duplicate($query = NULL, $request = NULL, $attributes = NULL, $cookies = NULL, $files = NULL, $server = NULL)
    {
    }

    public static function session()
    {
    }

    public static function user()
    {
    }

    public static function route()
    {
    }

    public static function getUserResolver()
    {
    }

    public static function setUserResolver($callback)
    {
    }

    public static function getRouteResolver()
    {
    }

    public static function setRouteResolver($callback)
    {
    }

    public static function offsetExists($offset)
    {
    }

    public static function offsetGet($offset)
    {
    }

    public static function offsetSet($offset, $value)
    {
    }

    public static function offsetUnset($offset)
    {
    }

    public static function initialize($query = array (
    ), $request = array (
    ), $attributes = array (
    ), $cookies = array (
    ), $files = array (
    ), $server = array (
    ), $content = NULL)
    {
    }

    public static function createFromGlobals()
    {
    }

    public static function create($uri, $method = 'GET', $parameters = array (
    ), $cookies = array (
    ), $files = array (
    ), $server = array (
    ), $content = NULL)
    {
    }

    public static function setFactory($callable)
    {
    }

    public static function overrideGlobals()
    {
    }

    public static function setTrustedProxies($proxies)
    {
    }

    public static function getTrustedProxies()
    {
    }

    public static function setTrustedHosts($hostPatterns)
    {
    }

    public static function getTrustedHosts()
    {
    }

    public static function setTrustedHeaderName($key, $value)
    {
    }

    public static function getTrustedHeaderName($key)
    {
    }

    public static function normalizeQueryString($qs)
    {
    }

    public static function enableHttpMethodParameterOverride()
    {
    }

    public static function getHttpMethodParameterOverride()
    {
    }

    public static function get($key, $default = NULL, $deep = false)
    {
    }

    public static function getSession()
    {
    }

    public static function hasPreviousSession()
    {
    }

    public static function hasSession()
    {
    }

    public static function setSession($session)
    {
    }

    public static function getClientIps()
    {
    }

    public static function getClientIp()
    {
    }

    public static function getScriptName()
    {
    }

    public static function getPathInfo()
    {
    }

    public static function getBasePath()
    {
    }

    public static function getBaseUrl()
    {
    }

    public static function getScheme()
    {
    }

    public static function getPort()
    {
    }

    public static function getUser()
    {
    }

    public static function getPassword()
    {
    }

    public static function getUserInfo()
    {
    }

    public static function getHttpHost()
    {
    }

    public static function getRequestUri()
    {
    }

    public static function getSchemeAndHttpHost()
    {
    }

    public static function getUri()
    {
    }

    public static function getUriForPath($path)
    {
    }

    public static function getRelativeUriForPath($path)
    {
    }

    public static function getQueryString()
    {
    }

    public static function isSecure()
    {
    }

    public static function getHost()
    {
    }

    public static function setMethod($method)
    {
    }

    public static function getMethod()
    {
    }

    public static function getRealMethod()
    {
    }

    public static function getMimeType($format)
    {
    }

    public static function getFormat($mimeType)
    {
    }

    public static function setFormat($format, $mimeTypes)
    {
    }

    public static function getRequestFormat($default = 'html')
    {
    }

    public static function setRequestFormat($format)
    {
    }

    public static function getContentType()
    {
    }

    public static function setDefaultLocale($locale)
    {
    }

    public static function getDefaultLocale()
    {
    }

    public static function setLocale($locale)
    {
    }

    public static function getLocale()
    {
    }

    public static function isMethod($method)
    {
    }

    public static function isMethodSafe()
    {
    }

    public static function getContent($asResource = false)
    {
    }

    public static function getETags()
    {
    }

    public static function isNoCache()
    {
    }

    public static function getPreferredLanguage($locales = NULL)
    {
    }

    public static function getLanguages()
    {
    }

    public static function getCharsets()
    {
    }

    public static function getEncodings()
    {
    }

    public static function getAcceptableContentTypes()
    {
    }

    public static function isXmlHttpRequest()
    {
    }
}

class Lang extends Illuminate\Translation\Translator
{

    public static function has($key, $locale = NULL)
    {
    }

    public static function get($key, $replace = array (
    ), $locale = NULL)
    {
    }

    public static function choice($key, $number, $replace = array (
    ), $locale = NULL)
    {
    }

    public static function trans($id, $parameters = array (
    ), $domain = 'messages', $locale = NULL)
    {
    }

    public static function transChoice($id, $number, $parameters = array (
    ), $domain = 'messages', $locale = NULL)
    {
    }

    public static function load($namespace, $group, $locale)
    {
    }

    public static function addNamespace($namespace, $hint)
    {
    }

    public static function parseKey($key)
    {
    }

    public static function getSelector()
    {
    }

    public static function setSelector($selector)
    {
    }

    public static function getLoader()
    {
    }

    public static function locale()
    {
    }

    public static function getLocale()
    {
    }

    public static function setLocale($locale)
    {
    }

    public static function getFallback()
    {
    }

    public static function setFallback($fallback)
    {
    }

    public static function setParsedKey($key, $parsed)
    {
    }
}

class Log extends Illuminate\Log\Writer
{

    public static function emergency($message, $context = array (
    ))
    {
    }

    public static function alert($message, $context = array (
    ))
    {
    }

    public static function critical($message, $context = array (
    ))
    {
    }

    public static function error($message, $context = array (
    ))
    {
    }

    public static function warning($message, $context = array (
    ))
    {
    }

    public static function notice($message, $context = array (
    ))
    {
    }

    public static function info($message, $context = array (
    ))
    {
    }

    public static function debug($message, $context = array (
    ))
    {
    }

    public static function log($level, $message, $context = array (
    ))
    {
    }

    public static function write($level, $message, $context = array (
    ))
    {
    }

    public static function useFiles($path, $level = 'debug')
    {
    }

    public static function useDailyFiles($path, $days = 0, $level = 'debug')
    {
    }

    public static function useSyslog($name = 'laravel', $level = 'debug')
    {
    }

    public static function useErrorLog($level = 'debug', $messageType = 0)
    {
    }

    public static function listen($callback)
    {
    }

    public static function getMonolog()
    {
    }

    public static function getEventDispatcher()
    {
    }

    public static function setEventDispatcher($dispatcher)
    {
    }
}

class Mail extends Illuminate\Mail\Mailer
{

    public static function alwaysFrom($address, $name = NULL)
    {
    }

    public static function alwaysTo($address, $name = NULL)
    {
    }

    public static function raw($text, $callback)
    {
    }

    public static function plain($view, $data, $callback)
    {
    }

    public static function send($view, $data, $callback)
    {
    }

    public static function queue($view, $data, $callback, $queue = NULL)
    {
    }

    public static function queueOn($queue, $view, $data, $callback)
    {
    }

    public static function later($delay, $view, $data, $callback, $queue = NULL)
    {
    }

    public static function laterOn($queue, $delay, $view, $data, $callback)
    {
    }

    public static function handleQueuedMessage($job, $data)
    {
    }

    public static function pretend($value = true)
    {
    }

    public static function isPretending()
    {
    }

    public static function getViewFactory()
    {
    }

    public static function getSwiftMailer()
    {
    }

    public static function failures()
    {
    }

    public static function setSwiftMailer($swift)
    {
    }

    public static function setLogger($logger)
    {
    }

    public static function setQueue($queue)
    {
    }

    public static function setContainer($container)
    {
    }
}

class Queue extends Illuminate\Queue\QueueManager
{

    public static function looping($callback)
    {
    }

    public static function failing($callback)
    {
    }

    public static function stopping($callback)
    {
    }

    public static function connected($name = NULL)
    {
    }

    public static function connection($name = NULL)
    {
    }

    public static function extend($driver, $resolver)
    {
    }

    public static function addConnector($driver, $resolver)
    {
    }

    public static function getDefaultDriver()
    {
    }

    public static function setDefaultDriver($name)
    {
    }

    public static function getName($connection = NULL)
    {
    }

    public static function isDownForMaintenance()
    {
    }
}

class Redirect extends Illuminate\Routing\Redirector
{

    public static function home($status = 302)
    {
    }

    public static function back($status = 302, $headers = array (
    ))
    {
    }

    public static function refresh($status = 302, $headers = array (
    ))
    {
    }

    public static function guest($path, $status = 302, $headers = array (
    ), $secure = NULL)
    {
    }

    public static function intended($default = '/', $status = 302, $headers = array (
    ), $secure = NULL)
    {
    }

    public static function to($path, $status = 302, $headers = array (
    ), $secure = NULL)
    {
    }

    public static function away($path, $status = 302, $headers = array (
    ))
    {
    }

    public static function secure($path, $status = 302, $headers = array (
    ))
    {
    }

    public static function route($route, $parameters = array (
    ), $status = 302, $headers = array (
    ))
    {
    }

    public static function action($action, $parameters = array (
    ), $status = 302, $headers = array (
    ))
    {
    }

    public static function getUrlGenerator()
    {
    }

    public static function setSession($session)
    {
    }
}

class Request extends Illuminate\Http\Request
{

    public static function capture()
    {
    }

    public static function instance()
    {
    }

    public static function method()
    {
    }

    public static function root()
    {
    }

    public static function url()
    {
    }

    public static function fullUrl()
    {
    }

    public static function path()
    {
    }

    public static function decodedPath()
    {
    }

    public static function segment($index, $default = NULL)
    {
    }

    public static function segments()
    {
    }

    public static function is()
    {
    }

    public static function ajax()
    {
    }

    public static function pjax()
    {
    }

    public static function secure()
    {
    }

    public static function ip()
    {
    }

    public static function ips()
    {
    }

    public static function exists($key)
    {
    }

    public static function has($key)
    {
    }

    public static function all()
    {
    }

    public static function input($key = NULL, $default = NULL)
    {
    }

    public static function only($keys)
    {
    }

    public static function except($keys)
    {
    }

    public static function query($key = NULL, $default = NULL)
    {
    }

    public static function hasCookie($key)
    {
    }

    public static function cookie($key = NULL, $default = NULL)
    {
    }

    public static function file($key = NULL, $default = NULL)
    {
    }

    public static function hasFile($key)
    {
    }

    public static function header($key = NULL, $default = NULL)
    {
    }

    public static function server($key = NULL, $default = NULL)
    {
    }

    public static function old($key = NULL, $default = NULL)
    {
    }

    public static function flash($filter = NULL, $keys = array (
    ))
    {
    }

    public static function flashOnly($keys)
    {
    }

    public static function flashExcept($keys)
    {
    }

    public static function flush()
    {
    }

    public static function merge($input)
    {
    }

    public static function replace($input)
    {
    }

    public static function json($key = NULL, $default = NULL)
    {
    }

    public static function isJson()
    {
    }

    public static function wantsJson()
    {
    }

    public static function accepts($contentTypes)
    {
    }

    public static function acceptsJson()
    {
    }

    public static function acceptsHtml()
    {
    }

    public static function format($default = 'html')
    {
    }

    public static function createFromBase($request)
    {
    }

    public static function duplicate($query = NULL, $request = NULL, $attributes = NULL, $cookies = NULL, $files = NULL, $server = NULL)
    {
    }

    public static function session()
    {
    }

    public static function user()
    {
    }

    public static function route()
    {
    }

    public static function getUserResolver()
    {
    }

    public static function setUserResolver($callback)
    {
    }

    public static function getRouteResolver()
    {
    }

    public static function setRouteResolver($callback)
    {
    }

    public static function offsetExists($offset)
    {
    }

    public static function offsetGet($offset)
    {
    }

    public static function offsetSet($offset, $value)
    {
    }

    public static function offsetUnset($offset)
    {
    }

    public static function initialize($query = array (
    ), $request = array (
    ), $attributes = array (
    ), $cookies = array (
    ), $files = array (
    ), $server = array (
    ), $content = NULL)
    {
    }

    public static function createFromGlobals()
    {
    }

    public static function create($uri, $method = 'GET', $parameters = array (
    ), $cookies = array (
    ), $files = array (
    ), $server = array (
    ), $content = NULL)
    {
    }

    public static function setFactory($callable)
    {
    }

    public static function overrideGlobals()
    {
    }

    public static function setTrustedProxies($proxies)
    {
    }

    public static function getTrustedProxies()
    {
    }

    public static function setTrustedHosts($hostPatterns)
    {
    }

    public static function getTrustedHosts()
    {
    }

    public static function setTrustedHeaderName($key, $value)
    {
    }

    public static function getTrustedHeaderName($key)
    {
    }

    public static function normalizeQueryString($qs)
    {
    }

    public static function enableHttpMethodParameterOverride()
    {
    }

    public static function getHttpMethodParameterOverride()
    {
    }

    public static function get($key, $default = NULL, $deep = false)
    {
    }

    public static function getSession()
    {
    }

    public static function hasPreviousSession()
    {
    }

    public static function hasSession()
    {
    }

    public static function setSession($session)
    {
    }

    public static function getClientIps()
    {
    }

    public static function getClientIp()
    {
    }

    public static function getScriptName()
    {
    }

    public static function getPathInfo()
    {
    }

    public static function getBasePath()
    {
    }

    public static function getBaseUrl()
    {
    }

    public static function getScheme()
    {
    }

    public static function getPort()
    {
    }

    public static function getUser()
    {
    }

    public static function getPassword()
    {
    }

    public static function getUserInfo()
    {
    }

    public static function getHttpHost()
    {
    }

    public static function getRequestUri()
    {
    }

    public static function getSchemeAndHttpHost()
    {
    }

    public static function getUri()
    {
    }

    public static function getUriForPath($path)
    {
    }

    public static function getRelativeUriForPath($path)
    {
    }

    public static function getQueryString()
    {
    }

    public static function isSecure()
    {
    }

    public static function getHost()
    {
    }

    public static function setMethod($method)
    {
    }

    public static function getMethod()
    {
    }

    public static function getRealMethod()
    {
    }

    public static function getMimeType($format)
    {
    }

    public static function getFormat($mimeType)
    {
    }

    public static function setFormat($format, $mimeTypes)
    {
    }

    public static function getRequestFormat($default = 'html')
    {
    }

    public static function setRequestFormat($format)
    {
    }

    public static function getContentType()
    {
    }

    public static function setDefaultLocale($locale)
    {
    }

    public static function getDefaultLocale()
    {
    }

    public static function setLocale($locale)
    {
    }

    public static function getLocale()
    {
    }

    public static function isMethod($method)
    {
    }

    public static function isMethodSafe()
    {
    }

    public static function getContent($asResource = false)
    {
    }

    public static function getETags()
    {
    }

    public static function isNoCache()
    {
    }

    public static function getPreferredLanguage($locales = NULL)
    {
    }

    public static function getLanguages()
    {
    }

    public static function getCharsets()
    {
    }

    public static function getEncodings()
    {
    }

    public static function getAcceptableContentTypes()
    {
    }

    public static function isXmlHttpRequest()
    {
    }
}

class Response extends Illuminate\Routing\ResponseFactory
{

    public static function make($content = '', $status = 200, $headers = array (
    ))
    {
    }

    public static function view($view, $data = array (
    ), $status = 200, $headers = array (
    ))
    {
    }

    public static function json($data = array (
    ), $status = 200, $headers = array (
    ), $options = 0)
    {
    }

    public static function jsonp($callback, $data = array (
    ), $status = 200, $headers = array (
    ), $options = 0)
    {
    }

    public static function stream($callback, $status = 200, $headers = array (
    ))
    {
    }

    public static function download($file, $name = NULL, $headers = array (
    ), $disposition = 'attachment')
    {
    }

    public static function redirectTo($path, $status = 302, $headers = array (
    ), $secure = NULL)
    {
    }

    public static function redirectToRoute($route, $parameters = array (
    ), $status = 302, $headers = array (
    ))
    {
    }

    public static function redirectToAction($action, $parameters = array (
    ), $status = 302, $headers = array (
    ))
    {
    }

    public static function redirectGuest($path, $status = 302, $headers = array (
    ), $secure = NULL)
    {
    }

    public static function redirectToIntended($default = '/', $status = 302, $headers = array (
    ), $secure = NULL)
    {
    }

    public static function macro($name, $macro)
    {
    }

    public static function hasMacro($name)
    {
    }
}

class Route extends Illuminate\Routing\Router
{

    public static function get($uri, $action)
    {
    }

    public static function post($uri, $action)
    {
    }

    public static function put($uri, $action)
    {
    }

    public static function patch($uri, $action)
    {
    }

    public static function delete($uri, $action)
    {
    }

    public static function options($uri, $action)
    {
    }

    public static function any($uri, $action)
    {
    }

    public static function match($methods, $uri, $action)
    {
    }

    public static function controllers($controllers)
    {
    }

    public static function controller($uri, $controller, $names = array (
    ))
    {
    }

    public static function resources($resources)
    {
    }

    public static function resource($name, $controller, $options = array (
    ))
    {
    }

    public static function group($attributes, $callback)
    {
    }

    public static function mergeWithLastGroup($new)
    {
    }

    public static function mergeGroup($new, $old)
    {
    }

    public static function getLastGroupPrefix()
    {
    }

    public static function dispatch($request)
    {
    }

    public static function dispatchToRoute($request)
    {
    }

    public static function gatherRouteMiddlewares($route)
    {
    }

    public static function resolveMiddlewareClassName($name)
    {
    }

    public static function matched($callback)
    {
    }

    public static function before($callback)
    {
    }

    public static function after($callback)
    {
    }

    public static function getMiddleware()
    {
    }

    public static function middleware($name, $class)
    {
    }

    public static function filter($name, $callback)
    {
    }

    public static function when($pattern, $name, $methods = NULL)
    {
    }

    public static function whenRegex($pattern, $name, $methods = NULL)
    {
    }

    public static function model($key, $class, $callback = NULL)
    {
    }

    public static function bind($key, $binder)
    {
    }

    public static function createClassBinding($binding)
    {
    }

    public static function pattern($key, $pattern)
    {
    }

    public static function patterns($patterns)
    {
    }

    public static function callRouteBefore($route, $request)
    {
    }

    public static function findPatternFilters($request)
    {
    }

    public static function callRouteAfter($route, $request, $response)
    {
    }

    public static function callRouteFilter($filter, $parameters, $route, $request, $response = NULL)
    {
    }

    public static function prepareResponse($request, $response)
    {
    }

    public static function hasGroupStack()
    {
    }

    public static function getGroupStack()
    {
    }

    public static function input($key, $default = NULL)
    {
    }

    public static function getCurrentRoute()
    {
    }

    public static function current()
    {
    }

    public static function has($name)
    {
    }

    public static function currentRouteName()
    {
    }

    public static function is()
    {
    }

    public static function currentRouteNamed($name)
    {
    }

    public static function currentRouteAction()
    {
    }

    public static function uses()
    {
    }

    public static function currentRouteUses($action)
    {
    }

    public static function getCurrentRequest()
    {
    }

    public static function getRoutes()
    {
    }

    public static function setRoutes($routes)
    {
    }

    public static function getPatterns()
    {
    }

    public static function macro($name, $macro)
    {
    }

    public static function hasMacro($name)
    {
    }
}

class Session extends Illuminate\Session\SessionManager
{

    public static function getSessionConfig()
    {
    }

    public static function getDefaultDriver()
    {
    }

    public static function setDefaultDriver($name)
    {
    }

    public static function driver($driver = NULL)
    {
    }

    public static function extend($driver, $callback)
    {
    }

    public static function getDrivers()
    {
    }
}

class Storage extends Illuminate\Filesystem\FilesystemManager
{

    public static function drive($name = NULL)
    {
    }

    public static function disk($name = NULL)
    {
    }

    public static function createLocalDriver($config)
    {
    }

    public static function createFtpDriver($config)
    {
    }

    public static function createS3Driver($config)
    {
    }

    public static function createRackspaceDriver($config)
    {
    }

    public static function getDefaultDriver()
    {
    }

    public static function extend($driver, $callback)
    {
    }
}

class URL extends Illuminate\Routing\UrlGenerator
{

    public static function full()
    {
    }

    public static function current()
    {
    }

    public static function previous()
    {
    }

    public static function to($path, $extra = array (
    ), $secure = NULL)
    {
    }

    public static function secure($path, $parameters = array (
    ))
    {
    }

    public static function asset($path, $secure = NULL)
    {
    }

    public static function secureAsset($path)
    {
    }

    public static function forceSchema($schema)
    {
    }

    public static function route($name, $parameters = array (
    ), $absolute = true)
    {
    }

    public static function action($action, $parameters = array (
    ), $absolute = true)
    {
    }

    public static function forceRootUrl($root)
    {
    }

    public static function isValidUrl($path)
    {
    }

    public static function getRequest()
    {
    }

    public static function setRequest($request)
    {
    }

    public static function setRoutes($routes)
    {
    }

    public static function setSessionResolver($sessionResolver)
    {
    }

    public static function setRootControllerNamespace($rootNamespace)
    {
    }
}

class Validator extends Illuminate\Validation\Factory
{

    public static function make($data, $rules, $messages = array (
    ), $customAttributes = array (
    ))
    {
    }

    public static function extend($rule, $extension, $message = NULL)
    {
    }

    public static function extendImplicit($rule, $extension, $message = NULL)
    {
    }

    public static function replacer($rule, $replacer)
    {
    }

    public static function resolver($resolver)
    {
    }

    public static function getTranslator()
    {
    }

    public static function getPresenceVerifier()
    {
    }

    public static function setPresenceVerifier($presenceVerifier)
    {
    }
}

class View extends Illuminate\View\Factory
{

    public static function file($path, $data = array (
    ), $mergeData = array (
    ))
    {
    }

    public static function make($view, $data = array (
    ), $mergeData = array (
    ))
    {
    }

    public static function of($view, $data = array (
    ))
    {
    }

    public static function name($view, $name)
    {
    }

    public static function alias($view, $alias)
    {
    }

    public static function exists($view)
    {
    }

    public static function renderEach($view, $data, $iterator, $empty = 'raw|')
    {
    }

    public static function getEngineFromPath($path)
    {
    }

    public static function share($key, $value = NULL)
    {
    }

    public static function creator($views, $callback)
    {
    }

    public static function composers($composers)
    {
    }

    public static function composer($views, $callback, $priority = NULL)
    {
    }

    public static function callComposer($view)
    {
    }

    public static function callCreator($view)
    {
    }

    public static function startSection($section, $content = '')
    {
    }

    public static function inject($section, $content)
    {
    }

    public static function yieldSection()
    {
    }

    public static function stopSection($overwrite = false)
    {
    }

    public static function appendSection()
    {
    }

    public static function yieldContent($section, $default = '')
    {
    }

    public static function flushSections()
    {
    }

    public static function flushSectionsIfDoneRendering()
    {
    }

    public static function incrementRender()
    {
    }

    public static function decrementRender()
    {
    }

    public static function doneRendering()
    {
    }

    public static function addLocation($location)
    {
    }

    public static function addNamespace($namespace, $hints)
    {
    }

    public static function prependNamespace($namespace, $hints)
    {
    }

    public static function addExtension($extension, $engine, $resolver = NULL)
    {
    }

    public static function getExtensions()
    {
    }

    public static function getEngineResolver()
    {
    }

    public static function getFinder()
    {
    }

    public static function setFinder($finder)
    {
    }

    public static function getDispatcher()
    {
    }

    public static function setDispatcher($events)
    {
    }

    public static function getContainer()
    {
    }

    public static function setContainer($container)
    {
    }

    public static function shared($key, $default = NULL)
    {
    }

    public static function getShared()
    {
    }

    public static function hasSection($name)
    {
    }

    public static function getSections()
    {
    }

    public static function getNames()
    {
    }
}
