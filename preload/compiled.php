<?php
namespace Illuminate\Support;

class ClassLoader
{
    /**
     * The registered directories.
     *
     * @var array
     */
    protected static $directories = array();
    /**
     * Indicates if a ClassLoader has been registered.
     *
     * @var bool
     */
    protected static $registered = false;
    /**
     * Load the given class file.
     *
     * @param  string  $class
     * @return void
     */
    public static function load($class)
    {
        $class = static::normalizeClass($class);
        foreach (static::$directories as $directory) {
            if (file_exists($path = $directory . DIRECTORY_SEPARATOR . $class)) {
                require_once $path;
                return true;
            }
        }
    }
    /**
     * Get the normal file name for a class.
     *
     * @param  string  $class
     * @return string
     */
    public static function normalizeClass($class)
    {
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        return str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class) . '.php';
    }
    /**
     * Register the given class loader on the auto-loader stack.
     *
     * @return void
     */
    public static function register()
    {
        if (!static::$registered) {
            spl_autoload_register(array('\\Illuminate\\Support\\ClassLoader', 'load'));
            static::$registered = true;
        }
    }
    /**
     * Add directories to the class loader.
     *
     * @param  string|array  $directories
     * @return void
     */
    public static function addDirectories($directories)
    {
        static::$directories = array_merge(static::$directories, (array) $directories);
        static::$directories = array_unique(static::$directories);
    }
    /**
     * Remove directories from the class loader.
     *
     * @param  string|array  $directories
     * @return void
     */
    public static function removeDirectories($directories = null)
    {
        if (is_null($directories)) {
            static::$directories = array();
        } else {
            $directories = (array) $directories;
            static::$directories = array_filter(static::$directories, function ($directory) use($directories) {
                return !in_array($directory, $directories);
            });
        }
    }
    /**
     * Gets all the directories registered with the loader.
     *
     * @return array
     */
    public static function getDirectories()
    {
        return static::$directories;
    }
}
namespace Illuminate\Container;

use Closure, ArrayAccess, ReflectionParameter;
class BindingResolutionException extends \Exception
{
    
}
class Container implements ArrayAccess
{
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected $bindings = array();
    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = array();
    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected $aliases = array();
    /**
     * All of the registered resolving callbacks.
     *
     * @var array
     */
    protected $resolvingCallbacks = array();
    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this[$abstract]);
    }
    /**
     * Register a binding with the container.
     *
     * @param  string               $abstract
     * @param  Closure|string|null  $concrete
     * @param  bool                 $shared
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        // If the given type is actually an array, we'll assume an alias is being
        // defined and will grab the real abstract class name and register the
        // alias with the container so it can be used as a short-cut for it.
        if (is_array($abstract)) {
            list($abstract, $alias) = $this->extractAlias($abstract);
            $this->alias($abstract, $alias);
        }
        // If no concrete type was given, we will simply set the concrete type to
        // the abstract. This allows concrete types to be registered as shared
        // without being made state their classes in both of the parameters.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        // If the factory is not a Closure, it means it is just a class name that
        // is bound into the container to an abstract type and we'll just wrap
        // it up in a Closure to make things more convenient when extending.
        if (!$concrete instanceof Closure) {
            $concrete = function ($c) use($abstract, $concrete) {
                $method = $abstract == $concrete ? 'build' : 'make';
                return $c->{$method}($concrete);
            };
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }
    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string               $abstract
     * @param  Closure|string|null  $concrete
     * @param  bool                 $shared
     * @return bool
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        if (!isset($this[$abstract])) {
            $this->bind($abstract, $concrete, $shared);
        }
    }
    /**
     * Register a shared binding in the container.
     *
     * @param  string               $abstract
     * @param  Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        return $this->bind($abstract, $concrete, true);
    }
    /**
     * Wrap a Closure such that it is shared.
     *
     * @param  Closure  $closure
     * @return Closure
     */
    public function share(Closure $closure)
    {
        return function ($container) use($closure) {
            // We'll simply declare a static variable within the Closures and if
            // it has not been set we'll execute the given Closure to resolve
            // the value and return it back to the consumers of the method.
            static $object;
            if (is_null($object)) {
                $object = $closure($container);
            }
            return $object;
        };
    }
    /**
     * "Extend" an abstract type in the container.
     *
     * @param  string   $abstract
     * @param  Closure  $closure
     * @return void
     */
    public function extend($abstract, Closure $closure)
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \InvalidArgumentException("Type {$abstract} is not bound.");
        }
        // To "extend" a binding, we will grab the old "resolver" Closure and pass it
        // into a new one. The old resolver will be called first and the result is
        // handed off to the "new" resolver, along with this container instance.
        $resolver = $this->bindings[$abstract]['concrete'];
        $this->bind($abstract, function ($container) use($resolver, $closure) {
            return $closure($resolver($container), $container);
        }, $this->isShared($abstract));
    }
    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed   $instance
     * @return void
     */
    public function instance($abstract, $instance)
    {
        if (is_array($abstract)) {
            list($abstract, $alias) = $this->extractAlias($abstract);
            $this->alias($abstract, $alias);
        }
        $this->instances[$abstract] = $instance;
    }
    /**
     * Alias a type to a shorter name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
    }
    /**
     * Extract the type and alias from a given definition.
     *
     * @param  array  $definition
     * @return array
     */
    protected function extractAlias(array $definition)
    {
        return array(key($definition), current($definition));
    }
    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, $parameters = array())
    {
        $abstract = $this->getAlias($abstract);
        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        $concrete = $this->getConcrete($abstract);
        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete);
        }
        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        $this->fireResolvingCallbacks($object);
        return $object;
    }
    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string  $abstract
     * @return mixed   $concrete
     */
    protected function getConcrete($abstract)
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        } else {
            return $this->bindings[$abstract]['concrete'];
        }
    }
    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $concrete
     * @param  array   $parameters
     * @return mixed
     */
    public function build($concrete, $parameters = array())
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        $reflector = new \ReflectionClass($concrete);
        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            $message = "Target [{$concrete}] is not instantiable.";
            throw new BindingResolutionException($message);
        }
        $constructor = $reflector->getConstructor();
        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            return new $concrete();
        }
        $parameters = $constructor->getParameters();
        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $dependencies = $this->getDependencies($parameters);
        return $reflector->newInstanceArgs($dependencies);
    }
    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array  $parameterrs
     * @return array
     */
    protected function getDependencies($parameters)
    {
        $dependencies = array();
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we'll just bomb out with an error since we have no-where to go.
            if (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->make($dependency->name);
            }
        }
        return (array) $dependencies;
    }
    /**
     * Resolve a non-class hinted dependency.
     *
     * @param  ReflectionParameter  $parameter
     * @return mixed
     */
    protected function resolveNonClass(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } else {
            $message = "Unresolvable dependency resolving [{$parameter}].";
            throw new BindingResolutionException($message);
        }
    }
    /**
     * Register a new resolving callback.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function resolving(Closure $callback)
    {
        $this->resolvingCallbacks[] = $callback;
    }
    /**
     * Fire all of the resolving callbacks.
     *
     * @param  mixed  $object
     * @return void
     */
    protected function fireResolvingCallbacks($object)
    {
        foreach ($this->resolvingCallbacks as $callback) {
            call_user_func($callback, $object);
        }
    }
    /**
     * Determine if a given type is shared.
     *
     * @param  string  $abstract
     * @return bool
     */
    protected function isShared($abstract)
    {
        $set = isset($this->bindings[$abstract]['shared']);
        return $set and $this->bindings[$abstract]['shared'] === true;
    }
    /**
     * Determine if the given concrete is buildable.
     *
     * @param  mixed   $concrete
     * @param  string  $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract or $concrete instanceof Closure;
    }
    /**
     * Get the alias for an abstract if available.
     *
     * @param  string  $abstract
     * @return string
     */
    protected function getAlias($abstract)
    {
        return isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;
    }
    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
    /**
     * Determine if a given offset exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->bindings[$key]);
    }
    /**
     * Get the value at a given offset.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }
    /**
     * Set the value at a given offset.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        // If the value is not a Closure, we will make it one. This simply gives
        // more "drop-in" replacement functionality for the Pimple which this
        // container's simplest functions are base modeled and built after.
        if (!$value instanceof Closure) {
            $value = function () use($value) {
                return $value;
            };
        }
        $this->bind($key, $value);
    }
    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->bindings[$key]);
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * HttpKernelInterface handles a Request to convert it to a Response.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
interface HttpKernelInterface
{
    const MASTER_REQUEST = 1;
    const SUB_REQUEST = 2;
    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param Request $request A Request instance
     * @param integer $type    The type of the request
     *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param Boolean $catch Whether to catch exceptions or not
     *
     * @return Response A Response instance
     *
     * @throws \Exception When an Exception occurs during processing
     *
     * @api
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true);
}
namespace Illuminate\Foundation;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Exception\ExceptionServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
class Application extends Container implements HttpKernelInterface
{
    /**
     * The Laravel framework version.
     *
     * @var string
     */
    const VERSION = '4.0.0';
    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;
    /**
     * Get the booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = array();
    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = array();
    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = array();
    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = array();
    /**
     * Create a new Illuminate application instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this['request'] = Request::createFromGlobals();
        // The exception handler class takes care of determining which of the bound
        // exception handler Closures should be called for a given exception and
        // gets the response from them. We'll bind it here to allow overrides.
        $this->register(new ExceptionServiceProvider($this));
        $this->register(new RoutingServiceProvider($this));
        $this->register(new EventServiceProvider($this));
    }
    /**
     * Bind the installation paths to the application.
     *
     * @param  array  $paths
     * @return void
     */
    public function bindInstallPaths(array $paths)
    {
        $this->instance('path', $paths['app']);
        $this->instance('path.base', $paths['base']);
        $this->instance('path.public', $paths['public']);
    }
    /**
     * Get the application bootstrap file.
     *
     * @return string
     */
    public static function getBootstrapFile()
    {
        return '/media/sf_Code/Laravel/app/vendor/laravel/framework/src/Illuminate/Foundation' . '/start.php';
    }
    /**
     * Register the aliased class loader.
     *
     * @param  array  $aliases
     * @return void
     */
    public function registerAliasLoader(array $aliases)
    {
        $loader = AliasLoader::getInstance($aliases);
        $loader->register();
    }
    /**
     * Start the exception handling for the request.
     *
     * @return void
     */
    public function startExceptionHandling()
    {
        $provider = array_first($this->serviceProviders, function ($key, $provider) {
            return $provider instanceof ExceptionServiceProvider;
        });
        $provider->startHandling($this);
    }
    /**
     * Get the current application environment.
     *
     * @return string
     */
    public function environment()
    {
        return $this['env'];
    }
    /**
     * Detect the application's current environment.
     *
     * @param  array|string  $environments
     * @return string
     */
    public function detectEnvironment($environments)
    {
        $base = $this['request']->getHost();
        $arguments = $this['request']->server->get('argv');
        if ($this->runningInConsole()) {
            return $this->detectConsoleEnvironment($base, $environments, $arguments);
        }
        return $this->detectWebEnvironment($base, $environments);
    }
    /**
     * Set the application environment for a web request.
     *
     * @param  string  $base
     * @param  array|string  $environments
     * @return string
     */
    protected function detectWebEnvironment($base, $environments)
    {
        // If the given environment is just a Closure, we will defer the environment
        // detection to the Closure the developer has provided, which allows them
        // to totally control the web environment detection if they require to.
        if ($environments instanceof Closure) {
            return $this['env'] = call_user_func($environments);
        }
        foreach ($environments as $environment => $hosts) {
            // To determine the current environment, we'll simply iterate through the
            // possible environments and look for a host that matches this host in
            // the request's context, then return back that environment's names.
            foreach ($hosts as $host) {
                if (str_is($host, $base) or $this->isMachine($host)) {
                    return $this['env'] = $environment;
                }
            }
        }
        return $this['env'] = 'production';
    }
    /**
     * Set the application environment from command-line arguments.
     *
     * @param  string  $base
     * @param  mixed   $environments
     * @param  array   $arguments
     * @return string
     */
    protected function detectConsoleEnvironment($base, $environments, array $arguments)
    {
        foreach ($arguments as $key => $value) {
            // For the console environmnet, we'll just look for an argument that starts
            // with "--env" then assume that it is setting the environment for every
            // operation being performed, and we'll use that environment's config.
            if (starts_with($value, '--env=')) {
                $segments = array_slice(explode('=', $value), 1);
                return $this['env'] = head($segments);
            }
        }
        return $this->detectWebEnvironment($base, $environments);
    }
    /**
     * Determine if the name matches the machine name.
     *
     * @param  string  $name
     * @return bool
     */
    protected function isMachine($name)
    {
        return gethostname() == $name;
    }
    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }
    /**
     * Determine if we are running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests()
    {
        return $this['env'] == 'testing';
    }
    /**
     * Register a service provider with the application.
     *
     * @param  Illuminate\Support\ServiceProvider  $provider
     * @param  array  $options
     * @return void
     */
    public function register(ServiceProvider $provider, $options = array())
    {
        $provider->register();
        // Once we have registered the service we will iterate through the options
        // and set each of them on the application so they will be available on
        // the actual loading of the service objects and for developer usage.
        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }
    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach (array_unique($this->deferredServices) as $provider) {
            $this->register($instance = new $provider($this));
            if ($this->booted) {
                $instance->boot();
            }
        }
        $this->deferredServices = array();
    }
    /**
     * Load the provider for a deferred service.
     *
     * @param  string  $service
     * @return void
     */
    protected function loadDeferredProvider($service)
    {
        $provider = $this->deferredServices[$service];
        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
        if (!isset($this->loadedProviders[$provider])) {
            $this->register($instance = new $provider($this));
            unset($this->deferredServices[$service]);
            if ($this->booted) {
                $instance->boot();
            }
        }
    }
    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, $parameters = array())
    {
        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
        return parent::make($abstract, $parameters);
    }
    /**
     * Register a "before" application filter.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function before($callback)
    {
        return $this['router']->before($callback);
    }
    /**
     * Register an "after" application filter.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function after($callback)
    {
        return $this['router']->after($callback);
    }
    /**
     * Register a "close" application filter.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function close($callback)
    {
        return $this['router']->close($callback);
    }
    /**
     * Register a "finish" application filter.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function finish($callback)
    {
        $this['router']->finish($callback);
    }
    /**
     * Handles the given request and delivers the response.
     *
     * @return void
     */
    public function run()
    {
        $response = $this->dispatch($this['request']);
        $response->send();
        $this['router']->callFinishFilter($this['request'], $response);
    }
    /**
     * Handle the given request and get the response.
     *
     * @param  Illuminate\Foundation\Request  $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        return $this['router']->dispatch($this->prepareRequest($request));
    }
    /**
     * Handle the given request and get the response.
     *
     * Provides compatibility with BrowserKit functional testing.
     *
     * @implements HttpKernelInterface::handle
     *
     * @param  Illuminate\Foundation\Request  $request
     * @param  int   $type
     * @param  bool  $catch
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this['request'] = $request;
        return $this->dispatch($request);
    }
    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }
        // To boot the application we will simply spin through each service provider
        // and call the boot method, which will give them a chance to override on
        // something that was registered by another provider when it registers.
        foreach ($this->serviceProviders as $provider) {
            $provider->boot();
        }
        $this->fireBootingCallbacks();
        $this->booted = true;
    }
    /**
     * Register a new boot listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }
    /**
     * Call the booting callbacks for the application.
     *
     * @return void
     */
    protected function fireBootingCallbacks()
    {
        foreach ($this->bootingCallbacks as $callback) {
            call_user_func($callback, $this);
        }
    }
    /**
     * Prepare the request by injecting any services.
     *
     * @param  Illuminate\Foundation\Request  $request
     * @return Illuminate\Foundation\Request
     */
    public function prepareRequest(Request $request)
    {
        if (isset($this['session'])) {
            $request->setSessionStore($this['session']);
        }
        return $request;
    }
    /**
     * Prepare the given value as a Response object.
     *
     * @param  mixed  $value
     * @param  Illuminate\Foundation\Request  $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function prepareResponse($value, Request $request)
    {
        if (!$value instanceof SymfonyResponse) {
            $value = new Response($value);
        }
        return $value->prepare($request);
    }
    /**
     * Set the current application locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);
        $this['translator']->setLocale($locale);
        $this['events']->fire('locale.changed', array($locale));
    }
    /**
     * Throw an HttpException with the given data.
     *
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     */
    public function abort($code, $message = '', array $headers = array())
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        } else {
            throw new HttpException($code, $message, null, $headers);
        }
    }
    /**
     * Register a 404 error handler.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function missing(Closure $callback)
    {
        $this->error(function (NotFoundHttpException $e) use($callback) {
            return call_user_func($callback, $e);
        });
    }
    /**
     * Register an application error handler.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        $this['exception']->error($callback);
    }
    /**
     * Register an error handler for fatal errors.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function fatal(Closure $callback)
    {
        $this->error(function (FatalErrorException $e) use($callback) {
            return call_user_func($callback, $e);
        });
    }
    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }
    /**
     * Set the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }
    /**
     * Dynamically access application services.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }
    /**
     * Dynamically set application services.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
namespace Illuminate\Http;

use Illuminate\Session\Store as SessionStore;
class Request extends \Symfony\Component\HttpFoundation\Request
{
    /**
     * The Illuminate session store implementation.
     *
     * @var Illuminate\Session\Store
     */
    protected $sessionStore;
    /**
     * Return the Request instance.
     *
     * @return Illuminate\Http\Request
     */
    public function instance()
    {
        return $this;
    }
    /**
     * Get the root URL for the application.
     *
     * @return string
     */
    public function root()
    {
        return rtrim($this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/');
    }
    /**
     * Get the URL (no query string) for the request.
     *
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\\?.*/', '', $this->getUri()), '/');
    }
    /**
     * Get the full URL for the request.
     *
     * @return string
     */
    public function fullUrl()
    {
        return rtrim($this->getUri(), '/');
    }
    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path()
    {
        $pattern = trim($this->getPathInfo(), '/');
        return $pattern == '' ? '/' : $pattern;
    }
    /**
     * Get a segment from the URI (1 based index).
     *
     * @param  string  $index
     * @param  mixed   $default
     * @return string
     */
    public function segment($index, $default = null)
    {
        $segments = explode('/', trim($this->getPathInfo(), '/'));
        $segments = array_filter($segments, function ($v) {
            return $v != '';
        });
        return array_get($segments, $index - 1, $default);
    }
    /**
     * Get all of the segments for the request path.
     *
     * @return array
     */
    public function segments()
    {
        $path = $this->path();
        return $path == '/' ? array() : explode('/', $path);
    }
    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param  string  $pattern
     * @return bool
     */
    public function is($pattern)
    {
        foreach (func_get_args() as $pattern) {
            if (str_is($pattern, $this->path())) {
                return true;
            }
        }
        return false;
    }
    /**
     * Determine if the request is the result of an AJAX call.
     * 
     * @return bool
     */
    public function ajax()
    {
        return $this->isXmlHttpRequest();
    }
    /**
     * Determine if the request is over HTTPS.
     *
     * @return bool
     */
    public function secure()
    {
        return $this->isSecure();
    }
    /**
     * Determine if the request contains a given input item.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        if (count(func_get_args()) > 1) {
            foreach (func_get_args() as $value) {
                if (!$this->has($value)) {
                    return false;
                }
            }
            return true;
        }
        if (is_array($this->input($key))) {
            return true;
        }
        return trim((string) $this->input($key)) !== '';
    }
    /**
     * Get all of the input and files for the request.
     *
     * @return array
     */
    public function all()
    {
        return array_merge($this->input(), $this->files->all());
    }
    /**
     * Retrieve an input item from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public function input($key = null, $default = null)
    {
        $input = array_merge($this->getInputSource()->all(), $this->query->all());
        return array_get($input, $key, $default);
    }
    /**
     * Get a subset of the items from the input data.
     *
     * @param  array  $keys
     * @return array
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return array_intersect_key($this->input(), array_flip((array) $keys));
    }
    /**
     * Get all of the input except for a specified array of items.
     *
     * @param  array  $keys
     * @return array
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return array_diff_key($this->input(), array_flip((array) $keys));
    }
    /**
     * Retrieve a query string item from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public function query($key = null, $default = null)
    {
        return $this->retrieveItem('query', $key, $default);
    }
    /**
     * Retrieve a cookie from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public function cookie($key = null, $default = null)
    {
        return $this->retrieveItem('cookies', $key, $default);
    }
    /**
     * Retrieve a file from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function file($key = null, $default = null)
    {
        return $this->retrieveItem('files', $key, $default);
    }
    /**
     * Determine if the uploaded data contains a file.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasFile($key)
    {
        return $this->files->has($key) and !is_null($this->file($key));
    }
    /**
     * Retrieve a header from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public function header($key = null, $default = null)
    {
        return $this->retrieveItem('headers', $key, $default);
    }
    /**
     * Retrieve a server variable from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public function server($key = null, $default = null)
    {
        return $this->retrieveItem('server', $key, $default);
    }
    /**
     * Retrieve an old input item.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public function old($key = null, $default = null)
    {
        return $this->getSessionStore()->getOldInput($key, $default);
    }
    /**
     * Flash the input for the current request to the session.
     *
     * @param  string $filter
     * @param  array  $keys
     * @return void
     */
    public function flash($filter = null, $keys = array())
    {
        $flash = !is_null($filter) ? $this->{$filter}($keys) : $this->input();
        $this->getSessionStore()->flashInput($flash);
    }
    /**
     * Flash only some of the input to the session.
     *
     * @param  dynamic  string
     * @return void
     */
    public function flashOnly($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return $this->flash('only', $keys);
    }
    /**
     * Flash only some of the input to the session.
     *
     * @param  dynamic  string
     * @return void
     */
    public function flashExcept($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return $this->flash('except', $keys);
    }
    /**
     * Flush all of the old input from the session.
     *
     * @return void
     */
    public function flush()
    {
        $this->getSessionStore()->flashInput(array());
    }
    /**
     * Retrieve a parameter item from a given source.
     *
     * @param  string  $source
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    protected function retrieveItem($source, $key, $default)
    {
        if (is_null($key)) {
            return $this->{$source}->all();
        } else {
            return $this->{$source}->get($key, $default, true);
        }
    }
    /**
     * Merge new input into the current request's input array.
     *
     * @param  array  $input
     * @return void
     */
    public function merge(array $input)
    {
        $this->getInputSource()->add($input);
    }
    /**
     * Replace the input for the current request.
     *
     * @param  array  $input
     * @return void
     */
    public function replace(array $input)
    {
        $this->getInputSource()->replace($input);
    }
    /**
     * Get the JSON payload for the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function json($key = null, $default = null)
    {
        $json = json_decode($this->getContent(), true);
        return $json ? array_get($json, $key, $default) : false;
    }
    /**
     * Get the input source for the request.
     *
     * @return Symfony\Component\HttpFoundation\ParameterBag
     */
    protected function getInputSource()
    {
        return $this->getMethod() == 'GET' ? $this->query : $this->request;
    }
    /**
     * Get the Illuminate session store implementation.
     *
     * @return Illuminate\Session\Store
     */
    public function getSessionStore()
    {
        if (!isset($this->sessionStore)) {
            throw new \RuntimeException('Session store not set on request.');
        }
        return $this->sessionStore;
    }
    /**
     * Set the Illuminate session store implementation.
     *
     * @param  Illuminate\Session\Store  $session
     * @return void
     */
    public function setSessionStore(SessionStore $session)
    {
        $this->sessionStore = $session;
    }
    /**
     * Determine if the session store has been set.
     *
     * @return bool
     */
    public function hasSessionStore()
    {
        return isset($this->sessionStore);
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
/**
 * Request represents an HTTP request.
 *
 * The methods dealing with URL accept / return a raw path (% encoded):
 *   * getBasePath
 *   * getBaseUrl
 *   * getPathInfo
 *   * getRequestUri
 *   * getUri
 *   * getUriForPath
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class Request
{
    const HEADER_CLIENT_IP = 'client_ip';
    const HEADER_CLIENT_HOST = 'client_host';
    const HEADER_CLIENT_PROTO = 'client_proto';
    const HEADER_CLIENT_PORT = 'client_port';
    protected static $trustProxy = false;
    protected static $trustedProxies = array();
    /**
     * Names for headers that can be trusted when
     * using trusted proxies.
     *
     * The default names are non-standard, but widely used
     * by popular reverse proxies (like Apache mod_proxy or Amazon EC2).
     */
    protected static $trustedHeaders = array(self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR', self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST', self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO', self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT');
    protected static $httpMethodParameterOverride = false;
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     *
     * @api
     */
    public $attributes;
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     *
     * @api
     */
    public $request;
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     *
     * @api
     */
    public $query;
    /**
     * @var \Symfony\Component\HttpFoundation\ServerBag
     *
     * @api
     */
    public $server;
    /**
     * @var \Symfony\Component\HttpFoundation\FileBag
     *
     * @api
     */
    public $files;
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     *
     * @api
     */
    public $cookies;
    /**
     * @var \Symfony\Component\HttpFoundation\HeaderBag
     *
     * @api
     */
    public $headers;
    /**
     * @var string
     */
    protected $content;
    /**
     * @var array
     */
    protected $languages;
    /**
     * @var array
     */
    protected $charsets;
    /**
     * @var array
     */
    protected $acceptableContentTypes;
    /**
     * @var string
     */
    protected $pathInfo;
    /**
     * @var string
     */
    protected $requestUri;
    /**
     * @var string
     */
    protected $baseUrl;
    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var string
     */
    protected $format;
    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var string
     */
    protected $defaultLocale = 'en';
    /**
     * @var array
     */
    protected static $formats;
    /**
     * Constructor.
     *
     * @param array  $query      The GET parameters
     * @param array  $request    The POST parameters
     * @param array  $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array  $cookies    The COOKIE parameters
     * @param array  $files      The FILES parameters
     * @param array  $server     The SERVER parameters
     * @param string $content    The raw body data
     *
     * @api
     */
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }
    /**
     * Sets the parameters for this request.
     *
     * This method also re-initializes all properties.
     *
     * @param array  $query      The GET parameters
     * @param array  $request    The POST parameters
     * @param array  $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array  $cookies    The COOKIE parameters
     * @param array  $files      The FILES parameters
     * @param array  $server     The SERVER parameters
     * @param string $content    The raw body data
     *
     * @api
     */
    public function initialize(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new FileBag($files);
        $this->server = new ServerBag($server);
        $this->headers = new HeaderBag($this->server->getHeaders());
        $this->content = $content;
        $this->languages = null;
        $this->charsets = null;
        $this->acceptableContentTypes = null;
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
        $this->format = null;
    }
    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @return Request A new request
     *
     * @api
     */
    public static function createFromGlobals()
    {
        $request = new static($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded') && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }
        return $request;
    }
    /**
     * Creates a Request based on a given URI and configuration.
     *
     * The information contained in the URI always take precedence
     * over the other information (server and parameters).
     *
     * @param string $uri        The URI
     * @param string $method     The HTTP method
     * @param array  $parameters The query (GET) or request (POST) parameters
     * @param array  $cookies    The request cookies ($_COOKIE)
     * @param array  $files      The request files ($_FILES)
     * @param array  $server     The server parameters ($_SERVER)
     * @param string $content    The raw body data
     *
     * @return Request A Request instance
     *
     * @api
     */
    public static function create($uri, $method = 'GET', $parameters = array(), $cookies = array(), $files = array(), $server = array(), $content = null)
    {
        $server = array_replace(array('SERVER_NAME' => 'localhost', 'SERVER_PORT' => 80, 'HTTP_HOST' => 'localhost', 'HTTP_USER_AGENT' => 'Symfony/2.X', 'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5', 'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7', 'REMOTE_ADDR' => '127.0.0.1', 'SCRIPT_NAME' => '', 'SCRIPT_FILENAME' => '', 'SERVER_PROTOCOL' => 'HTTP/1.1', 'REQUEST_TIME' => time()), $server);
        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);
        $components = parse_url($uri);
        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }
        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }
        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] = $server['HTTP_HOST'] . ':' . $components['port'];
        }
        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }
        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }
        if (!isset($components['path'])) {
            $components['path'] = '/';
        }
        switch (strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
            case 'PATCH':
                $request = $parameters;
                $query = array();
                break;
            default:
                $request = array();
                $query = $parameters;
                break;
        }
        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);
            $query = array_replace($qs, $query);
        }
        $queryString = http_build_query($query, '', '&');
        $server['REQUEST_URI'] = $components['path'] . ('' !== $queryString ? '?' . $queryString : '');
        $server['QUERY_STRING'] = $queryString;
        return new static($query, $request, array(), $cookies, $files, $server, $content);
    }
    /**
     * Clones a request and overrides some of its parameters.
     *
     * @param array $query      The GET parameters
     * @param array $request    The POST parameters
     * @param array $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array $cookies    The COOKIE parameters
     * @param array $files      The FILES parameters
     * @param array $server     The SERVER parameters
     *
     * @return Request The duplicated request
     *
     * @api
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        $dup = clone $this;
        if ($query !== null) {
            $dup->query = new ParameterBag($query);
        }
        if ($request !== null) {
            $dup->request = new ParameterBag($request);
        }
        if ($attributes !== null) {
            $dup->attributes = new ParameterBag($attributes);
        }
        if ($cookies !== null) {
            $dup->cookies = new ParameterBag($cookies);
        }
        if ($files !== null) {
            $dup->files = new FileBag($files);
        }
        if ($server !== null) {
            $dup->server = new ServerBag($server);
            $dup->headers = new HeaderBag($dup->server->getHeaders());
        }
        $dup->languages = null;
        $dup->charsets = null;
        $dup->acceptableContentTypes = null;
        $dup->pathInfo = null;
        $dup->requestUri = null;
        $dup->baseUrl = null;
        $dup->basePath = null;
        $dup->method = null;
        $dup->format = null;
        return $dup;
    }
    /**
     * Clones the current request.
     *
     * Note that the session is not cloned as duplicated requests
     * are most of the time sub-requests of the main one.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->request = clone $this->request;
        $this->attributes = clone $this->attributes;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->server = clone $this->server;
        $this->headers = clone $this->headers;
    }
    /**
     * Returns the request as a string.
     *
     * @return string The request
     */
    public function __toString()
    {
        return sprintf('%s %s %s', $this->getMethod(), $this->getRequestUri(), $this->server->get('SERVER_PROTOCOL')) . '
' . $this->headers . '
' . $this->getContent();
    }
    /**
     * Overrides the PHP global variables according to this request instance.
     *
     * It overrides $_GET, $_POST, $_REQUEST, $_SERVER, $_COOKIE.
     * $_FILES is never override, see rfc1867
     *
     * @api
     */
    public function overrideGlobals()
    {
        $_GET = $this->query->all();
        $_POST = $this->request->all();
        $_SERVER = $this->server->all();
        $_COOKIE = $this->cookies->all();
        foreach ($this->headers->all() as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
                $_SERVER[$key] = implode(', ', $value);
            } else {
                $_SERVER['HTTP_' . $key] = implode(', ', $value);
            }
        }
        $request = array('g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE);
        $requestOrder = ini_get('request_order') ?: ini_get('variable_order');
        $requestOrder = preg_replace('#[^cgp]#', '', strtolower($requestOrder)) ?: 'gp';
        $_REQUEST = array();
        foreach (str_split($requestOrder) as $order) {
            $_REQUEST = array_merge($_REQUEST, $request[$order]);
        }
    }
    /**
     * Trusts $_SERVER entries coming from proxies.
     *
     * @deprecated Deprecated since version 2.0, to be removed in 2.3. Use setTrustedProxies instead.
     */
    public static function trustProxyData()
    {
        trigger_error('trustProxyData() is deprecated since version 2.0 and will be removed in 2.3. Use setTrustedProxies() instead.', E_USER_DEPRECATED);
        self::$trustProxy = true;
    }
    /**
     * Sets a list of trusted proxies.
     *
     * You should only list the reverse proxies that you manage directly.
     *
     * @param array $proxies A list of trusted proxies
     *
     * @api
     */
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
        self::$trustProxy = $proxies ? true : false;
    }
    /**
     * Gets the list of trusted proxies.
     *
     * @return array An array of trusted proxies.
     */
    public static function getTrustedProxies()
    {
        return self::$trustedProxies;
    }
    /**
     * Sets the name for trusted headers.
     *
     * The following header keys are supported:
     *
     *  * Request::HEADER_CLIENT_IP:    defaults to X-Forwarded-For   (see getClientIp())
     *  * Request::HEADER_CLIENT_HOST:  defaults to X-Forwarded-Host  (see getClientHost())
     *  * Request::HEADER_CLIENT_PORT:  defaults to X-Forwarded-Port  (see getClientPort())
     *  * Request::HEADER_CLIENT_PROTO: defaults to X-Forwarded-Proto (see getScheme() and isSecure())
     *
     * Setting an empty value allows to disable the trusted header for the given key.
     *
     * @param string $key   The header key
     * @param string $value The header name
     *
     * @throws \InvalidArgumentException
     */
    public static function setTrustedHeaderName($key, $value)
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to set the trusted header name for key "%s".', $key));
        }
        self::$trustedHeaders[$key] = $value;
    }
    /**
     * Returns true if $_SERVER entries coming from proxies are trusted,
     * false otherwise.
     *
     * @return boolean
     *
     * @deprecated Deprecated since version 2.2, to be removed in 2.3. Use getTrustedProxies instead.
     */
    public static function isProxyTrusted()
    {
        return self::$trustProxy;
    }
    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     *
     * @return string A normalized query string for the Request
     */
    public static function normalizeQueryString($qs)
    {
        if ('' == $qs) {
            return '';
        }
        $parts = array();
        $order = array();
        foreach (explode('&', $qs) as $param) {
            if ('' === $param || '=' === $param[0]) {
                // Ignore useless delimiters, e.g. "x=y&".
                // Also ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be retrieved anyway.
                // PHP also does not include them when building _GET.
                continue;
            }
            $keyValuePair = explode('=', $param, 2);
            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in enctype application/x-www-form-urlencoded).
            // PHP also converts "+" to spaces when filling the global _GET or when using the function parse_str. This is why we use urldecode and then normalize to
            // RFC 3986 with rawurlencode.
            $parts[] = isset($keyValuePair[1]) ? rawurlencode(urldecode($keyValuePair[0])) . '=' . rawurlencode(urldecode($keyValuePair[1])) : rawurlencode(urldecode($keyValuePair[0]));
            $order[] = urldecode($keyValuePair[0]);
        }
        array_multisort($order, SORT_ASC, $parts);
        return implode('&', $parts);
    }
    /**
     * Enables support for the _method request parameter to determine the intended HTTP method.
     *
     * Be warned that enabling this feature might lead to CSRF issues in your code.
     * Check that you are using CSRF tokens when required.
     *
     * The HTTP method can only be overridden when the real HTTP method is POST.
     */
    public static function enableHttpMethodParameterOverride()
    {
        self::$httpMethodParameterOverride = true;
    }
    /**
     * Checks whether support for the _method request parameter is enabled.
     *
     * @return Boolean True when the _method request parameter is enabled, false otherwise
     */
    public static function getHttpMethodParameterOverride()
    {
        return self::$httpMethodParameterOverride;
    }
    /**
     * Gets a "parameter" value.
     *
     * This method is mainly useful for libraries that want to provide some flexibility.
     *
     * Order of precedence: GET, PATH, POST
     *
     * Avoid using this method in controllers:
     *
     *  * slow
     *  * prefer to get from a "named" source
     *
     * It is better to explicitly get request parameters from the appropriate
     * public property instead (query, attributes, request).
     *
     * @param string  $key     the key
     * @param mixed   $default the default value
     * @param Boolean $deep    is parameter deep in multidimensional array
     *
     * @return mixed
     */
    public function get($key, $default = null, $deep = false)
    {
        return $this->query->get($key, $this->attributes->get($key, $this->request->get($key, $default, $deep), $deep), $deep);
    }
    /**
     * Gets the Session.
     *
     * @return SessionInterface|null The session
     *
     * @api
     */
    public function getSession()
    {
        return $this->session;
    }
    /**
     * Whether the request contains a Session which was started in one of the
     * previous requests.
     *
     * @return Boolean
     *
     * @api
     */
    public function hasPreviousSession()
    {
        // the check for $this->session avoids malicious users trying to fake a session cookie with proper name
        return $this->hasSession() && $this->cookies->has($this->session->getName());
    }
    /**
     * Whether the request contains a Session object.
     *
     * This method does not give any information about the state of the session object,
     * like whether the session is started or not. It is just a way to check if this Request
     * is associated with a Session instance.
     *
     * @return Boolean true when the Request contains a Session object, false otherwise
     *
     * @api
     */
    public function hasSession()
    {
        return null !== $this->session;
    }
    /**
     * Sets the Session.
     *
     * @param SessionInterface $session The Session
     *
     * @api
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }
    /**
     * Returns the client IP address.
     *
     * This method can read the client IP address from the "X-Forwarded-For" header
     * when trusted proxies were set via "setTrustedProxies()". The "X-Forwarded-For"
     * header value is a comma+space separated list of IP addresses, the left-most
     * being the original client, and each successive proxy that passed the request
     * adding the IP address where it received the request from.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-For",
     * ("Client-Ip" for instance), configure it via "setTrustedHeaderName()" with
     * the "client-ip" key.
     *
     * @return string The client IP address
     *
     * @see http://en.wikipedia.org/wiki/X-Forwarded-For
     *
     * @api
     */
    public function getClientIp()
    {
        $ip = $this->server->get('REMOTE_ADDR');
        if (!self::$trustProxy) {
            return $ip;
        }
        if (!self::$trustedHeaders[self::HEADER_CLIENT_IP] || !$this->headers->has(self::$trustedHeaders[self::HEADER_CLIENT_IP])) {
            return $ip;
        }
        $clientIps = array_map('trim', explode(',', $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_IP])));
        $clientIps[] = $ip;
        $trustedProxies = self::$trustProxy && !self::$trustedProxies ? array($ip) : self::$trustedProxies;
        $clientIps = array_diff($clientIps, $trustedProxies);
        return array_pop($clientIps);
    }
    /**
     * Returns current script name.
     *
     * @return string
     *
     * @api
     */
    public function getScriptName()
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }
    /**
     * Returns the path being requested relative to the executed script.
     *
     * The path info always starts with a /.
     *
     * Suppose this request is instantiated from /mysite on localhost:
     *
     *  * http://localhost/mysite              returns an empty string
     *  * http://localhost/mysite/about        returns '/about'
     *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded'
     *  * http://localhost/mysite/about?var=1  returns '/about'
     *
     * @return string The raw path (i.e. not urldecoded)
     *
     * @api
     */
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }
        return $this->pathInfo;
    }
    /**
     * Returns the root path from which this request is executed.
     *
     * Suppose that an index.php file instantiates this request object:
     *
     *  * http://localhost/index.php         returns an empty string
     *  * http://localhost/index.php/page    returns an empty string
     *  * http://localhost/web/index.php     returns '/web'
     *  * http://localhost/we%20b/index.php  returns '/we%20b'
     *
     * @return string The raw path (i.e. not urldecoded)
     *
     * @api
     */
    public function getBasePath()
    {
        if (null === $this->basePath) {
            $this->basePath = $this->prepareBasePath();
        }
        return $this->basePath;
    }
    /**
     * Returns the root url from which this request is executed.
     *
     * The base URL never ends with a /.
     *
     * This is similar to getBasePath(), except that it also includes the
     * script filename (e.g. index.php) if one exists.
     *
     * @return string The raw url (i.e. not urldecoded)
     *
     * @api
     */
    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }
        return $this->baseUrl;
    }
    /**
     * Gets the request's scheme.
     *
     * @return string
     *
     * @api
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }
    /**
     * Returns the port on which the request is made.
     *
     * This method can read the client port from the "X-Forwarded-Port" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Port" header must contain the client port.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Port",
     * configure it via "setTrustedHeaderName()" with the "client-port" key.
     *
     * @return string
     *
     * @api
     */
    public function getPort()
    {
        if (self::$trustProxy && self::$trustedHeaders[self::HEADER_CLIENT_PORT] && ($port = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PORT]))) {
            return $port;
        }
        return $this->server->get('SERVER_PORT');
    }
    /**
     * Returns the user.
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->server->get('PHP_AUTH_USER');
    }
    /**
     * Returns the password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->server->get('PHP_AUTH_PW');
    }
    /**
     * Gets the user info.
     *
     * @return string A user name and, optionally, scheme-specific information about how to gain authorization to access the server
     */
    public function getUserInfo()
    {
        $userinfo = $this->getUser();
        $pass = $this->getPassword();
        if ('' != $pass) {
            $userinfo .= ":{$pass}";
        }
        return $userinfo;
    }
    /**
     * Returns the HTTP host being requested.
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * @return string
     *
     * @api
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();
        if ('http' == $scheme && $port == 80 || 'https' == $scheme && $port == 443) {
            return $this->getHost();
        }
        return $this->getHost() . ':' . $port;
    }
    /**
     * Returns the requested URI.
     *
     * @return string The raw URI (i.e. not urldecoded)
     *
     * @api
     */
    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            $this->requestUri = $this->prepareRequestUri();
        }
        return $this->requestUri;
    }
    /**
     * Gets the scheme and HTTP host.
     *
     * If the URL was called with basic authentication, the user
     * and the password are not added to the generated string.
     *
     * @return string The scheme and HTTP host
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }
    /**
     * Generates a normalized URI for the Request.
     *
     * @return string A normalized URI for the Request
     *
     * @see getQueryString()
     *
     * @api
     */
    public function getUri()
    {
        if (null !== ($qs = $this->getQueryString())) {
            $qs = '?' . $qs;
        }
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $qs;
    }
    /**
     * Generates a normalized URI for the given path.
     *
     * @param string $path A path to use instead of the current one
     *
     * @return string The normalized URI for the path
     *
     * @api
     */
    public function getUriForPath($path)
    {
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $path;
    }
    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * @return string|null A normalized query string for the Request
     *
     * @api
     */
    public function getQueryString()
    {
        $qs = static::normalizeQueryString($this->server->get('QUERY_STRING'));
        return '' === $qs ? null : $qs;
    }
    /**
     * Checks whether the request is secure or not.
     *
     * This method can read the client port from the "X-Forwarded-Proto" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Proto"
     * ("SSL_HTTPS" for instance), configure it via "setTrustedHeaderName()" with
     * the "client-proto" key.
     *
     * @return Boolean
     *
     * @api
     */
    public function isSecure()
    {
        if (self::$trustProxy && self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && ($proto = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PROTO]))) {
            return in_array(strtolower($proto), array('https', 'on', '1'));
        }
        return 'on' == strtolower($this->server->get('HTTPS')) || 1 == $this->server->get('HTTPS');
    }
    /**
     * Returns the host name.
     *
     * This method can read the client port from the "X-Forwarded-Host" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Host" header must contain the client host name.
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Host",
     * configure it via "setTrustedHeaderName()" with the "client-host" key.
     *
     * @return string
     *
     * @throws \UnexpectedValueException when the host name is invalid
     *
     * @api
     */
    public function getHost()
    {
        if (self::$trustProxy && self::$trustedHeaders[self::HEADER_CLIENT_HOST] && ($host = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_HOST]))) {
            $elements = explode(',', $host);
            $host = $elements[count($elements) - 1];
        } elseif (!($host = $this->headers->get('HOST'))) {
            if (!($host = $this->server->get('SERVER_NAME'))) {
                $host = $this->server->get('SERVER_ADDR', '');
            }
        }
        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        $host = strtolower(preg_replace('/:\\d+$/', '', trim($host)));
        // as the host can come from the user (HTTP_HOST and depending on the configuration, SERVER_NAME too can come from the user)
        // check that it does not contain forbidden characters (see RFC 952 and RFC 2181)
        if ($host && !preg_match('/^\\[?(?:[a-zA-Z0-9-:\\]_]+\\.?)+$/', $host)) {
            throw new \UnexpectedValueException('Invalid Host');
        }
        return $host;
    }
    /**
     * Sets the request method.
     *
     * @param string $method
     *
     * @api
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);
    }
    /**
     * Gets the request "intended" method.
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     *
     * The _method request parameter can also be used to determine the HTTP method,
     * but only if enableHttpMethodParameterOverride() has been called.
     *
     * The method is always an uppercased string.
     *
     * @return string The request method
     *
     * @api
     *
     * @see getRealMethod
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
            if ('POST' === $this->method) {
                if ($method = $this->headers->get('X-HTTP-METHOD-OVERRIDE')) {
                    $this->method = strtoupper($method);
                } elseif (self::$httpMethodParameterOverride) {
                    $this->method = strtoupper($this->request->get('_method', $this->query->get('_method', 'POST')));
                }
            }
        }
        return $this->method;
    }
    /**
     * Gets the "real" request method.
     *
     * @return string The request method
     *
     * @see getMethod
     */
    public function getRealMethod()
    {
        return strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
    }
    /**
     * Gets the mime type associated with the format.
     *
     * @param string $format The format
     *
     * @return string The associated mime type (null if not found)
     *
     * @api
     */
    public function getMimeType($format)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }
        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }
    /**
     * Gets the format associated with the mime type.
     *
     * @param string $mimeType The associated mime type
     *
     * @return string|null The format (null if not found)
     *
     * @api
     */
    public function getFormat($mimeType)
    {
        if (false !== ($pos = strpos($mimeType, ';'))) {
            $mimeType = substr($mimeType, 0, $pos);
        }
        if (null === static::$formats) {
            static::initializeFormats();
        }
        foreach (static::$formats as $format => $mimeTypes) {
            if (in_array($mimeType, (array) $mimeTypes)) {
                return $format;
            }
        }
        return null;
    }
    /**
     * Associates a format with mime types.
     *
     * @param string       $format    The format
     * @param string|array $mimeTypes The associated mime types (the preferred one must be the first as it will be used as the content type)
     *
     * @api
     */
    public function setFormat($format, $mimeTypes)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }
        static::$formats[$format] = is_array($mimeTypes) ? $mimeTypes : array($mimeTypes);
    }
    /**
     * Gets the request format.
     *
     * Here is the process to determine the format:
     *
     *  * format defined by the user (with setRequestFormat())
     *  * _format request parameter
     *  * $default
     *
     * @param string $default The default format
     *
     * @return string The request format
     *
     * @api
     */
    public function getRequestFormat($default = 'html')
    {
        if (null === $this->format) {
            $this->format = $this->get('_format', $default);
        }
        return $this->format;
    }
    /**
     * Sets the request format.
     *
     * @param string $format The request format.
     *
     * @api
     */
    public function setRequestFormat($format)
    {
        $this->format = $format;
    }
    /**
     * Gets the format associated with the request.
     *
     * @return string|null The format (null if no content type is present)
     *
     * @api
     */
    public function getContentType()
    {
        return $this->getFormat($this->headers->get('CONTENT_TYPE'));
    }
    /**
     * Sets the default locale.
     *
     * @param string $locale
     *
     * @api
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
        if (null === $this->locale) {
            $this->setPhpDefaultLocale($locale);
        }
    }
    /**
     * Sets the locale.
     *
     * @param string $locale
     *
     * @api
     */
    public function setLocale($locale)
    {
        $this->setPhpDefaultLocale($this->locale = $locale);
    }
    /**
     * Get the locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return null === $this->locale ? $this->defaultLocale : $this->locale;
    }
    /**
     * Checks if the request method is of specified type.
     *
     * @param string $method Uppercase request method (GET, POST etc).
     *
     * @return Boolean
     */
    public function isMethod($method)
    {
        return $this->getMethod() === strtoupper($method);
    }
    /**
     * Checks whether the method is safe or not.
     *
     * @return Boolean
     *
     * @api
     */
    public function isMethodSafe()
    {
        return in_array($this->getMethod(), array('GET', 'HEAD'));
    }
    /**
     * Returns the request body content.
     *
     * @param Boolean $asResource If true, a resource will be returned
     *
     * @return string|resource The request body content or a resource to read the body stream.
     *
     * @throws \LogicException
     */
    public function getContent($asResource = false)
    {
        if (false === $this->content || true === $asResource && null !== $this->content) {
            throw new \LogicException('getContent() can only be called once when using the resource return type.');
        }
        if (true === $asResource) {
            $this->content = false;
            return fopen('php://input', 'rb');
        }
        if (null === $this->content) {
            $this->content = file_get_contents('php://input');
        }
        return $this->content;
    }
    /**
     * Gets the Etags.
     *
     * @return array The entity tags
     */
    public function getETags()
    {
        return preg_split('/\\s*,\\s*/', $this->headers->get('if_none_match'), null, PREG_SPLIT_NO_EMPTY);
    }
    /**
     * @return Boolean
     */
    public function isNoCache()
    {
        return $this->headers->hasCacheControlDirective('no-cache') || 'no-cache' == $this->headers->get('Pragma');
    }
    /**
     * Returns the preferred language.
     *
     * @param array $locales An array of ordered available locales
     *
     * @return string|null The preferred locale
     *
     * @api
     */
    public function getPreferredLanguage(array $locales = null)
    {
        $preferredLanguages = $this->getLanguages();
        if (empty($locales)) {
            return isset($preferredLanguages[0]) ? $preferredLanguages[0] : null;
        }
        if (!$preferredLanguages) {
            return $locales[0];
        }
        $preferredLanguages = array_values(array_intersect($preferredLanguages, $locales));
        return isset($preferredLanguages[0]) ? $preferredLanguages[0] : $locales[0];
    }
    /**
     * Gets a list of languages acceptable by the client browser.
     *
     * @return array Languages ordered in the user browser preferences
     *
     * @api
     */
    public function getLanguages()
    {
        if (null !== $this->languages) {
            return $this->languages;
        }
        $languages = AcceptHeader::fromString($this->headers->get('Accept-Language'))->all();
        $this->languages = array();
        foreach (array_keys($languages) as $lang) {
            if (strstr($lang, '-')) {
                $codes = explode('-', $lang);
                if ($codes[0] == 'i') {
                    // Language not listed in ISO 639 that are not variants
                    // of any listed language, which can be registered with the
                    // i-prefix, such as i-cherokee
                    if (count($codes) > 1) {
                        $lang = $codes[1];
                    }
                } else {
                    for ($i = 0, $max = count($codes); $i < $max; $i++) {
                        if ($i == 0) {
                            $lang = strtolower($codes[0]);
                        } else {
                            $lang .= '_' . strtoupper($codes[$i]);
                        }
                    }
                }
            }
            $this->languages[] = $lang;
        }
        return $this->languages;
    }
    /**
     * Gets a list of charsets acceptable by the client browser.
     *
     * @return array List of charsets in preferable order
     *
     * @api
     */
    public function getCharsets()
    {
        if (null !== $this->charsets) {
            return $this->charsets;
        }
        return $this->charsets = array_keys(AcceptHeader::fromString($this->headers->get('Accept-Charset'))->all());
    }
    /**
     * Gets a list of content types acceptable by the client browser
     *
     * @return array List of content types in preferable order
     *
     * @api
     */
    public function getAcceptableContentTypes()
    {
        if (null !== $this->acceptableContentTypes) {
            return $this->acceptableContentTypes;
        }
        return $this->acceptableContentTypes = array_keys(AcceptHeader::fromString($this->headers->get('Accept'))->all());
    }
    /**
     * Returns true if the request is a XMLHttpRequest.
     *
     * It works if your JavaScript library set an X-Requested-With HTTP header.
     * It is known to work with common JavaScript frameworks:
     * @link http://en.wikipedia.org/wiki/List_of_Ajax_frameworks#JavaScript
     *
     * @return Boolean true if the request is an XMLHttpRequest, false otherwise
     *
     * @api
     */
    public function isXmlHttpRequest()
    {
        return 'XMLHttpRequest' == $this->headers->get('X-Requested-With');
    }
    /**
     * Splits an Accept-* HTTP header.
     *
     * @param string $header Header to split
     *
     * @return array Array indexed by the values of the Accept-* header in preferred order
     *
     * @deprecated Deprecated since version 2.2, to be removed in 2.3.
     */
    public function splitHttpAcceptHeader($header)
    {
        trigger_error('splitHttpAcceptHeader() is deprecated since version 2.2 and will be removed in 2.3.', E_USER_DEPRECATED);
        $headers = array();
        foreach (AcceptHeader::fromString($header)->all() as $item) {
            $key = $item->getValue();
            foreach ($item->getAttributes() as $name => $value) {
                $key .= sprintf(';%s=%s', $name, $value);
            }
            $headers[$key] = $item->getQuality();
        }
        return $headers;
    }
    /*
     * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
     *
     * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     *
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     */
    protected function prepareRequestUri()
    {
        $requestUri = '';
        if ($this->headers->has('X_ORIGINAL_URL') && false !== stripos(PHP_OS, 'WIN')) {
            // IIS with Microsoft Rewrite Module
            $requestUri = $this->headers->get('X_ORIGINAL_URL');
            $this->headers->remove('X_ORIGINAL_URL');
        } elseif ($this->headers->has('X_REWRITE_URL') && false !== stripos(PHP_OS, 'WIN')) {
            // IIS with ISAPI_Rewrite
            $requestUri = $this->headers->get('X_REWRITE_URL');
            $this->headers->remove('X_REWRITE_URL');
        } elseif ($this->server->get('IIS_WasUrlRewritten') == '1' && $this->server->get('UNENCODED_URL') != '') {
            // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
            $requestUri = $this->server->get('UNENCODED_URL');
            $this->server->remove('UNENCODED_URL');
            $this->server->remove('IIS_WasUrlRewritten');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');
            // HTTP proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
            $schemeAndHttpHost = $this->getSchemeAndHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        } elseif ($this->server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = $this->server->get('ORIG_PATH_INFO');
            if ('' != $this->server->get('QUERY_STRING')) {
                $requestUri .= '?' . $this->server->get('QUERY_STRING');
            }
            $this->server->remove('ORIG_PATH_INFO');
        }
        // normalize the request URI to ease creating sub-requests from this request
        $this->server->set('REQUEST_URI', $requestUri);
        return $requestUri;
    }
    /**
     * Prepares the base URL.
     *
     * @return string
     */
    protected function prepareBaseUrl()
    {
        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        if (basename($this->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        } elseif (basename($this->server->get('PHP_SELF')) === $filename) {
            $baseUrl = $this->server->get('PHP_SELF');
        } elseif (basename($this->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('ORIG_SCRIPT_NAME');
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $this->server->get('PHP_SELF', '');
            $file = $this->server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while ($last > $index && false !== ($pos = strpos($path, $baseUrl)) && 0 != $pos);
        }
        // Does the baseUrl have anything in common with the request_uri?
        $requestUri = $this->getRequestUri();
        if ($baseUrl && false !== ($prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl))) {
            // full $baseUrl matches
            return $prefix;
        }
        if ($baseUrl && false !== ($prefix = $this->getUrlencodedPrefix($requestUri, dirname($baseUrl)))) {
            // directory portion of $baseUrl matches
            return rtrim($prefix, '/');
        }
        $truncatedRequestUri = $requestUri;
        if (($pos = strpos($requestUri, '?')) !== false) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }
        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            return '';
        }
        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if (strlen($requestUri) >= strlen($baseUrl) && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }
        return rtrim($baseUrl, '/');
    }
    /**
     * Prepares the base path.
     *
     * @return string base path
     */
    protected function prepareBasePath()
    {
        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return '';
        }
        if (basename($baseUrl) === $filename) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }
        if ('\\' === DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }
        return rtrim($basePath, '/');
    }
    /**
     * Prepares the path info.
     *
     * @return string path info
     */
    protected function preparePathInfo()
    {
        $baseUrl = $this->getBaseUrl();
        if (null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }
        $pathInfo = '/';
        // Remove the query string from REQUEST_URI
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        if (null !== $baseUrl && false === ($pathInfo = substr($requestUri, strlen($baseUrl)))) {
            // If substr() returns false then PATH_INFO is set to an empty string
            return '/';
        } elseif (null === $baseUrl) {
            return $requestUri;
        }
        return (string) $pathInfo;
    }
    /**
     * Initializes HTTP request formats.
     */
    protected static function initializeFormats()
    {
        static::$formats = array('html' => array('text/html', 'application/xhtml+xml'), 'txt' => array('text/plain'), 'js' => array('application/javascript', 'application/x-javascript', 'text/javascript'), 'css' => array('text/css'), 'json' => array('application/json', 'application/x-json'), 'xml' => array('text/xml', 'application/xml', 'application/x-xml'), 'rdf' => array('application/rdf+xml'), 'atom' => array('application/atom+xml'), 'rss' => array('application/rss+xml'));
    }
    /**
     * Sets the default PHP locale.
     *
     * @param string $locale
     */
    private function setPhpDefaultLocale($locale)
    {
        // if either the class Locale doesn't exist, or an exception is thrown when
        // setting the default locale, the intl module is not installed, and
        // the call can be ignored:
        try {
            if (class_exists('Locale', false)) {
                \Locale::setDefault($locale);
            }
        } catch (\Exception $e) {
            
        }
    }
    /*
     * Returns the prefix as encoded in the string when the string starts with
     * the given prefix, false otherwise.
     *
     * @param string $string The urlencoded string
     * @param string $prefix The prefix not encoded
     *
     * @return string|false The prefix as it is encoded in $string, or false
     */
    private function getUrlencodedPrefix($string, $prefix)
    {
        if (0 !== strpos(rawurldecode($string), $prefix)) {
            return false;
        }
        $len = strlen($prefix);
        if (preg_match("#^(%[[:xdigit:]]{2}|.){{$len}}#", $string, $match)) {
            return $match[0];
        }
        return false;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

/**
 * ParameterBag is a container for key/value pairs.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class ParameterBag implements \IteratorAggregate, \Countable
{
    /**
     * Parameter storage.
     *
     * @var array
     */
    protected $parameters;
    /**
     * Constructor.
     *
     * @param array $parameters An array of parameters
     *
     * @api
     */
    public function __construct(array $parameters = array())
    {
        $this->parameters = $parameters;
    }
    /**
     * Returns the parameters.
     *
     * @return array An array of parameters
     *
     * @api
     */
    public function all()
    {
        return $this->parameters;
    }
    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     *
     * @api
     */
    public function keys()
    {
        return array_keys($this->parameters);
    }
    /**
     * Replaces the current parameters by a new set.
     *
     * @param array $parameters An array of parameters
     *
     * @api
     */
    public function replace(array $parameters = array())
    {
        $this->parameters = $parameters;
    }
    /**
     * Adds parameters.
     *
     * @param array $parameters An array of parameters
     *
     * @api
     */
    public function add(array $parameters = array())
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }
    /**
     * Returns a parameter by name.
     *
     * @param string  $path    The key
     * @param mixed   $default The default value if the parameter key does not exist
     * @param boolean $deep    If true, a path like foo[bar] will find deeper items
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function get($path, $default = null, $deep = false)
    {
        if (!$deep || false === ($pos = strpos($path, '['))) {
            return array_key_exists($path, $this->parameters) ? $this->parameters[$path] : $default;
        }
        $root = substr($path, 0, $pos);
        if (!array_key_exists($root, $this->parameters)) {
            return $default;
        }
        $value = $this->parameters[$root];
        $currentKey = null;
        for ($i = $pos, $c = strlen($path); $i < $c; $i++) {
            $char = $path[$i];
            if ('[' === $char) {
                if (null !== $currentKey) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "[" at position %d.', $i));
                }
                $currentKey = '';
            } elseif (']' === $char) {
                if (null === $currentKey) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "]" at position %d.', $i));
                }
                if (!is_array($value) || !array_key_exists($currentKey, $value)) {
                    return $default;
                }
                $value = $value[$currentKey];
                $currentKey = null;
            } else {
                if (null === $currentKey) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "%s" at position %d.', $char, $i));
                }
                $currentKey .= $char;
            }
        }
        if (null !== $currentKey) {
            throw new \InvalidArgumentException(sprintf('Malformed path. Path must end with "]".'));
        }
        return $value;
    }
    /**
     * Sets a parameter by name.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     *
     * @api
     */
    public function set($key, $value)
    {
        $this->parameters[$key] = $value;
    }
    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     *
     * @return Boolean true if the parameter exists, false otherwise
     *
     * @api
     */
    public function has($key)
    {
        return array_key_exists($key, $this->parameters);
    }
    /**
     * Removes a parameter.
     *
     * @param string $key The key
     *
     * @api
     */
    public function remove($key)
    {
        unset($this->parameters[$key]);
    }
    /**
     * Returns the alphabetic characters of the parameter value.
     *
     * @param string  $key     The parameter key
     * @param mixed   $default The default value if the parameter key does not exist
     * @param boolean $deep    If true, a path like foo[bar] will find deeper items
     *
     * @return string The filtered value
     *
     * @api
     */
    public function getAlpha($key, $default = '', $deep = false)
    {
        return preg_replace('/[^[:alpha:]]/', '', $this->get($key, $default, $deep));
    }
    /**
     * Returns the alphabetic characters and digits of the parameter value.
     *
     * @param string  $key     The parameter key
     * @param mixed   $default The default value if the parameter key does not exist
     * @param boolean $deep    If true, a path like foo[bar] will find deeper items
     *
     * @return string The filtered value
     *
     * @api
     */
    public function getAlnum($key, $default = '', $deep = false)
    {
        return preg_replace('/[^[:alnum:]]/', '', $this->get($key, $default, $deep));
    }
    /**
     * Returns the digits of the parameter value.
     *
     * @param string  $key     The parameter key
     * @param mixed   $default The default value if the parameter key does not exist
     * @param boolean $deep    If true, a path like foo[bar] will find deeper items
     *
     * @return string The filtered value
     *
     * @api
     */
    public function getDigits($key, $default = '', $deep = false)
    {
        // we need to remove - and + because they're allowed in the filter
        return str_replace(array('-', '+'), '', $this->filter($key, $default, $deep, FILTER_SANITIZE_NUMBER_INT));
    }
    /**
     * Returns the parameter value converted to integer.
     *
     * @param string  $key     The parameter key
     * @param mixed   $default The default value if the parameter key does not exist
     * @param boolean $deep    If true, a path like foo[bar] will find deeper items
     *
     * @return integer The filtered value
     *
     * @api
     */
    public function getInt($key, $default = 0, $deep = false)
    {
        return (int) $this->get($key, $default, $deep);
    }
    /**
     * Filter key.
     *
     * @param string  $key     Key.
     * @param mixed   $default Default = null.
     * @param boolean $deep    Default = false.
     * @param integer $filter  FILTER_* constant.
     * @param mixed   $options Filter options.
     *
     * @see http://php.net/manual/en/function.filter-var.php
     *
     * @return mixed
     */
    public function filter($key, $default = null, $deep = false, $filter = FILTER_DEFAULT, $options = array())
    {
        $value = $this->get($key, $default, $deep);
        // Always turn $options into an array - this allows filter_var option shortcuts.
        if (!is_array($options) && $options) {
            $options = array('flags' => $options);
        }
        // Add a convenience check for arrays.
        if (is_array($value) && !isset($options['flags'])) {
            $options['flags'] = FILTER_REQUIRE_ARRAY;
        }
        return filter_var($value, $filter, $options);
    }
    /**
     * Returns an iterator for parameters.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->parameters);
    }
    /**
     * Returns the number of parameters.
     *
     * @return int The number of parameters
     */
    public function count()
    {
        return count($this->parameters);
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\File\UploadedFile;
/**
 * FileBag is a container for HTTP headers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * @api
 */
class FileBag extends ParameterBag
{
    private static $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');
    /**
     * Constructor.
     *
     * @param array $parameters An array of HTTP files
     *
     * @api
     */
    public function __construct(array $parameters = array())
    {
        $this->replace($parameters);
    }
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function replace(array $files = array())
    {
        $this->parameters = array();
        $this->add($files);
    }
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function set($key, $value)
    {
        if (!is_array($value) && !$value instanceof UploadedFile) {
            throw new \InvalidArgumentException('An uploaded file must be an array or an instance of UploadedFile.');
        }
        parent::set($key, $this->convertFileInformation($value));
    }
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function add(array $files = array())
    {
        foreach ($files as $key => $file) {
            $this->set($key, $file);
        }
    }
    /**
     * Converts uploaded files to UploadedFile instances.
     *
     * @param array|UploadedFile $file A (multi-dimensional) array of uploaded file information
     *
     * @return array A (multi-dimensional) array of UploadedFile instances
     */
    protected function convertFileInformation($file)
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }
        $file = $this->fixPhpFilesArray($file);
        if (is_array($file)) {
            $keys = array_keys($file);
            sort($keys);
            if ($keys == self::$fileKeys) {
                if (UPLOAD_ERR_NO_FILE == $file['error']) {
                    $file = null;
                } else {
                    $file = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
                }
            } else {
                $file = array_map(array($this, 'convertFileInformation'), $file);
            }
        }
        return $file;
    }
    /**
     * Fixes a malformed PHP $_FILES array.
     *
     * PHP has a bug that the format of the $_FILES array differs, depending on
     * whether the uploaded file fields had normal field names or array-like
     * field names ("normal" vs. "parent[child]").
     *
     * This method fixes the array to look like the "normal" $_FILES array.
     *
     * It's safe to pass an already converted array, in which case this method
     * just returns the original array unmodified.
     *
     * @param array $data
     *
     * @return array
     */
    protected function fixPhpFilesArray($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        $keys = array_keys($data);
        sort($keys);
        if (self::$fileKeys != $keys || !isset($data['name']) || !is_array($data['name'])) {
            return $data;
        }
        $files = $data;
        foreach (self::$fileKeys as $k) {
            unset($files[$k]);
        }
        foreach (array_keys($data['name']) as $key) {
            $files[$key] = $this->fixPhpFilesArray(array('error' => $data['error'][$key], 'name' => $data['name'][$key], 'type' => $data['type'][$key], 'tmp_name' => $data['tmp_name'][$key], 'size' => $data['size'][$key]));
        }
        return $files;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

/**
 * ServerBag is a container for HTTP headers from the $_SERVER variable.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Robert Kiss <kepten@gmail.com>
 */
class ServerBag extends ParameterBag
{
    /**
     * Gets the HTTP headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = array();
        foreach ($this->parameters as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) {
                $headers[$key] = $value;
            }
        }
        if (isset($this->parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($this->parameters['PHP_AUTH_PW']) ? $this->parameters['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */
            $authorizationHeader = null;
            if (isset($this->parameters['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }
            // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
            if (null !== $authorizationHeader && 0 === stripos($authorizationHeader, 'basic')) {
                $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)));
                if (count($exploded) == 2) {
                    list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                }
            }
        }
        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        }
        return $headers;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

/**
 * HeaderBag is a container for HTTP headers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class HeaderBag implements \IteratorAggregate, \Countable
{
    protected $headers;
    protected $cacheControl;
    /**
     * Constructor.
     *
     * @param array $headers An array of HTTP headers
     *
     * @api
     */
    public function __construct(array $headers = array())
    {
        $this->cacheControl = array();
        $this->headers = array();
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }
    /**
     * Returns the headers as a string.
     *
     * @return string The headers
     */
    public function __toString()
    {
        if (!$this->headers) {
            return '';
        }
        $max = max(array_map('strlen', array_keys($this->headers))) + 1;
        $content = '';
        ksort($this->headers);
        foreach ($this->headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }
        return $content;
    }
    /**
     * Returns the headers.
     *
     * @return array An array of headers
     *
     * @api
     */
    public function all()
    {
        return $this->headers;
    }
    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     *
     * @api
     */
    public function keys()
    {
        return array_keys($this->headers);
    }
    /**
     * Replaces the current HTTP headers by a new set.
     *
     * @param array $headers An array of HTTP headers
     *
     * @api
     */
    public function replace(array $headers = array())
    {
        $this->headers = array();
        $this->add($headers);
    }
    /**
     * Adds new headers the current HTTP headers set.
     *
     * @param array $headers An array of HTTP headers
     *
     * @api
     */
    public function add(array $headers)
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }
    /**
     * Returns a header value by name.
     *
     * @param string  $key     The header name
     * @param mixed   $default The default value
     * @param Boolean $first   Whether to return the first value or all header values
     *
     * @return string|array The first header value if $first is true, an array of values otherwise
     *
     * @api
     */
    public function get($key, $default = null, $first = true)
    {
        $key = strtr(strtolower($key), '_', '-');
        if (!array_key_exists($key, $this->headers)) {
            if (null === $default) {
                return $first ? null : array();
            }
            return $first ? $default : array($default);
        }
        if ($first) {
            return count($this->headers[$key]) ? $this->headers[$key][0] : $default;
        }
        return $this->headers[$key];
    }
    /**
     * Sets a header by name.
     *
     * @param string       $key     The key
     * @param string|array $values  The value or an array of values
     * @param Boolean      $replace Whether to replace the actual value of not (true by default)
     *
     * @api
     */
    public function set($key, $values, $replace = true)
    {
        $key = strtr(strtolower($key), '_', '-');
        $values = array_values((array) $values);
        if (true === $replace || !isset($this->headers[$key])) {
            $this->headers[$key] = $values;
        } else {
            $this->headers[$key] = array_merge($this->headers[$key], $values);
        }
        if ('cache-control' === $key) {
            $this->cacheControl = $this->parseCacheControl($values[0]);
        }
    }
    /**
     * Returns true if the HTTP header is defined.
     *
     * @param string $key The HTTP header
     *
     * @return Boolean true if the parameter exists, false otherwise
     *
     * @api
     */
    public function has($key)
    {
        return array_key_exists(strtr(strtolower($key), '_', '-'), $this->headers);
    }
    /**
     * Returns true if the given HTTP header contains the given value.
     *
     * @param string $key   The HTTP header name
     * @param string $value The HTTP value
     *
     * @return Boolean true if the value is contained in the header, false otherwise
     *
     * @api
     */
    public function contains($key, $value)
    {
        return in_array($value, $this->get($key, null, false));
    }
    /**
     * Removes a header.
     *
     * @param string $key The HTTP header name
     *
     * @api
     */
    public function remove($key)
    {
        $key = strtr(strtolower($key), '_', '-');
        unset($this->headers[$key]);
        if ('cache-control' === $key) {
            $this->cacheControl = array();
        }
    }
    /**
     * Returns the HTTP header value converted to a date.
     *
     * @param string    $key     The parameter key
     * @param \DateTime $default The default value
     *
     * @return null|\DateTime The parsed DateTime or the default value if the header does not exist
     *
     * @throws \RuntimeException When the HTTP header is not parseable
     *
     * @api
     */
    public function getDate($key, \DateTime $default = null)
    {
        if (null === ($value = $this->get($key))) {
            return $default;
        }
        if (false === ($date = \DateTime::createFromFormat(DATE_RFC2822, $value))) {
            throw new \RuntimeException(sprintf('The %s HTTP header is not parseable (%s).', $key, $value));
        }
        return $date;
    }
    public function addCacheControlDirective($key, $value = true)
    {
        $this->cacheControl[$key] = $value;
        $this->set('Cache-Control', $this->getCacheControlHeader());
    }
    public function hasCacheControlDirective($key)
    {
        return array_key_exists($key, $this->cacheControl);
    }
    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null;
    }
    public function removeCacheControlDirective($key)
    {
        unset($this->cacheControl[$key]);
        $this->set('Cache-Control', $this->getCacheControlHeader());
    }
    /**
     * Returns an iterator for headers.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }
    /**
     * Returns the number of headers.
     *
     * @return int The number of headers
     */
    public function count()
    {
        return count($this->headers);
    }
    protected function getCacheControlHeader()
    {
        $parts = array();
        ksort($this->cacheControl);
        foreach ($this->cacheControl as $key => $value) {
            if (true === $value) {
                $parts[] = $key;
            } else {
                if (preg_match('#[^a-zA-Z0-9._-]#', $value)) {
                    $value = '"' . $value . '"';
                }
                $parts[] = "{$key}={$value}";
            }
        }
        return implode(', ', $parts);
    }
    /**
     * Parses a Cache-Control HTTP header.
     *
     * @param string $header The value of the Cache-Control HTTP header
     *
     * @return array An array representing the attribute values
     */
    protected function parseCacheControl($header)
    {
        $cacheControl = array();
        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\\s*(?:=(?:"([^"]*)"|([^ \\t",;]*)))?#', $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $cacheControl[strtolower($match[1])] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : true);
        }
        return $cacheControl;
    }
}
namespace Illuminate\Support;

use ReflectionClass;
abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    /**
     * Create a new service provider instance.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public abstract function register();
    /**
     * Register the package's component namespaces.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @param  string  $path
     * @return void
     */
    public function package($package, $namespace = null, $path = null)
    {
        $namespace = $this->getPackageNamespace($package, $namespace);
        // In this method we will register the configuration package for the package
        // so that the configuration options cleanly cascade into the application
        // folder to make the developers lives much easier in maintaining them.
        $path = $path ?: $this->guessPackagePath();
        $config = $path . '/config';
        if ($this->app['files']->isDirectory($config)) {
            $this->app['config']->package($package, $config, $namespace);
        }
        // Next we will check for any "language" components. If language files exist
        // we will register them with this given package's namespace so that they
        // may be accessed using the translation facilities of the application.
        $lang = $path . '/lang';
        if ($this->app['files']->isDirectory($lang)) {
            $this->app['translator']->addNamespace($namespace, $lang);
        }
        // Finally we will register the view namespace so that we can access each of
        // the views available in this package. We use a standard convention when
        // registering the paths to every package's views and other components.
        $view = $path . '/views';
        if ($this->app['files']->isDirectory($view)) {
            $this->app['view']->addNamespace($namespace, $view);
        }
    }
    /**
     * Guess the package path for the provider.
     *
     * @return string
     */
    public function guessPackagePath()
    {
        $reflect = new ReflectionClass($this);
        // We want to get the class that is closest to this base class in the chain of
        // classes extending it. That should be the original service provider given
        // by the package and should allow us to guess the location of resources.
        $chain = $this->getClassChain($reflect);
        $path = $chain[count($chain) - 2]->getFileName();
        return realpath(dirname($path) . '/../../');
    }
    /**
     * Get a class from the ReflectionClass inheritance chain.
     *
     * @param  ReflectionClass  $reflection
     * @return array
     */
    protected function getClassChain(ReflectionClass $reflect)
    {
        $lastName = null;
        // This loop essentially walks the inheritance chain of the classes starting
        // at the most "childish" class and walks back up to this class. Once we
        // get to the end of the chain we will bail out and return the offset.
        while ($reflect !== false) {
            $classes[] = $reflect;
            $reflect = $reflect->getParentClass();
        }
        return $classes;
    }
    /**
     * Determine the namespace for a package.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @return string
     */
    protected function getPackageNamespace($package, $namespace)
    {
        if (is_null($namespace)) {
            list($vendor, $namespace) = explode('/', $package);
        }
        return $namespace;
    }
    /**
     * Register the package's custom Artisan commands.
     *
     * @param  dynamic  string
     * @return void
     */
    public function commands()
    {
        $commands = func_get_args();
        // To register the commands with Artisan, we will grab each of the arguments
        // passed into the method and listen for Artisan "start" event which will
        // give us the Artisan console instance which we will give commands to.
        $events = $this->app['events'];
        $events->listen('artisan.start', function ($artisan) use($commands) {
            $artisan->resolveCommands($commands);
        });
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this->defer;
    }
}
namespace Illuminate\Exception;

use Closure;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Debug\ErrorHandler;
use Symfony\Component\HttpKernel\Debug\ExceptionHandler as KernelHandler;
class ExceptionServiceProvider extends ServiceProvider
{
    /**
     * Start the error handling facilities.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return void
     */
    public function startHandling($app)
    {
        $this->setExceptionHandler($app['exception.function']);
        // By registering the error handler with a level of -1, we state that we want
        // all PHP errors converted into ErrorExceptions and thrown which provides
        // a very strict development environment but prevents any unseen errors.
        $app['kernel.error']->register(-1);
        if (isset($app['env']) and $app['env'] != 'testing') {
            $this->registerShutdownHandler();
        }
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerKernelHandlers();
        $this->app['exception'] = $this->app->share(function () {
            return new Handler();
        });
        $this->registerExceptionHandler();
    }
    /**
     * Register the HttpKernel error and exception handlers.
     *
     * @return void
     */
    protected function registerKernelHandlers()
    {
        $app = $this->app;
        $app['kernel.error'] = function () {
            return new ErrorHandler();
        };
        $this->app['kernel.exception'] = function () use($app) {
            return new KernelHandler($app['config']['app.debug']);
        };
    }
    /**
     * Register the PHP exception handler function.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $app = $this->app;
        $app['exception.function'] = function () use($app) {
            return function ($exception) use($app) {
                $response = $app['exception']->handle($exception);
                // If one of the custom error handlers returned a response, we will send that
                // response back to the client after preparing it. This allows a specific
                // type of exceptions to handled by a Closure giving great flexibility.
                if (!is_null($response)) {
                    $response = $app->prepareResponse($response, $app['request']);
                    $response->send();
                } else {
                    $app['kernel.exception']->handle($exception);
                }
            };
        };
    }
    /**
     * Register the shutdown handler Closure.
     *
     * @return void
     */
    protected function registerShutdownHandler()
    {
        $app = $this->app;
        register_shutdown_function(function () use($app) {
            set_exception_handler(array(new StubShutdownHandler($app), 'handle'));
            $app['kernel.error']->handleFatal();
        });
    }
    /**
     * Set the given Closure as the exception handler.
     *
     * This function is mainly needed for mocking purposes.
     *
     * @param  Closure  $handler
     * @return mixed
     */
    protected function setExceptionHandler(Closure $handler)
    {
        return set_exception_handler($handler);
    }
}
namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;
class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRouter();
        $this->registerUrlGenerator();
        $this->registerRedirector();
    }
    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function ($app) {
            $router = new Router($app);
            // If the current application environment is "testing", we will disable the
            // routing filters, since they can be tested independently of the routes
            // and just get in the way of our typical controller testing concerns.
            if ($app['env'] == 'testing') {
                $router->disableFilters();
            }
            return $router;
        });
    }
    /**
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app['url'] = $this->app->share(function ($app) {
            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();
            return new UrlGenerator($routes, $app['request']);
        });
    }
    /**
     * Register the Redirector service.
     *
     * @return void
     */
    protected function registerRedirector()
    {
        $this->app['redirect'] = $this->app->share(function ($app) {
            $redirector = new Redirector($app['url']);
            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session'])) {
                $redirector->setSession($app['session']);
            }
            return $redirector;
        });
    }
}
namespace Illuminate\Events;

use Illuminate\Support\ServiceProvider;
class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['events'] = $this->app->share(function ($app) {
            return new Dispatcher($app);
        });
    }
}
namespace Illuminate\Support\Facades;

abstract class Facade
{
    /**
     * The application instance being facaded.
     *
     * @var Illuminate\Foundation\Application
     */
    protected static $app;
    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstance;
    /**
     * Hotswap the underlying instance behind the facade.
     *
     * @param  mixed  $instance
     * @return void
     */
    public static function swap($instance)
    {
        static::$resolvedInstance[static::getFacadeAccessor()] = $instance;
        static::$app->instance(static::getFacadeAccessor(), $instance);
    }
    /**
     * Initiate a mock expectation on the facade.
     *
     * @param  dynamic
     * @return Mockery\Expectation
     */
    public static function shouldReceive()
    {
        $name = static::getFacadeAccessor();
        static::$resolvedInstance[$name] = $mock = \Mockery::mock(get_class(static::getFacadeRoot()));
        static::$app->instance($name, $mock);
        return call_user_func_array(array($mock, 'shouldReceive'), func_get_args());
    }
    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        throw new \RuntimeException('Facade does not implement getFacadeAccessor method.');
    }
    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }
        return static::$resolvedInstance[$name] = static::$app[$name];
    }
    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = array();
    }
    /**
     * Get the application instance behind the facade.
     *
     * @return Illuminate\Foundation\Application
     */
    public static function getFacadeApplication()
    {
        return static::$app;
    }
    /**
     * Set the application instance.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return void
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }
    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::resolveFacadeInstance(static::getFacadeAccessor());
        switch (count($args)) {
            case 0:
                return $instance->{$method}();
            case 1:
                return $instance->{$method}($args[0]);
            case 2:
                return $instance->{$method}($args[0], $args[1]);
            case 3:
                return $instance->{$method}($args[0], $args[1], $args[2]);
            case 4:
                return $instance->{$method}($args[0], $args[1], $args[2], $args[3]);
            default:
                return call_user_func_array(array($instance, $method), $args);
        }
    }
}
namespace Illuminate\Support;

class Str
{
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
        foreach ((array) $needle as $n) {
            if (strpos($haystack, $n) !== false) {
                return true;
            }
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
        return rtrim($value, $cap) . $cap;
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
        if ($pattern !== '/') {
            $pattern = str_replace('*', '(.*)', $pattern) . '\\z';
        } else {
            $pattern = '/$';
        }
        return (bool) preg_match('#^' . $pattern . '#', $value);
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
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit, 'UTF-8') . $end;
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
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int     $length
     * @return string
     */
    public static function random($length = 16)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);
            if ($bytes === false) {
                throw new \RuntimeException('Unable to generate random string.');
            }
            return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
        }
        return static::quickRandom($length);
    }
    /**
     * Generate a "random" alpha-numeric string.
     *
     * Should not be considered sufficient for cryptography, etc.
     *
     * @param  int     $length
     * @return string
     */
    public static function quickRandom($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
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
        $title = preg_replace('![^' . preg_quote($separator) . '\\pL\\pN\\s]+!u', '', mb_strtolower($title));
        // Convert all dashes/undescores into separator
        $flip = $separator == '-' ? '_' : '-';
        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);
        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\\s]+!u', $separator, $title);
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
        $replace = '$1' . $delimiter . '$2';
        return ctype_lower($value) ? $value : strtolower(preg_replace('/(.)([A-Z])/', $replace, $value));
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
        foreach ((array) $needles as $needle) {
            if (strpos($haystack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpKernel\Debug;

use Symfony\Component\HttpKernel\Exception\FatalErrorException;
use Psr\Log\LoggerInterface;
/**
 * ErrorHandler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ErrorHandler
{
    const TYPE_DEPRECATION = -100;
    private $levels = array(E_WARNING => 'Warning', E_NOTICE => 'Notice', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice', E_STRICT => 'Runtime Notice', E_RECOVERABLE_ERROR => 'Catchable Fatal Error', E_DEPRECATED => 'Deprecated', E_USER_DEPRECATED => 'User Deprecated', E_ERROR => 'Error', E_CORE_ERROR => 'Core Error', E_COMPILE_ERROR => 'Compile Error', E_PARSE => 'Parse');
    private $level;
    private $reservedMemory;
    /** @var LoggerInterface */
    private static $logger;
    /**
     * Register the error handler.
     *
     * @param integer $level The level at which the conversion to Exception is done (null to use the error_reporting() value and 0 to disable)
     *
     * @return The registered error handler
     */
    public static function register($level = null)
    {
        $handler = new static();
        $handler->setLevel($level);
        ini_set('display_errors', 0);
        set_error_handler(array($handler, 'handle'));
        register_shutdown_function(array($handler, 'handleFatal'));
        $handler->reservedMemory = str_repeat('x', 10240);
        return $handler;
    }
    public function setLevel($level)
    {
        $this->level = null === $level ? error_reporting() : $level;
    }
    public static function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }
    /**
     * @throws \ErrorException When error_reporting returns error
     */
    public function handle($level, $message, $file, $line, $context)
    {
        if (0 === $this->level) {
            return false;
        }
        if ($level & (E_USER_DEPRECATED | E_DEPRECATED)) {
            if (null !== self::$logger) {
                $stack = version_compare(PHP_VERSION, '5.4', '<') ? array_slice(debug_backtrace(false), 0, 10) : debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
                self::$logger->warning($message, array('type' => self::TYPE_DEPRECATION, 'stack' => $stack));
            }
            return true;
        }
        if (error_reporting() & $level && $this->level & $level) {
            throw new \ErrorException(sprintf('%s: %s in %s line %d', isset($this->levels[$level]) ? $this->levels[$level] : $level, $message, $file, $line), 0, $level, $file, $line);
        }
        return false;
    }
    public function handleFatal()
    {
        if (null === ($error = error_get_last())) {
            return;
        }
        unset($this->reservedMemory);
        $type = $error['type'];
        if (0 === $this->level || !in_array($type, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE))) {
            return;
        }
        // get current exception handler
        $exceptionHandler = set_exception_handler(function () {
            
        });
        restore_exception_handler();
        if (is_array($exceptionHandler) && $exceptionHandler[0] instanceof ExceptionHandler) {
            $level = isset($this->levels[$type]) ? $this->levels[$type] : $type;
            $message = sprintf('%s: %s in %s line %d', $level, $error['message'], $error['file'], $error['line']);
            $exception = new FatalErrorException($message, 0, $type, $error['file'], $error['line']);
            $exceptionHandler[0]->handle($exception);
        }
    }
}
namespace Illuminate\Config;

use Closure;
use ArrayAccess;
use Illuminate\Support\NamespacedItemResolver;
class Repository extends NamespacedItemResolver implements ArrayAccess
{
    /**
     * The loader implementation.
     *
     * @var Illuminate\Config\LoaderInterface
     */
    protected $loader;
    /**
     * The current environment.
     *
     * @var string
     */
    protected $environment;
    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = array();
    /**
     * All of the registered packages.
     *
     * @var array
     */
    protected $packages = array();
    /**
     * The after load callbacks for namespaces.
     *
     * @var array
     */
    protected $afterLoad = array();
    /**
     * Create a new configuration repository.
     *
     * @param  Illuminate\Config\LoaderInterface  $loader
     * @param  string  $environment
     * @return void
     */
    public function __construct(LoaderInterface $loader, $environment)
    {
        $this->loader = $loader;
        $this->environment = $environment;
    }
    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        $default = microtime(true);
        return $this->get($key, $default) != $default;
    }
    /**
     * Determine if a configuration group exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGroup($key)
    {
        list($namespace, $group, $item) = $this->parseKey($key);
        return $this->loader->exists($group, $namespace);
    }
    /**
     * Get the specified configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        list($namespace, $group, $item) = $this->parseKey($key);
        // Configuration items are actually keyed by "collection", which is simply a
        // combination of each namespace and groups, which allows a unique way to
        // identify the arrays of configuration items for the particular files.
        $collection = $this->getCollection($group, $namespace);
        $this->load($group, $namespace, $collection);
        return array_get($this->items[$collection], $item, $default);
    }
    /**
     * Set a given configuration value.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value)
    {
        list($namespace, $group, $item) = $this->parseKey($key);
        $collection = $this->getCollection($group, $namespace);
        // We'll need to go ahead and lazy load each configuration groups even when
        // we're just setting a configuration item so that the set item does not
        // get overwritten if a different item in the group is requested later.
        $this->load($group, $namespace, $collection);
        if (is_null($item)) {
            $this->items[$collection] = $value;
        } else {
            array_set($this->items[$collection], $item, $value);
        }
    }
    /**
     * Load the configuration group for the key.
     *
     * @param  string  $key
     * @param  string  $namespace
     * @param  string  $collection
     * @return void
     */
    protected function load($group, $namespace, $collection)
    {
        $env = $this->environment;
        // If we've already loaded this collection, we will just bail out since we do
        // not want to load it again. Once items are loaded a first time they will
        // stay kept in memory within this class and not loaded from disk again.
        if (isset($this->items[$collection])) {
            return;
        }
        $items = $this->loader->load($env, $group, $namespace);
        // If we've already loaded this collection, we will just bail out since we do
        // not want to load it again. Once items are loaded a first time they will
        // stay kept in memory within this class and not loaded from disk again.
        if (isset($this->afterLoad[$namespace])) {
            $items = $this->callAfterLoad($namespace, $group, $items);
        }
        $this->items[$collection] = $items;
    }
    /**
     * Call the after load callback for a namespace.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  array   $items
     * @return array
     */
    protected function callAfterLoad($namespace, $group, $items)
    {
        $callback = $this->afterLoad[$namespace];
        return call_user_func($callback, $this, $group, $items);
    }
    /**
     * Parse an array of namespaced segments.
     *
     * @param  string  $key
     * @return array
     */
    protected function parseNamespacedSegments($key)
    {
        list($namespace, $item) = explode('::', $key);
        // If the namespace is registered as a package, we will just assume the group
        // is equal to the naemspace since all packages cascade in this way having
        // a single file per package, otherwise we'll just parse them as normal.
        if (in_array($namespace, $this->packages)) {
            return $this->parsePackageSegments($key, $namespace, $item);
        }
        return parent::parseNamespacedSegments($key);
    }
    /**
     * Parse the segments of a package namespace.
     *
     * @param  string  $namespace
     * @param  string  $item
     * @return array
     */
    protected function parsePackageSegments($key, $namespace, $item)
    {
        $itemSegments = explode('.', $item);
        // If the configuration file doesn't exist for the given package group we can
        // assume that we should implicitly use the config file matching the name
        // of the namespace. Generally packages should use one type or another.
        if (!$this->loader->exists($itemSegments[0], $namespace)) {
            return array($namespace, 'config', $item);
        }
        return parent::parseNamespacedSegments($key);
    }
    /**
     * Register a package for cascading configuration.
     *
     * @param  string  $package
     * @param  string  $hint
     * @param  string  $namespace
     * @return void
     */
    public function package($package, $hint, $namespace = null)
    {
        $namespace = $this->getPackageNamespace($package, $namespace);
        $this->packages[] = $namespace;
        // First we will simply register the namespace with the repository so that it
        // can be loaded. Once we have done that we'll register an after namespace
        // callback so that we can cascade an application package configuration.
        $this->addNamespace($namespace, $hint);
        $this->afterLoading($namespace, function ($me, $group, $items) use($package) {
            $env = $me->getEnvironment();
            $loader = $me->getLoader();
            return $loader->cascadePackage($env, $package, $group, $items);
        });
    }
    /**
     * Get the configuration namespace for a package.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @return string
     */
    protected function getPackageNamespace($package, $namespace)
    {
        if (is_null($namespace)) {
            list($vendor, $namespace) = explode('/', $package);
        }
        return $namespace;
    }
    /**
     * Register an after load callback for a given namespace.
     *
     * @param  string   $namespace
     * @param  Closure  $callback
     * @return void
     */
    public function afterLoading($namespace, Closure $callback)
    {
        $this->afterLoad[$namespace] = $callback;
    }
    /**
     * Get the collection identifier.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @return string
     */
    protected function getCollection($group, $namespace = null)
    {
        $namespace = $namespace ?: '*';
        return $namespace . '::' . $group;
    }
    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        return $this->loader->addNamespace($namespace, $hint);
    }
    /**
     * Returns all registered namespaces with the config
     * loader.
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->loader->getNamespaces();
    }
    /**
     * Get the loader implementation.
     *
     * @return Illuminate\Config\LoaderInterface
     */
    public function getLoader()
    {
        return $this->loader;
    }
    /**
     * Set the loader implementation.
     *
     * @return Illuminate\Config\LoaderInterface
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }
    /**
     * Get the current configuration environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
    /**
     * Get the after load callback array.
     *
     * @return array
     */
    public function getAfterLoadCallbacks()
    {
        return $this->afterLoad;
    }
    /**
     * Get all of the configuration items.
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }
    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }
    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }
    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }
    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
namespace Illuminate\Support;

class NamespacedItemResolver
{
    /**
     * A cache of the parsed items.
     *
     * @var array
     */
    protected $parsed = array();
    /**
     * Parse a key into namespace, group, and item.
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        // If we've already parsed the given key, we'll return the cached version we
        // already have, as this will save us some processing. We cache off every
        // key we parse so we can quickly return it on all subsequent requests.
        if (isset($this->parsed[$key])) {
            return $this->parsed[$key];
        }
        $segments = explode('.', $key);
        // If the key does not contain a double colon, it means the key is not in a
        // namespace, and is just a regular configuration item. Namespaces are a
        // tool for organizing configuration items for things such as modules.
        if (strpos($key, '::') === false) {
            $parsed = $this->parseBasicSegments($segments);
        } else {
            $parsed = $this->parseNamespacedSegments($key);
        }
        // Once we have the parsed array of this key's elements, such as its groups
        // and namespace, we will cache each array inside a simple list that has
        // the key and the parsed array for quick look-ups for later requests.
        return $this->parsed[$key] = $parsed;
    }
    /**
     * Parse an array of basic segments.
     *
     * @param  array  $segments
     * @return array
     */
    protected function parseBasicSegments(array $segments)
    {
        // The first segment in a basic array will always be the group, so we can go
        // ahead and grab that segment. If there is only one total segment we are
        // just pulling an entire group out of the array and not a single item.
        $group = $segments[0];
        if (count($segments) == 1) {
            return array(null, $group, null);
        } else {
            $item = implode('.', array_slice($segments, 1));
            return array(null, $group, $item);
        }
    }
    /**
     * Parse an array of namespaced segments.
     *
     * @param  string  $key
     * @return array
     */
    protected function parseNamespacedSegments($key)
    {
        list($namespace, $item) = explode('::', $key);
        // First we'll just explode the first segment to get the namespace and group
        // since the item should be in the remaining segments. Once we have these
        // two pieces of data we can proceed with parsing out the item's value.
        $itemSegments = explode('.', $item);
        $groupAndItem = array_slice($this->parseBasicSegments($itemSegments), 1);
        return array_merge(array($namespace), $groupAndItem);
    }
    /**
     * Set the parsed value of a key.
     *
     * @param  string  $key
     * @param  array   $parsed
     * @return void
     */
    public function setParsedKey($key, $parsed)
    {
        $this->parsed[$key] = $parsed;
    }
}
namespace Illuminate\Config;

use Illuminate\Filesystem\Filesystem;
class FileLoader implements LoaderInterface
{
    /**
     * The filesystem instance.
     *
     * @var Illuminate\Filesystem
     */
    protected $files;
    /**
     * The default configuration path.
     *
     * @var string
     */
    protected $defaultPath;
    /**
     * All of the named path hints.
     *
     * @var array
     */
    protected $hints = array();
    /**
     * A cache of whether namespaces and groups exists.
     *
     * @var array
     */
    protected $exists = array();
    /**
     * Create a new file configuration loader.
     *
     * @param  Illuminate\Filesystem  $files
     * @param  string  $defaultPath
     * @return void
     */
    public function __construct(Filesystem $files, $defaultPath)
    {
        $this->files = $files;
        $this->defaultPath = $defaultPath;
    }
    /**
     * Load the given configuration group.
     *
     * @param  string  $environment
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    public function load($environment, $group, $namespace = null)
    {
        $items = array();
        // First we'll get the root configuration path for the environment which is
        // where all of the configuration files live for that namespace, as well
        // as any environment folders with their specific configuration items.
        $path = $this->getPath($namespace);
        if (is_null($path)) {
            return $items;
        }
        // First we'll get the main configuration file for the groups. Once we have
        // that we can check for any environment specific files, which will get
        // merged on top of the main arrays to make the environments cascade.
        $file = "{$path}/{$group}.php";
        if ($this->files->exists($file)) {
            $items = $this->files->getRequire($file);
        }
        // Finally we're ready to check for the environment specific configuration
        // file which will be merged on top of the main arrays so that they get
        // precedence over them if we are currently in an environments setup.
        $file = "{$path}/{$environment}/{$group}.php";
        if ($this->files->exists($file)) {
            $items = array_merge($items, $this->files->getRequire($file));
        }
        return $items;
    }
    /**
     * Determine if the given group exists.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @return bool
     */
    public function exists($group, $namespace = null)
    {
        $key = $group . $namespace;
        // We'll first check to see if we have determined if this namespace and
        // group combination have been checked before. If they have, we will
        // just return the cached result so we don't have to hit the disk.
        if (isset($this->exists[$key])) {
            return $this->exists[$key];
        }
        $path = $this->getPath($namespace);
        // To check if a group exists, we will simply get the path based on the
        // namespace, and then check to see if this files exists within that
        // namespace. False is returned if no path exists for a namespace.
        if (is_null($path)) {
            return $this->exists[$key] = false;
        }
        $file = "{$path}/{$group}.php";
        // Finally, we can simply check if this file exists. We will also cache
        // the value in an array so we don't have to go through this process
        // again on subsequent checks for the existing of the config file.
        $exists = $this->files->exists($file);
        return $this->exists[$key] = $exists;
    }
    /**
     * Apply any cascades to an array of package options.
     *
     * @param  string  $environment
     * @param  string  $package
     * @param  string  $group
     * @param  array   $items
     * @return array
     */
    public function cascadePackage($environment, $package, $group, $items)
    {
        // First we will look for a configuration file in the packages configuration
        // folder. If it exists, we will load it and merge it with these original
        // options so that we will easily "cascade" a package's configurations.
        $file = "packages/{$package}/{$group}.php";
        if ($this->files->exists($path = $this->defaultPath . '/' . $file)) {
            $items = array_merge($items, $this->getRequire($path));
        }
        // Once we have merged the regular package configuration we need to look for
        // an environment specific configuration file. If one exists, we will get
        // the contents and merge them on top of this array of options we have.
        $path = $this->defaultPath . "/{$environment}/" . $file;
        if ($this->files->exists($path)) {
            $items = array_merge($items, $this->getRequire($path));
        }
        return $items;
    }
    /**
     * Get the configuration path for a namespace.
     *
     * @param  string  $namespace
     * @return string
     */
    protected function getPath($namespace)
    {
        if (is_null($namespace)) {
            return $this->defaultPath;
        } elseif (isset($this->hints[$namespace])) {
            return $this->hints[$namespace];
        }
    }
    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }
    /**
     * Returns all registered namespaces with the config
     * loader.
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->hints;
    }
    /**
     * Get a file's contents by requiring it.
     *
     * @param  string  $path
     * @return mixed
     */
    protected function getRequire($path)
    {
        return $this->files->getRequire($path);
    }
    /**
     * Get the Filesystem instance.
     *
     * @return Illuminate\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
namespace Illuminate\Config;

interface LoaderInterface
{
    /**
     * Load the given configuration group.
     *
     * @param  string  $environment
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    public function load($environment, $group, $namespace = null);
    /**
     * Determine if the given configuration group exists.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @return bool
     */
    public function exists($group, $namespace = null);
    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint);
    /**
     * Returns all registered namespaces with the config
     * loader.
     *
     * @return array
     */
    public function getNamespaces();
    /**
     * Apply any cascades to an array of package options.
     *
     * @param  string  $environment
     * @param  string  $package
     * @param  string  $group
     * @param  array   $items
     * @return array
     */
    public function cascadePackage($environment, $package, $group, $items);
}
namespace Illuminate\Filesystem;

use FilesystemIterator;
class FileNotFoundException extends \Exception
{
    
}
class Filesystem
{
    /**
     * Determine if a file exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }
    /**
     * Get the contents of a file.
     *
     * @param  string  $path
     * @return string
     */
    public function get($path)
    {
        if ($this->exists($path)) {
            return file_get_contents($path);
        }
        throw new FileNotFoundException("File does not exist at path {$path}");
    }
    /**
     * Get the contents of a remote file.
     *
     * @param  string  $path
     * @return string
     */
    public function getRemote($path)
    {
        return file_get_contents($path);
    }
    /**
     * Get the returned value of a file.
     *
     * @param  string  $path
     * @return mixed
     */
    public function getRequire($path)
    {
        if ($this->exists($path)) {
            return require $path;
        }
        throw new FileNotFoundException("File does not exist at path {$path}");
    }
    /**
     * Require the given file once.
     *
     * @param  string  $file
     * @return void
     */
    public function requireOnce($file)
    {
        require_once $file;
    }
    /**
     * Write the contents of a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @return int
     */
    public function put($path, $contents)
    {
        return file_put_contents($path, $contents, LOCK_EX);
    }
    /**
     * Append to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, LOCK_EX | FILE_APPEND);
    }
    /**
     * Delete the file at a given path.
     *
     * @param  string  $path
     * @return bool
     */
    public function delete($path)
    {
        return unlink($path);
    }
    /**
     * Move a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return void
     */
    public function move($path, $target)
    {
        return rename($path, $target);
    }
    /**
     * Copy a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return void
     */
    public function copy($path, $target)
    {
        return copy($path, $target);
    }
    /**
     * Extract the file extension from a file path.
     * 
     * @param  string  $path
     * @return string
     */
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
    /**
     * Get the file type of a given file.
     *
     * @param  string  $path
     * @return string
     */
    public function type($path)
    {
        return filetype($path);
    }
    /**
     * Get the file size of a given file.
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }
    /**
     * Get the file's last modification time.
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path)
    {
        return filemtime($path);
    }
    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }
    /**
     * Find path names matching a given pattern.
     *
     * @param  string  $pattern
     * @param  int     $flags
     * @return array
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }
    /**
     * Get an array of all files in a directory.
     *
     * @param  string  $directory
     * @return array
     */
    public function files($directory)
    {
        $glob = glob($directory . '/*');
        if ($glob === false) {
            return array();
        }
        // To get the appropriate files, we'll simply glob the directory and filter
        // out any "files" that are not truly files so we do not end up with any
        // directories in our list, but only true files within the directory.
        return array_filter($glob, function ($file) {
            return filetype($file) == 'file';
        });
    }
    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int     $mode
     * @param  bool    $recursive
     * @return bool
     */
    public function makeDirectory($path, $mode = 511, $recursive = false)
    {
        return mkdir($path, $mode, $recursive);
    }
    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  int     $options
     * @return void
     */
    public function copyDirectory($directory, $destination, $options = null)
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }
        $options = $options ?: FilesystemIterator::SKIP_DOTS;
        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        if (!$this->isDirectory($destination)) {
            $this->makeDirectory($destination, 511, true);
        }
        $items = new FilesystemIterator($directory, $options);
        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination . '/' . $item->getBasename();
            if ($item->isDir()) {
                $path = $item->getRealPath();
                if (!$this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            } else {
                if (!$this->copy($item->getRealPath(), $target)) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string  $directory
     * @param  bool    $preserve
     * @return void
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        if (!$this->isDirectory($directory)) {
            return;
        }
        $items = new FilesystemIterator($directory);
        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-director, otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir()) {
                $this->deleteDirectory($item->getRealPath());
            } else {
                $this->delete($item->getRealPath());
            }
        }
        if (!$preserve) {
            @rmdir($directory);
        }
    }
    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory
     * @return void
     */
    public function cleanDirectory($directory)
    {
        return $this->deleteDirectory($directory, true);
    }
}
namespace Illuminate\Foundation;

class AliasLoader
{
    /**
     * The array of class aliases.
     *
     * @var array
     */
    protected $aliases;
    /**
     * Indicates if a loader has been registered.
     *
     * @var bool
     */
    protected $registered = false;
    /**
     * The singleton instance of the loader.
     *
     * @var Illuminate\Foundation\AliasLoader
     */
    protected static $instance;
    /**
     * Create a new class alias loader instance.
     *
     * @param  array  $aliases
     * @return void
     */
    public function __construct(array $aliases = array())
    {
        $this->aliases = $aliases;
    }
    /**
     * Get or create the singleton alias loader instance.
     *
     * @param  array  $aliases
     * @return Illuminate\Foundation\AliasLoader
     */
    public static function getInstance(array $aliases = array())
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($aliases);
        }
        $aliases = array_merge(static::$instance->getAliases(), $aliases);
        static::$instance->setAliases($aliases);
        return static::$instance;
    }
    /**
     * Load a class alias if it is registered.
     *
     * @param  string  $alias
     * @return void
     */
    public function load($alias)
    {
        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }
    }
    /**
     * Add an alias to the loader.
     *
     * @param  string  $class
     * @param  string  $alias
     * @return void
     */
    public function alias($class, $alias)
    {
        $this->aliases[$class] = $alias;
    }
    /**
     * Register the loader on the auto-loader stack.
     *
     * @return void
     */
    public function register()
    {
        if (!$this->registered) {
            $this->prependToLoaderStack();
            $this->registered = true;
        }
    }
    /**
     * Prepend the load method to the auto-loader stack.
     *
     * @return void
     */
    protected function prependToLoaderStack()
    {
        spl_autoload_register(array($this, 'load'), true, true);
    }
    /**
     * Get the registered aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }
    /**
     * Set the registered aliases.
     *
     * @param  array  $aliases
     * @return void
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
    }
    /**
     * Indicates if the loader has been registered.
     *
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }
    /**
     * Set the "registered" state of the loader.
     *
     * @param  bool  $value
     * @return void
     */
    public function setRegistered($value)
    {
        $this->registered = $value;
    }
    /**
     * Set the value of the singleton alias loader.
     *
     * @param  Illuminate\Foundation\AliasLoader  $loader
     * @return void
     */
    public static function setInstance($loader)
    {
        static::$instance = $loader;
    }
}
namespace Illuminate\Foundation;

use Illuminate\Filesystem\Filesystem;
class ProviderRepository
{
    /**
     * The filesystem instance.
     *
     * @var Illuminate\Filesystem
     */
    protected $files;
    /**
     * The path to the manifest.
     *
     * @var string
     */
    protected $manifestPath;
    /**
     * Create a new service repository instance.
     *
     * @param  Illuminate\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $manifestPath)
    {
        $this->files = $files;
        $this->manifestPath = $manifestPath;
    }
    /**
     * Register the application service providers.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @param  array  $providers
     * @param  string  $path
     * @return void
     */
    public function load(Application $app, array $providers)
    {
        $manifest = $this->loadManifest();
        // First we will load the service manifest, which contains information on all
        // service providers registered with the application and which services it
        // provides. This is used to know which services are "deferred" loaders.
        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($app, $providers);
        }
        // If the application is running in the console, we will not lazy load any of
        // the service providers. This is mainly because it's not as necessary for
        // performance and also so any provided Artisan commands get registered.
        if ($app->runningInConsole()) {
            $manifest['eager'] = $manifest['providers'];
        }
        // We will go ahead and register all of the eagerly loaded providers with the
        // application so their services can be registered with the application as
        // a provided service. Then we will set the deferred service list on it.
        foreach ($manifest['eager'] as $provider) {
            $app->register($this->createProvider($app, $provider));
        }
        $app->setDeferredServices($manifest['deferred']);
    }
    /**
     * Compile the application manifest file.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @param  array  $providers
     * @return array
     */
    protected function compileManifest(Application $app, $providers)
    {
        // The service manifest should contain a list of all of the providers for
        // the application so we can compare it on each request to the service
        // and determine if the manifest should be recompiled or is current.
        $manifest = $this->freshManifest($providers);
        foreach ($providers as $provider) {
            $instance = $this->createProvider($app, $provider);
            // When recomiling the service manifest, we will spin through each of the
            // providers and check if it's a deferred provider or not. If so we'll
            // add it's provided services to the manifest and note the provider.
            if ($instance->isDeferred()) {
                foreach ($instance->provides() as $service) {
                    $manifest['deferred'][$service] = $provider;
                }
            } else {
                $manifest['eager'][] = $provider;
            }
        }
        return $this->writeManifest($manifest);
    }
    /**
     * Create a new provider instance.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @param  string  $provider
     * @return Illuminate\Support\ServiceProvider
     */
    public function createProvider(Application $app, $provider)
    {
        return new $provider($app);
    }
    /**
     * Determine if the manifest should be compiled.
     *
     * @param  array  $manifest
     * @param  array  $providers
     * @return bool
     */
    public function shouldRecompile($manifest, $providers)
    {
        return is_null($manifest) or $manifest['providers'] != $providers;
    }
    /**
     * Load the service provider manifest JSON file.
     *
     * @return array
     */
    public function loadManifest()
    {
        $path = $this->manifestPath . '/services.json';
        // The service manifest is a file containing a JSON representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.
        if ($this->files->exists($path)) {
            return json_decode($this->files->get($path), true);
        }
    }
    /**
     * Write the service manifest file to disk.
     *
     * @param  array  $manifest
     * @return array
     */
    public function writeManifest($manifest)
    {
        $path = $this->manifestPath . '/services.json';
        $this->files->put($path, json_encode($manifest));
        return $manifest;
    }
    /**
     * Get the manifest file path.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return string
     */
    protected function getManifestPath($app)
    {
        return $this->manifestPath;
    }
    /**
     * Create a fresh manifest array.
     *
     * @param  array  $providers
     * @return array
     */
    protected function freshManifest(array $providers)
    {
        list($eager, $deferred) = array(array(), array());
        return compact('providers', 'eager', 'deferred');
    }
    /**
     * Get the filesystem instance.
     *
     * @return Illuminate\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
namespace Illuminate\Cookie;

use Illuminate\Support\ServiceProvider;
class CookieServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['cookie.defaults'] = $this->cookieDefaults();
        // The Illuminate cookie creator is just a convenient way to make cookies
        // that share a given set of options. Typically cookies created by the
        // application will have the same settings so this just DRY's it up.
        $this->app['cookie'] = $this->app->share(function ($app) {
            $options = $app['cookie.defaults'];
            return new CookieJar($app['request'], $app['encrypter'], $options);
        });
    }
    /**
     * Get the default cookie options.
     *
     * @return array
     */
    protected function cookieDefaults()
    {
        return array('path' => '/', 'domain' => null, 'secure' => false, 'httpOnly' => true);
    }
}
namespace Illuminate\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connectors\ConnectionFactory;
class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);
        Model::setEventDispatcher($this->app['events']);
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app['db.factory'] = $this->app->share(function () {
            return new ConnectionFactory();
        });
        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app['db'] = $this->app->share(function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });
    }
}
namespace Illuminate\Encryption;

use Illuminate\Support\ServiceProvider;
class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['encrypter'] = $this->app->share(function ($app) {
            return new Encrypter($app['config']['app.key']);
        });
    }
}
namespace Illuminate\Filesystem;

use Illuminate\Support\ServiceProvider;
class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['files'] = $this->app->share(function () {
            return new Filesystem();
        });
    }
}
namespace Illuminate\Session;

use Illuminate\Support\ServiceProvider;
class SessionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerSessionEvents();
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['session'] = $this->app->share(function ($app) {
            // First, we will create the session manager which is responsible for the
            // creation of the various session drivers when they are needed by the
            // application instance, and will resolve them on a lazy load basis.
            $manager = new SessionManager($app);
            $driver = $manager->driver();
            $config = $app['config']['session'];
            // Once we get an instance of the session driver, we need to set a few of
            // the session options based on the application configuration, such as
            // the session lifetime and the sweeper lottery configuration value.
            $driver->setLifetime($config['lifetime']);
            $driver->setSweepLottery($config['lottery']);
            return $driver;
        });
    }
    /**
     * Register the events needed for session management.
     *
     * @return void
     */
    protected function registerSessionEvents()
    {
        $app = $this->app;
        $config = $app['config']['session'];
        // The session needs to be started and closed, so we will register a before
        // and after events to do all stuff for us. This will manage the loading
        // the session "payloads", as well as writing them after each request.
        if (!is_null($config['driver'])) {
            $this->registerBootingEvent($app);
            $this->registerCloseEvent($app, $config);
        }
    }
    /**
     * Register the session booting event.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function registerBootingEvent($app)
    {
        $app->booting(function ($app) {
            $app['session']->start($app['cookie'], $app['config']['session.cookie']);
        });
    }
    /**
     * Register the session close event.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @param  array $config
     * @return void
     */
    protected function registerCloseEvent($app, $config)
    {
        $app->close(function ($request, $response) use($app, $config) {
            $session = $app['session'];
            $session->finish($response, $config['lifetime']);
            $cookie = $session->getCookie($app['cookie'], $config['cookie'], $config['lifetime']);
            if (!is_null($cookie)) {
                $response->headers->setCookie($cookie);
            }
        });
    }
}
namespace Illuminate\View;

use Illuminate\Support\MessageBag;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\BladeEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEngineResolver();
        $this->registerViewFinder();
        // Once the other components have been registered we're ready to include the
        // view environment and session binder. The session binder will bind onto
        // the "before" application event and add errors into shared view data.
        $this->registerEnvironment();
        $this->registerSessionBinder();
    }
    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        list($me, $app) = array($this, $this->app);
        $app['view.engine.resolver'] = $app->share(function ($app) use($me) {
            $resolver = new EngineResolver();
            // Next we will register the various engines with the resolver so that the
            // environment can resolve the engines it needs for various views based
            // on the extension of view files. We call a method for each engines.
            foreach (array('php', 'blade') as $engine) {
                $me->{'register' . ucfirst($engine) . 'Engine'}($resolver);
            }
            return $resolver;
        });
    }
    /**
     * Register the PHP engine implementation.
     *
     * @param  Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function () {
            return new PhpEngine();
        });
    }
    /**
     * Register the Blade engine implementation.
     *
     * @param  Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        $app = $this->app;
        $resolver->register('blade', function () use($app) {
            $cache = $app['path'] . '/storage/views';
            // The Compiler engine requires an instance of the CompilerInterface, which in
            // this case will be the Blade compiler, so we'll first create the compiler
            // instance to pass into the engine so it can compile the views properly.
            $compiler = new BladeCompiler($app['files'], $cache);
            return new CompilerEngine($compiler, $app['files']);
        });
    }
    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $this->app['view.finder'] = $this->app->share(function ($app) {
            $paths = $app['config']['view.paths'];
            return new FileViewFinder($app['files'], $paths);
        });
    }
    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerEnvironment()
    {
        $this->app['view'] = $this->app->share(function ($app) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $app['view.engine.resolver'];
            $finder = $app['view.finder'];
            $env = new Environment($resolver, $finder, $app['events']);
            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $env->setContainer($app);
            $env->share('app', $app);
            return $env;
        });
    }
    /**
     * Register the session binder for the view environment.
     *
     * @return void
     */
    protected function registerSessionBinder()
    {
        list($app, $me) = array($this->app, $this);
        $app->before(function () use($app, $me) {
            // If the current session has an "errors" variable bound to it, we will share
            // its value with all view instances so the views can easily access errors
            // without having to bind. An empty bag is set when there aren't errors.
            if ($me->sessionHasErrors($app)) {
                $errors = $app['session']->get('errors');
                $app['view']->share('errors', $errors);
            } else {
                $app['view']->share('errors', new MessageBag());
            }
        });
    }
    /**
     * Determine if the application session has errors.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return bool
     */
    public function sessionHasErrors($app)
    {
        $config = $app['config']['session'];
        if (isset($app['session']) and !is_null($config['driver'])) {
            return $app['session']->has('errors');
        }
    }
}
namespace Illuminate\Routing;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Illuminate\Routing\Controllers\Inspector;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
class Router
{
    /**
     * The route collection instance.
     *
     * @var Symfony\Component\Routing\RouteCollection
     */
    protected $routes;
    /**
     * The route filters.
     *
     * @var array
     */
    protected $filters = array();
    /**
     * The pattern to filter bindings.
     *
     * @var array
     */
    protected $patternFilters = array();
    /**
     * The global filters for the router.
     *
     * @var array
     */
    protected $globalFilters = array();
    /**
     * The stack of grouped attributes.
     *
     * @var array
     */
    protected $groupStack = array();
    /**
     * The inversion of control container instance.
     *
     * @var Illuminate\Container
     */
    protected $container;
    /**
     * The controller inspector instance.
     *
     * @var Illuminate\Routing\Controllers\Inspector
     */
    protected $inspector;
    /**
     * The global parameter patterns.
     *
     * @var array
     */
    protected $patterns = array();
    /**
     * The registered route binders.
     *
     * @var array
     */
    protected $binders = array();
    /**
     * The current request being dispatched.
     *
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $currentRequest;
    /**
     * The current route being executed.
     *
     * @var Illuminate\Routing\Route
     */
    protected $currentRoute;
    /**
     * Indicates if filters should be run.
     *
     * @var bool
     */
    protected $runFilters = true;
    /**
     * Create a new router instance.
     *
     * @param  Illuminate\Container  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
        $this->routes = new RouteCollection();
        $this->bind('_missing', function ($v) {
            return explode('/', $v);
        });
    }
    /**
     * Add a new route to the collection.
     *
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    public function get($pattern, $action)
    {
        return $this->createRoute('get', $pattern, $action);
    }
    /**
     * Add a new route to the collection.
     *
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    public function post($pattern, $action)
    {
        return $this->createRoute('post', $pattern, $action);
    }
    /**
     * Add a new route to the collection.
     *
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    public function put($pattern, $action)
    {
        return $this->createRoute('put', $pattern, $action);
    }
    /**
     * Add a new route to the collection.
     *
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    public function patch($pattern, $action)
    {
        return $this->createRoute('patch', $pattern, $action);
    }
    /**
     * Add a new route to the collection.
     *
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    public function delete($pattern, $action)
    {
        return $this->createRoute('delete', $pattern, $action);
    }
    /**
     * Add a new route to the collection.
     *
     * @param  string  $method
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    public function match($method, $pattern, $action)
    {
        return $this->createRoute($method, $pattern, $action);
    }
    /**
     * Add a new route to the collection.
     *
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    public function any($pattern, $action)
    {
        return $this->createRoute('get|post|put|patch|delete', $pattern, $action);
    }
    /**
     * Register an array of controllers with wildcard routing.
     *
     * @param  array  $controllers
     * @return void
     */
    public function controllers(array $controllers)
    {
        foreach ($controllers as $uri => $name) {
            $this->controller($uri, $name);
        }
    }
    /**
     * Route a controller to a URI with wildcard routing.
     *
     * @param  string  $uri
     * @param  string  $controller
     * @return Illuminate\Routing\Route
     */
    public function controller($uri, $controller)
    {
        $routable = $this->getInspector()->getRoutable($controller, $uri);
        // When a controller is routed using this method, we use Reflection to parse
        // out all of the routable methods for the controller, then register each
        // route explicitly for the developers, so reverse routing is possible.
        foreach ($routable as $method => $routes) {
            foreach ($routes as $route) {
                $this->{$route['verb']}($route['uri'], $controller . '@' . $method);
            }
        }
        $this->addFallthroughRoute($controller, $uri);
    }
    /**
     * Add a fallthrough route for a controller.
     *
     * @param  string  $controller
     * @param  string  $uri
     * @return void
     */
    protected function addFallthroughRoute($controller, $uri)
    {
        $missing = $this->any($uri . '/{_missing}', $controller . '@missingMethod');
        $missing->where('_missing', '(.*)');
    }
    /**
     * Route a resource to a controller.
     *
     * @param  string  $resource
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function resource($resource, $controller, array $options = array())
    {
        $defaults = array('index', 'create', 'store', 'show', 'edit', 'update', 'destroy');
        $base = $this->getBaseResource($resource);
        foreach ($this->getResourceMethods($defaults, $options) as $method) {
            $this->{'addResource' . ucfirst($method)}($resource, $base, $controller);
        }
    }
    /**
     * Get the applicable resource methods.
     *
     * @param  array  $defaults
     * @param  array  $options
     * @return array
     */
    protected function getResourceMethods($defaults, $options)
    {
        if (isset($options['only'])) {
            return array_intersect($defaults, $options['only']);
        } elseif (isset($options['except'])) {
            return array_diff($defaults, $options['except']);
        }
        return $defaults;
    }
    /**
     * Add the index method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addResourceIndex($name, $base, $controller)
    {
        $action = $this->getResourceAction($name, $controller, 'index');
        return $this->get($this->getResourceUri($name), $action);
    }
    /**
     * Add the create method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addResourceCreate($name, $base, $controller)
    {
        $action = $this->getResourceAction($name, $controller, 'create');
        return $this->get($this->getResourceUri($name) . '/create', $action);
    }
    /**
     * Add the store method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addResourceStore($name, $base, $controller)
    {
        $action = $this->getResourceAction($name, $controller, 'store');
        return $this->post($this->getResourceUri($name), $action);
    }
    /**
     * Add the show method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addResourceShow($name, $base, $controller)
    {
        $uri = $this->getResourceUri($name) . '/{' . $base . '}';
        return $this->get($uri, $this->getResourceAction($name, $controller, 'show'));
    }
    /**
     * Add the edit method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addResourceEdit($name, $base, $controller)
    {
        $uri = $this->getResourceUri($name) . '/{' . $base . '}/edit';
        return $this->get($uri, $this->getResourceAction($name, $controller, 'edit'));
    }
    /**
     * Add the update method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addResourceUpdate($name, $base, $controller)
    {
        $this->addPutResourceUpdate($name, $base, $controller);
        return $this->addPatchResourceUpdate($name, $base, $controller);
    }
    /**
     * Add the update method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addPutResourceUpdate($name, $base, $controller)
    {
        $uri = $this->getResourceUri($name) . '/{' . $base . '}';
        return $this->put($uri, $this->getResourceAction($name, $controller, 'update'));
    }
    /**
     * Add the update method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addPatchResourceUpdate($name, $base, $controller)
    {
        $uri = $this->getResourceUri($name) . '/{' . $base . '}';
        $this->patch($uri, $controller . '@update');
    }
    /**
     * Add the destroy method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addResourceDestroy($name, $base, $controller)
    {
        $uri = $this->getResourceUri($name) . '/{' . $base . '}';
        return $this->delete($uri, $this->getResourceAction($name, $controller, 'destroy'));
    }
    /**
     * Get the base resource URI for a given resource.
     *
     * @param  string  $resource
     * @return string
     */
    public function getResourceUri($resource)
    {
        if (!str_contains($resource, '.')) {
            return $resource;
        }
        // To create the nested resource URI, we will simply explode the segments and
        // create a base URI for each of them, then join all of them back together
        // with slashes. This should create the properly nested resource routes.
        $nested = implode('/', array_map(function ($segment) {
            return $segment . '/{' . $segment . '}';
        }, $segments = explode('.', $resource)));
        // Once we have built the base URI, we'll remove the wildcard holder for this
        // base resource name so that the individual route adders can suffix these
        // paths however they need to, as some do not have any wildcards at all.
        $last = $segments[count($segments) - 1];
        return str_replace('/{' . $last . '}', '', $nested);
    }
    /**
     * Get the action array for a resource route.
     *
     * @param  string  $resource
     * @param  string  $controller
     * @param  string  $method
     * @return array
     */
    protected function getResourceAction($resource, $controller, $method)
    {
        return array('as' => $resource . '.' . $method, 'uses' => $controller . '@' . $method);
    }
    /**
     * Get the base resource from a resource name.
     *
     * @param  string  $resource
     * @return string
     */
    protected function getBaseResource($resource)
    {
        $segments = explode('.', $resource);
        return $segments[count($segments) - 1];
    }
    /**
     * Create a route group with shared attributes.
     *
     * @param  array    $attributes
     * @param  Closure  $callback
     * @return void
     */
    public function group(array $attributes, Closure $callback)
    {
        $this->updateGroupStack($attributes);
        call_user_func($callback);
        array_pop($this->groupStack);
    }
    /**
     * Update the group stack array.
     *
     * @param  array  $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if (count($this->groupStack) > 0) {
            $last = count($this->groupStack) - 1;
            $this->groupStack[] = array_merge_recursive($this->groupStack[$last], $attributes);
        } else {
            $this->groupStack[] = $attributes;
        }
    }
    /**
     * Create a new route instance.
     *
     * @param  string  $method
     * @param  string  $pattern
     * @param  mixed   $action
     * @return Illuminate\Routing\Route
     */
    protected function createRoute($method, $pattern, $action)
    {
        // We will force the action parameters to be an array just for convenience.
        // This will let us examine it for other attributes like middlewares or
        // a specific HTTP schemes the route only responds to, such as HTTPS.
        if (!is_array($action)) {
            $action = $this->parseAction($action);
        }
        $groupCount = count($this->groupStack);
        // If there are attributes being grouped across routes we will merge those
        // attributes into the action array so that they will get shared across
        // the routes. The route can override the attribute by specifying it.
        if ($groupCount > 0) {
            $index = $groupCount - 1;
            $action = array_merge($this->groupStack[$index], $action);
        }
        // Next we will parse the pattern and add any specified prefix to the it so
        // a common URI prefix may be specified for a group of routes easily and
        // without having to specify them all for every route that is defined.
        list($pattern, $optional) = $this->getOptional($pattern);
        if (isset($action['prefix'])) {
            $prefix = $action['prefix'];
            $pattern = $this->addPrefix($pattern, $prefix);
        }
        // We will create the routes, setting the Closure callbacks on the instance
        // so we can easily access it later. If there are other parameters on a
        // routes we'll also set those requirements as well such as defaults.
        $route = with(new Route($pattern))->setOptions(array('_call' => $this->getCallback($action)))->setRouter($this)->addRequirements($this->patterns);
        $route->setRequirement('_method', $method);
        // Once we have created the route, we will add them to our route collection
        // which contains all the other routes and is used to match on incoming
        // URL and their appropriate route destination and on URL generation.
        $this->setAttributes($route, $action, $optional);
        $name = $this->getName($method, $pattern, $action);
        $this->routes->add($name, $route);
        return $route;
    }
    /**
     * Parse the given route action into array form.
     *
     * @param  mixed  $action
     * @return array
     */
    protected function parseAction($action)
    {
        // If the action is just a Closure we'll stick it in an array and just send
        // it back out. However if it's a string we'll just assume it's meant to
        // route into a controller action and change it to a controller array.
        if ($action instanceof Closure) {
            return array($action);
        } elseif (is_string($action)) {
            return array('uses' => $action);
        }
        throw new \InvalidArgumentException('Unroutable action.');
    }
    /**
     * Add the given prefix to the given URI pattern.
     *
     * @param  string  $pattern
     * @param  string  $prefix
     * @return string
     */
    protected function addPrefix($pattern, $prefix)
    {
        $pattern = trim($prefix, '/') . '/' . ltrim($pattern, '/');
        return trim($pattern, '/');
    }
    /**
     * Set the attributes and requirements on the route.
     *
     * @param  Illuminate\Routing\Route  $route
     * @param  array  $action
     * @param  array  $optional
     * @return void
     */
    protected function setAttributes(Route $route, $action, $optional)
    {
        // First we will set the requirement for the HTTP schemes. Some routes may
        // only respond to requests using the HTTPS scheme, while others might
        // respond to all, regardless of the scheme, so we'll set that here.
        if (in_array('https', $action)) {
            $route->setRequirement('_scheme', 'https');
        }
        if (in_array('http', $action)) {
            $route->setRequirement('_scheme', 'http');
        }
        // Once the scheme requirements have been made, we will set the before and
        // after middleware options, which will be used to run any middlewares
        // by the consuming library, making halting the request cycles easy.
        if (isset($action['before'])) {
            $route->setBeforeFilters($action['before']);
        }
        if (isset($action['after'])) {
            $route->setAfterFilters($action['after']);
        }
        // If there is a "uses" key on the route it means it is using a controller
        // instead of a Closures route. So, we'll need to set that as an option
        // on the route so we can easily do reverse routing ot the route URI.
        if (isset($action['uses'])) {
            $route->setOption('_uses', $action['uses']);
        }
        if (isset($action['domain'])) {
            $route->setHost($action['domain']);
        }
        // Finally we will go through and set all of the default variables to null
        // so the developer doesn't have to manually specify one each time they
        // are declared on a route. This is simply for developer convenience.
        foreach ($optional as $key) {
            $route->setDefault($key, null);
        }
    }
    /**
     * Modify the pattern and extract optional parameters.
     *
     * @param  string  $pattern
     * @return array
     */
    protected function getOptional($pattern)
    {
        $optional = array();
        preg_match_all('#\\{(\\w+)\\?\\}#', $pattern, $matches);
        // For each matching value, we will extract the name of the optional values
        // and add it to our array, then we will replace the place-holder to be
        // a valid place-holder minus this optional indicating question mark.
        foreach ($matches[0] as $key => $value) {
            $optional[] = $name = $matches[1][$key];
            $pattern = str_replace($value, '{' . $name . '}', $pattern);
        }
        return array($pattern, $optional);
    }
    /**
     * Get the name of the route.
     *
     * @param  string  $method
     * @param  string  $pattern
     * @param  array   $action
     * @return string
     */
    protected function getName($method, $pattern, array $action)
    {
        if (isset($action['as'])) {
            return $action['as'];
        }
        $domain = isset($action['domain']) ? $action['domain'] . ' ' : '';
        return "{$domain}{$method} {$pattern}";
    }
    /**
     * Get the callback from the given action array.
     *
     * @param  array    $action
     * @return Closure
     */
    protected function getCallback(array $action)
    {
        foreach ($action as $key => $attribute) {
            // If the action has a "uses" key, the route is pointing to a controller
            // action instead of using a Closure. So, we'll create a Closure that
            // resolves the controller instances and calls the needed function.
            if ($key === 'uses') {
                return $this->createControllerCallback($attribute);
            } elseif ($attribute instanceof Closure) {
                return $attribute;
            }
        }
    }
    /**
     * Create the controller callback for a route.
     *
     * @param  string   $attribute
     * @return Closure
     */
    protected function createControllerCallback($attribute)
    {
        $ioc = $this->container;
        $me = $this;
        // We'll return a Closure that is able to resolve the controller instance and
        // call the appropriate method on the controller, passing in the arguments
        // it receives. Controllers are created with the IoC container instance.
        return function () use($me, $ioc, $attribute) {
            list($controller, $method) = explode('@', $attribute);
            $route = $me->getCurrentRoute();
            // We will extract the passed in parameters off of the route object so we will
            // pass them off to the controller method as arguments. We will not get the
            // defaults so that the controllers will be able to use its own defaults.
            $args = array_values($route->getParametersWithoutDefaults());
            $instance = $ioc->make($controller);
            return $instance->callAction($ioc, $me, $method, $args);
        };
    }
    /**
     * Get the response for a given request.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;
        // First we will call the "before" global middlware, which we'll give a chance
        // to override the normal requests process when a response is returned by a
        // middleware. Otherwise we'll call the route just like a normal request.
        $response = $this->callGlobalFilter($request, 'before');
        if (!is_null($response)) {
            $response = $this->prepare($response, $request);
        } else {
            $this->currentRoute = $route = $this->findRoute($request);
            $response = $route->run($request);
        }
        // Finally after the route has been run we can call the after and close global
        // filters for the request, giving a chance for any final processing to get
        // done before the response gets returned back to the user's web browser.
        $this->callAfterFilter($request, $response);
        return $response;
    }
    /**
     * Match the given request to a route object.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return Illuminate\Routing\Route
     */
    protected function findRoute(Request $request)
    {
        // We will catch any exceptions thrown during routing and convert it to a
        // HTTP Kernel equivalent exception, since that is a more generic type
        // that's used by the Illuminate foundation framework for responses.
        try {
            $path = $this->formatRequestPath($request);
            $parameters = $this->getUrlMatcher($request)->match($path);
        } catch (ExceptionInterface $e) {
            $this->handleRoutingException($e);
        }
        $route = $this->routes->get($parameters['_route']);
        // If we found a route, we will grab the actual route objects out of this
        // route collection and set the matching parameters on the instance so
        // we will easily access them later if the route action is executed.
        $route->setParameters($parameters);
        return $route;
    }
    /**
     * Format the request path info for routing.
     *
     * @param  Illuminate\Http\Request  $request
     * @return string
     */
    protected function formatRequestPath($request)
    {
        $path = $request->getPathInfo();
        if (strlen($path) > 1 and ends_with($path, '/')) {
            return '/' . ltrim(substr($path, 0, -1), '/');
        }
        return '/' . ltrim($path, '/');
    }
    /**
     * Register a "before" routing filter.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function before($callback)
    {
        $this->globalFilters['before'][] = $this->buildGlobalFilter($callback);
    }
    /**
     * Register an "after" routing filter.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function after($callback)
    {
        $this->globalFilters['after'][] = $this->buildGlobalFilter($callback);
    }
    /**
     * Register a "close" routing filter.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function close($callback)
    {
        $this->globalFilters['close'][] = $this->buildGlobalFilter($callback);
    }
    /**
     * Register a "finish" routing filters.
     *
     * @param  Closure|string  $callback
     * @return void
     */
    public function finish($callback)
    {
        $this->globalFilters['finish'][] = $this->buildGlobalFilter($callback);
    }
    /**
     * Build a global filter definition for the router.
     *
     * @param  Closure|string  $callback
     * @return Closure
     */
    protected function buildGlobalFilter($callback)
    {
        if (is_string($callback)) {
            $container = $this->container;
            // When the given "callback" is actually a string, we will assume that it is
            // a filter class that we need to resolve out of an IoC container to call
            // the filter method on the instance, passing in the arguments we take.
            return function () use($callback, $container) {
                $callable = array($container->make($callback), 'filter');
                return call_user_func_array($callable, func_get_args());
            };
        } else {
            return $callback;
        }
    }
    /**
     * Register a new filter with the application.
     *
     * @param  string   $name
     * @param  Closure|string  $callback
     * @return void
     */
    public function addFilter($name, $callback)
    {
        $this->filters[$name] = $callback;
    }
    /**
     * Get a registered filter callback.
     *
     * @param  string   $name
     * @return Closure
     */
    public function getFilter($name)
    {
        if (array_key_exists($name, $this->filters)) {
            $filter = $this->filters[$name];
            // If the filter is a string, it means we are using a class based Filter which
            // allows for the easier testing of the filter's methods rather than trying
            // to test a Closure. So, we will resolve the class out of the container.
            if (is_string($filter)) {
                return $this->getClassBasedFilter($filter);
            }
            return $filter;
        }
    }
    /**
     * Get a callable array for a class based filter.
     *
     * @param  string  $filter
     * @return array
     */
    protected function getClassBasedFilter($filter)
    {
        if (str_contains($filter, '@')) {
            list($class, $method) = explode('@', $filter);
            return array($this->container->make($class), $method);
        }
        return array($this->container->make($filter), 'filter');
    }
    /**
     * Tie a registered filter to a URI pattern.
     *
     * @param  string  $pattern
     * @param  string|array  $names
     * @return void
     */
    public function matchFilter($pattern, $names)
    {
        foreach ((array) $names as $name) {
            $this->patternFilters[$pattern][] = $name;
        }
    }
    /**
     * Find the patterned filters matching a request.
     *
     * @param  Illuminate\Foundation\Request  $request
     * @return array
     */
    public function findPatternFilters(Request $request)
    {
        $filters = array();
        foreach ($this->patternFilters as $pattern => $values) {
            // To find the pattern middlewares for a request, we just need to check the
            // registered patterns against the path info for the current request to
            // the application, and if it matches we'll merge in the middlewares.
            if (str_is('/' . $pattern, $request->getPathInfo())) {
                $filters = array_merge($filters, $values);
            }
        }
        return $filters;
    }
    /**
     * Call the "after" global filters.
     *
     * @param  Symfony\Component\HttpFoundation\Request   $request
     * @param  Symfony\Component\HttpFoundation\Response  $response
     * @return mixed
     */
    protected function callAfterFilter(Request $request, SymfonyResponse $response)
    {
        $this->callGlobalFilter($request, 'after', array($response));
        $this->callGlobalFilter($request, 'close', array($response));
    }
    /**
     * Call the "finish" global filter.
     *
     * @param  Symfony\Component\HttpFoundation\Request   $request
     * @param  Symfony\Component\HttpFoundation\Response  $response
     * @return mixed
     */
    public function callFinishFilter(Request $request, SymfonyResponse $response)
    {
        return $this->callGlobalFilter($request, 'finish', array($response));
    }
    /**
     * Call a given global filter with the parameters.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $name
     * @param  array   $parameters
     * @return mixed
     */
    protected function callGlobalFilter(Request $request, $name, array $parameters = array())
    {
        if (!$this->filtersEnabled()) {
            return;
        }
        array_unshift($parameters, $request);
        if (isset($this->globalFilters[$name])) {
            // There may be multiple handlers registered for a global middleware so we
            // will need to spin through each one and execute each of them and will
            // return back first non-null responses we come across from a filter.
            foreach ($this->globalFilters[$name] as $filter) {
                $response = call_user_func_array($filter, $parameters);
                if (!is_null($response)) {
                    return $response;
                }
            }
        }
    }
    /**
     * Set a global where pattern on all routes
     *
     * @param  string  $key
     * @param  string  $pattern
     * @return void
     */
    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
    }
    /**
     * Register a model binder for a wildcard.
     *
     * @param  string  $key
     * @param  string  $class
     * @return void
     */
    public function model($key, $class)
    {
        return $this->bind($key, function ($value) use($class) {
            if (is_null($value)) {
                return null;
            }
            // For model binders, we will attempt to retrieve the model using the find
            // method on the model instance. If we cannot retrieve the models we'll
            // throw a not found exception otherwise we will return the instance.
            if (!is_null($model = with(new $class())->find($value))) {
                return $model;
            }
            throw new NotFoundHttpException();
        });
    }
    /**
     * Register a custom parameter binder.
     *
     * @param  string  $key
     * @param  mixed   $binder
     */
    public function bind($key, $binder)
    {
        $this->binders[$key] = $binder;
    }
    /**
     * Determine if a given key has a registered binder.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasBinder($key)
    {
        return isset($this->binders[$key]);
    }
    /**
     * Call a binder for a given wildcard.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  Illuminate\Routing\Route  $route
     * @return mixed
     */
    public function performBinding($key, $value, $route)
    {
        return call_user_func($this->binders[$key], $value, $route);
    }
    /**
     * Prepare the given value as a Response object.
     *
     * @param  mixed  $value
     * @param  Illuminate\Foundation\Request  $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function prepare($value, Request $request)
    {
        if (!$value instanceof SymfonyResponse) {
            $value = new Response($value);
        }
        return $value->prepare($request);
    }
    /**
     * Convert routing exception to HttpKernel version.
     *
     * @param  Exception  $e
     * @return void
     */
    protected function handleRoutingException(\Exception $e)
    {
        if ($e instanceof ResourceNotFoundException) {
            throw new NotFoundHttpException($e->getMessage());
        } elseif ($e instanceof MethodNotAllowedException) {
            $allowed = $e->getAllowedMethods();
            throw new MethodNotAllowedHttpException($allowed, $e->getMessage());
        }
    }
    /**
     * Determine if the current route has a given name.
     *
     * @param  string  $name
     * @return bool
     */
    public function currentRouteNamed($name)
    {
        $route = $this->routes->get($name);
        return !is_null($route) and $route === $this->currentRoute;
    }
    /**
     * Determine if the current route uses a given controller action.
     *
     * @param  string  $action
     * @return bool
     */
    public function currentRouteUses($action)
    {
        return $this->currentRoute->getOption('_uses') === $action;
    }
    /**
     * Determine if route filters are enabled.
     *
     * @return bool
     */
    public function filtersEnabled()
    {
        return $this->runFilters;
    }
    /**
     * Enable the running of filters.
     *
     * @return void
     */
    public function enableFilters()
    {
        $this->runFilters = true;
    }
    /**
     * Disable the runnning of all filters.
     *
     * @return void
     */
    public function disableFilters()
    {
        $this->runFilters = false;
    }
    /**
     * Retrieve the entire route collection.
     * 
     * @return Symfony\Component\Routing\RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }
    /**
     * Get the current request being dispatched.
     *
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->currentRequest;
    }
    /**
     * Get the current route being executed.
     *
     * @return Illuminate\Routing\Route
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }
    /**
     * Set the current route on the router.
     *
     * @param  Illuminate\Routing\Route  $route
     * @return void
     */
    public function setCurrentRoute(Route $route)
    {
        $this->currentRoute = $route;
    }
    /**
     * Get the filters defined on the router.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
    /**
     * Get the global filters defined on the router.
     *
     * @return array
     */
    public function getGlobalFilters()
    {
        return $this->globalFilters;
    }
    /**
     * Create a new URL matcher instance.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return Symfony\Component\Routing\Matcher\UrlMatcher
     */
    protected function getUrlMatcher(Request $request)
    {
        $context = new RequestContext();
        $context->fromRequest($request);
        return new UrlMatcher($this->routes, $context);
    }
    /**
     * Get the controller inspector instance.
     *
     * @return Illuminate\Routing\Controllers\Inspector
     */
    public function getInspector()
    {
        return $this->inspector ?: new Controllers\Inspector();
    }
    /**
     * Set the controller inspector instance.
     *
     * @param  Illuminate\Routing\Controllers\Inspector  $inspector
     * @return void
     */
    public function setInspector(Inspector $inspector)
    {
        $this->inspector = $inspector;
    }
    /**
     * Get the container used by the router.
     *
     * @return Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }
    /**
     * Set the container instance on the router.
     *
     * @param  Illuminate\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing;

use Symfony\Component\Config\Resource\ResourceInterface;
/**
 * A RouteCollection represents a set of Route instances.
 *
 * When adding a route at the end of the collection, an existing route
 * with the same name is removed first. So there can only be one route
 * with a given name.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 *
 * @api
 */
class RouteCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var Route[]
     */
    private $routes = array();
    /**
     * @var array
     */
    private $resources = array();
    /**
     * @var string
     * @deprecated since version 2.2, will be removed in 2.3
     */
    private $prefix = '';
    /**
     * @var RouteCollection|null
     * @deprecated since version 2.2, will be removed in 2.3
     */
    private $parent;
    public function __clone()
    {
        foreach ($this->routes as $name => $route) {
            $this->routes[$name] = clone $route;
        }
    }
    /**
     * Gets the parent RouteCollection.
     *
     * @return RouteCollection|null The parent RouteCollection or null when it's the root
     *
     * @deprecated since version 2.2, will be removed in 2.3
     */
    public function getParent()
    {
        return $this->parent;
    }
    /**
     * Gets the root RouteCollection.
     *
     * @return RouteCollection The root RouteCollection
     *
     * @deprecated since version 2.2, will be removed in 2.3
     */
    public function getRoot()
    {
        $parent = $this;
        while ($parent->getParent()) {
            $parent = $parent->getParent();
        }
        return $parent;
    }
    /**
     * Gets the current RouteCollection as an Iterator that includes all routes.
     *
     * It implements \IteratorAggregate.
     *
     * @see all()
     *
     * @return \ArrayIterator An \ArrayIterator object for iterating over routes
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }
    /**
     * Gets the number of Routes in this collection.
     *
     * @return int The number of routes
     */
    public function count()
    {
        return count($this->routes);
    }
    /**
     * Adds a route.
     *
     * @param string $name  The route name
     * @param Route  $route A Route instance
     *
     * @api
     */
    public function add($name, Route $route)
    {
        unset($this->routes[$name]);
        $this->routes[$name] = $route;
    }
    /**
     * Returns all routes in this collection.
     *
     * @return Route[] An array of routes
     */
    public function all()
    {
        return $this->routes;
    }
    /**
     * Gets a route by name.
     *
     * @param string $name The route name
     *
     * @return Route|null A Route instance or null when not found
     */
    public function get($name)
    {
        return isset($this->routes[$name]) ? $this->routes[$name] : null;
    }
    /**
     * Removes a route or an array of routes by name from the collection
     *
     * For BC it's also removed from the root, which will not be the case in 2.3
     * as the RouteCollection won't be a tree structure.
     *
     * @param string|array $name The route name or an array of route names
     */
    public function remove($name)
    {
        // just for BC
        $root = $this->getRoot();
        foreach ((array) $name as $n) {
            unset($root->routes[$n]);
            unset($this->routes[$n]);
        }
    }
    /**
     * Adds a route collection at the end of the current set by appending all
     * routes of the added collection.
     *
     * @param RouteCollection $collection      A RouteCollection instance
     *
     * @api
     */
    public function addCollection(RouteCollection $collection)
    {
        // This is to keep BC for getParent() and getRoot(). It does not prevent
        // infinite loops by recursive referencing. But we don't need that logic
        // anymore as the tree logic has been deprecated and we are just widening
        // the accepted range.
        $collection->parent = $this;
        // this is to keep BC
        $numargs = func_num_args();
        if ($numargs > 1) {
            $collection->addPrefix($this->prefix . func_get_arg(1));
            if ($numargs > 2) {
                $collection->addDefaults(func_get_arg(2));
                if ($numargs > 3) {
                    $collection->addRequirements(func_get_arg(3));
                    if ($numargs > 4) {
                        $collection->addOptions(func_get_arg(4));
                    }
                }
            }
        } else {
            // the sub-collection must have the prefix of the parent (current instance) prepended because it does not
            // necessarily already have it applied (depending on the order RouteCollections are added to each other)
            // this will be removed when the BC layer for getPrefix() is removed
            $collection->addPrefix($this->prefix);
        }
        // we need to remove all routes with the same names first because just replacing them
        // would not place the new route at the end of the merged array
        foreach ($collection->all() as $name => $route) {
            unset($this->routes[$name]);
            $this->routes[$name] = $route;
        }
        $this->resources = array_merge($this->resources, $collection->getResources());
    }
    /**
     * Adds a prefix to the path of all child routes.
     *
     * @param string $prefix       An optional prefix to add before each pattern of the route collection
     * @param array  $defaults     An array of default values
     * @param array  $requirements An array of requirements
     *
     * @api
     */
    public function addPrefix($prefix, array $defaults = array(), array $requirements = array())
    {
        $prefix = trim(trim($prefix), '/');
        if ('' === $prefix) {
            return;
        }
        // a prefix must start with a single slash and must not end with a slash
        $this->prefix = '/' . $prefix . $this->prefix;
        // this is to keep BC
        $options = func_num_args() > 3 ? func_get_arg(3) : array();
        foreach ($this->routes as $route) {
            $route->setPath('/' . $prefix . $route->getPath());
            $route->addDefaults($defaults);
            $route->addRequirements($requirements);
            $route->addOptions($options);
        }
    }
    /**
     * Returns the prefix that may contain placeholders.
     *
     * @return string The prefix
     *
     * @deprecated since version 2.2, will be removed in 2.3
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    /**
     * Sets the host pattern on all routes.
     *
     * @param string $pattern      The pattern
     * @param array  $defaults     An array of default values
     * @param array  $requirements An array of requirements
     */
    public function setHost($pattern, array $defaults = array(), array $requirements = array())
    {
        foreach ($this->routes as $route) {
            $route->setHost($pattern);
            $route->addDefaults($defaults);
            $route->addRequirements($requirements);
        }
    }
    /**
     * Adds defaults to all routes.
     *
     * An existing default value under the same name in a route will be overridden.
     *
     * @param array $defaults An array of default values
     */
    public function addDefaults(array $defaults)
    {
        if ($defaults) {
            foreach ($this->routes as $route) {
                $route->addDefaults($defaults);
            }
        }
    }
    /**
     * Adds requirements to all routes.
     *
     * An existing requirement under the same name in a route will be overridden.
     *
     * @param array $requirements An array of requirements
     */
    public function addRequirements(array $requirements)
    {
        if ($requirements) {
            foreach ($this->routes as $route) {
                $route->addRequirements($requirements);
            }
        }
    }
    /**
     * Adds options to all routes.
     *
     * An existing option value under the same name in a route will be overridden.
     *
     * @param array $options An array of options
     */
    public function addOptions(array $options)
    {
        if ($options) {
            foreach ($this->routes as $route) {
                $route->addOptions($options);
            }
        }
    }
    /**
     * Sets the schemes (e.g. 'https') all child routes are restricted to.
     *
     * @param string|array $schemes The scheme or an array of schemes
     */
    public function setSchemes($schemes)
    {
        foreach ($this->routes as $route) {
            $route->setSchemes($schemes);
        }
    }
    /**
     * Sets the HTTP methods (e.g. 'POST') all child routes are restricted to.
     *
     * @param string|array $methods The method or an array of methods
     */
    public function setMethods($methods)
    {
        foreach ($this->routes as $route) {
            $route->setMethods($methods);
        }
    }
    /**
     * Returns an array of resources loaded to build this collection.
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources()
    {
        return array_unique($this->resources);
    }
    /**
     * Adds a resource for this collection.
     *
     * @param ResourceInterface $resource A resource instance
     */
    public function addResource(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }
}
namespace Illuminate\Workbench;

use Illuminate\Support\ServiceProvider;
use Illuminate\Workbench\Console\WorkbenchMakeCommand;
class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['package.creator'] = $this->app->share(function ($app) {
            return new PackageCreator($app['files']);
        });
        $this->app['command.workbench'] = $this->app->share(function ($app) {
            return new WorkbenchMakeCommand($app['package.creator']);
        });
        $this->commands('command.workbench');
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('package.creator', 'command.workbench');
    }
}
namespace Illuminate\Events;

use Illuminate\Container\Container;
class Dispatcher
{
    /**
     * The IoC container instance.
     *
     * @var Illuminate\Container
     */
    protected $container;
    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = array();
    /**
     * The sorted event listeners.
     *
     * @var array
     */
    protected $sorted = array();
    /**
     * Create a new event dispatcher instance.
     *
     * @param  Illuminate\Container  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container;
    }
    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string  $event
     * @param  mixed   $listener
     * @param  int     $priority
     * @return void
     */
    public function listen($event, $listener, $priority = 0)
    {
        $this->listeners[$event][$priority][] = $this->makeListener($listener);
        unset($this->sorted[$event]);
    }
    /**
     * Determine if a given event has listeners.
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]);
    }
    /**
     * Register a queued event and payload.
     *
     * @param  string  $event
     * @param  array   $payload
     * @return void
     */
    public function queue($event, $payload = array())
    {
        $me = $this;
        $this->listen($event . '_queue', function () use($me, $event, $payload) {
            $me->fire($event, $payload);
        });
    }
    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        $subscriber = $this->resolveSubscriber($subscriber);
        $subscriber->subscribe($this);
    }
    /**
     * Resolve the subscriber instance.
     *
     * @param  mixed  $subscriber
     * @return mixed
     */
    protected function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }
        return $subscriber;
    }
    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param  string  $event
     * @param  array   $payload
     * @return mixed
     */
    public function until($event, $payload = array())
    {
        return $this->fire($event, $payload, true);
    }
    /**
     * Flush a set of queued events.
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        $this->fire($event . '_queue');
    }
    /**
     * Fire an event and call the listeners.
     *
     * @param  string  $event
     * @param  mixed   $payload
     * @return void
     */
    public function fire($event, $payload = array(), $halt = false)
    {
        $responses = array();
        // If an array is not given to us as the payload, we will turn it into one so
        // we can easily use call_user_func_array on the listeners, passing in the
        // payload to each of them so that they receive each of these arguments.
        if (!is_array($payload)) {
            $payload = array($payload);
        }
        foreach ($this->getListeners($event) as $listener) {
            $response = call_user_func_array($listener, $payload);
            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if (!is_null($response) and $halt) {
                return $response;
            }
            // If a boolean false is returned from a listener, we will stop propogating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }
            $responses[] = $response;
        }
        return $halt ? null : $responses;
    }
    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string  $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        if (!isset($this->sorted[$eventName])) {
            $this->sortListeners($eventName);
        }
        return $this->sorted[$eventName];
    }
    /**
     * Sort the listeners for a given event by priority.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function sortListeners($eventName)
    {
        $this->sorted[$eventName] = array();
        // If listeners exist for the given event, we will sort them by the priority
        // so that we can call them in the correct order. We will cache off these
        // sorted event listeners so we do not have to re-sort on every events.
        if (isset($this->listeners[$eventName])) {
            krsort($this->listeners[$eventName]);
            $this->sorted[$eventName] = call_user_func_array('array_merge', $this->listeners[$eventName]);
        }
    }
    /**
     * Register an event listener with the dispatcher.
     *
     * @param  mixed   $listener
     * @return void
     */
    public function makeListener($listener)
    {
        if (is_string($listener)) {
            $listener = $this->createClassListener($listener);
        }
        return $listener;
    }
    /**
     * Create a class based listener using the IoC container.
     *
     * @param  mixed    $listener
     * @return Closure
     */
    public function createClassListener($listener)
    {
        $container = $this->container;
        return function () use($listener, $container) {
            // If the listener has an @ sign, we will assume it is being used to delimit
            // the class name from the handle method name. This allows for handlers
            // to run multiple handler methods in a single class for convenience.
            $segments = explode('@', $listener);
            $method = count($segments) == 2 ? $segments[1] : 'handle';
            $callable = array($container->make($segments[0]), $method);
            // We will make a callable of the listener instance and a method that should
            // be called on that instance, then we will pass in the arguments that we
            // received in this method into this listener class instance's methods.
            $data = func_get_args();
            return call_user_func_array($callable, $data);
        };
    }
}
namespace Illuminate\Database\Eloquent;

use Closure;
use DateTime;
use ArrayAccess;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
abstract class Model implements ArrayAccess, ArrayableInterface, JsonableInterface
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = array();
    /**
     * The model attribute's original state.
     *
     * @var array
     */
    protected $original = array();
    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = array();
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = array();
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = array();
    /**
     * The attribute that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = array();
    /**
     * The date fields for the model.clear
     *
     * @var array
     */
    protected $dates = array();
    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = array();
    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;
    /**
     * The connection resolver instance.
     *
     * @var Illuminate\Database\ConnectionResolverInterface
     */
    protected static $resolver;
    /**
     * The event dispatcher instance.
     *
     * @var Illuminate\Events\Dispacher
     */
    protected static $dispatcher;
    /**
     * The array of booted models.
     *
     * @var array
     */
    protected static $booted = array();
    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = array())
    {
        if (!isset(static::$booted[get_class($this)])) {
            static::boot();
            static::$booted[get_class($this)] = true;
        }
        $this->fill($attributes);
    }
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        
    }
    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return Illuminate\Database\Eloquent\Model
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            // The developers may choose to place some attributes in the "fillable"
            // array, which means only those attributes may be set through mass
            // assignment to the model, and all others will just be ignored.
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }
    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool   $exists
     * @return Illuminate\Database\Eloquent\Model
     */
    public function newInstance($attributes = array(), $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $model = new static((array) $attributes);
        $model->exists = $exists;
        return $model;
    }
    /**
     * Create a new model instance that is existing.
     *
     * @param  array  $attributes
     * @return Illuminate\Database\Eloquent\Model
     */
    public function newExisting($attributes = array())
    {
        return $this->newInstance($attributes, true);
    }
    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return Illuminate\Database\Eloquent\Model
     */
    public static function create(array $attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
    /**
     * Begin querying the model on a given connection.
     *
     * @param  string  $connection
     * @return Illuminate\Database\Eloquent\Builder
     */
    public static function on($connection)
    {
        // First we will just create a fresh instance of this model, and then we can
        // set the connection on the model so that it is be used for the queries
        // we execute, as well as being set on each relationship we retrieve.
        $instance = new static();
        $instance->setConnection($connection);
        return $instance->newQuery();
    }
    /**
     * Get all of the models from the database.
     *
     * @param  array  $columns
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function all($columns = array('*'))
    {
        $instance = new static();
        return $instance->newQuery()->get($columns);
    }
    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return Illuminate\Database\Eloquent\Model|Collection
     */
    public static function find($id, $columns = array('*'))
    {
        $instance = new static();
        if (is_array($id)) {
            return $instance->newQuery()->whereIn($id)->get($columns);
        }
        return $instance->newQuery()->find($id, $columns);
    }
    /**
     * Being querying a model with eager loading.
     *
     * @param  array  $relations
     * @return Illuminate\Database\Eloquent\Builder
     */
    public static function with($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        $instance = new static();
        return $instance->newQuery()->with($relations);
    }
    /**
     * Define a one-to-one relationship.
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @return Illuminate\Database\Eloquent\Relation\HasOne
     */
    public function hasOne($related, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related();
        return new HasOne($instance->newQuery(), $this, $foreignKey);
    }
    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $foreignKey
     * @return Illuminate\Database\Eloquent\Relation\MorphOne
     */
    public function morphOne($related, $name)
    {
        $instance = new $related();
        return new MorphOne($instance->newQuery(), $this, $name);
    }
    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null)
    {
        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            list(, $caller) = debug_backtrace(false);
            $foreignKey = snake_case($caller['function']) . '_id';
        }
        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $instance = new $related();
        $query = $instance->newQuery();
        return new BelongsTo($query, $this, $foreignKey);
    }
    /**
     * Define an polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string  $name
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function morphTo($name = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        if (is_null($name)) {
            list(, $caller) = debug_backtrace(false);
            $name = snake_case($caller['function']);
        }
        return $this->belongsTo($this->{"{$name}_type"}, "{$name}_id");
    }
    /**
     * Define a one-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related();
        return new HasMany($instance->newQuery(), $this, $foreignKey);
    }
    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $foreignKey
     * @return Illuminate\Database\Eloquent\Relation\MorphMany
     */
    public function morphMany($related, $name)
    {
        $instance = new $related();
        return new MorphMany($instance->newQuery(), $this, $name);
    }
    /**
     * Define a many-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null)
    {
        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related();
        $otherKey = $otherKey ?: $instance->getForeignKey();
        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }
        // Now we're ready to create a new query builder for the related model and
        // the relationship instances for the relation. The relations will set
        // appropriate query constraint and entirely manages the hydrations.
        $query = $instance->newQuery();
        return new BelongsToMany($query, $this, $table, $foreignKey, $otherKey);
    }
    /**
     * Get the joining table name for a many-to-many relation.
     *
     * @param  string  $related
     * @return string
     */
    public function joiningTable($related)
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
        $base = snake_case(class_basename($this));
        $related = snake_case(class_basename($related));
        $models = array($related, $base);
        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        sort($models);
        return strtolower(implode('_', $models));
    }
    /**
     * Delete the model from the database.
     *
     * @return void
     */
    public function delete()
    {
        if ($this->exists) {
            $key = $this->getKeyName();
            return $this->newQuery()->where($key, $this->getKey())->delete();
        }
    }
    /**
     * Register an updating model event with the dispatcher.
     *
     * @param  Closure  $callback
     * @return void
     */
    public static function updating(Closure $callback)
    {
        static::registerModelEvent('updating', $callback);
    }
    /**
     * Register an updated model event with the dispatcher.
     *
     * @param  Closure  $callback
     * @return void
     */
    public static function updated(Closure $callback)
    {
        static::registerModelEvent('updated', $callback);
    }
    /**
     * Register a creating model event with the dispatcher.
     *
     * @param  Closure  $callback
     * @return void
     */
    public static function creating(Closure $callback)
    {
        static::registerModelEvent('creating', $callback);
    }
    /**
     * Register a created model event with the dispatcher.
     *
     * @param  Closure  $callback
     * @return void
     */
    public static function created(Closure $callback)
    {
        static::registerModelEvent('created', $callback);
    }
    /**
     * Register a model event with the dispatcher.
     *
     * @param  string   $event
     * @param  Closure  $callback
     * @return void
     */
    protected static function registerModelEvent($event, Closure $callback)
    {
        if (isset(static::$dispatcher)) {
            $name = get_called_class();
            static::$dispatcher->listen("eloquent.{$event}: {$name}", $callback);
        }
    }
    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save()
    {
        $query = $this->newQuery();
        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->timestamps) {
            $this->updateTimestamps();
        }
        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            $saved = $this->performUpdate($query);
        } else {
            $saved = $this->performInsert($query);
            $this->exists = $saved;
        }
        return $saved;
    }
    /**
     * Perform a model update operation.
     *
     * @param  Illuminate\Database\Eloquent\Builder
     * @return bool
     */
    protected function performUpdate($query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }
        $this->setKeysForSaveQuery($query)->update($this->attributes);
        $this->fireModelEvent('updated', false);
        return true;
    }
    /**
     * Perform a model insert operation.
     *
     * @param  Illuminate\Database\Eloquent\Builder
     * @return bool
     */
    protected function performInsert($query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }
        $attributes = $this->attributes;
        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        if ($this->incrementing) {
            $keyName = $this->getKeyName();
            $id = $query->insertGetId($attributes, $keyName);
            $this->setAttribute($keyName, $id);
        } else {
            $query->insert($attributes);
        }
        $this->fireModelEvent('created', false);
        return true;
    }
    /**
     * Fire the given event for the model.
     *
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }
        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "eloquent.{$event}: " . get_class($this);
        $method = $halt ? 'until' : 'fire';
        return static::$dispatcher->{$method}($event, $this);
    }
    /**
     * Set the keys for a save update query.
     *
     * @param  Illuminate\Database\Eloquent\Builder
     * @return void
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKey());
        return $query;
    }
    /**
     * Update the model's updat timestamp.
     *
     * @return bool
     */
    public function touch()
    {
        $this->updateTimestamps();
        return $this->save();
    }
    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $this->updated_at = $this->freshTimestamp();
        if (!$this->exists) {
            $this->created_at = $this->updated_at;
        }
    }
    /**
     * Get a fresh timestamp for the model.
     *
     * @return mixed
     */
    public function freshTimestamp()
    {
        return new DateTime();
    }
    /**
     * Get a new query builder for the model's table.
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        $builder = new Builder($this->newBaseQueryBuilder());
        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        $builder->setModel($this)->with($this->with);
        return $builder;
    }
    /**
     * Get a new query builder instance for the connection.
     *
     * @return Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();
        $grammar = $conn->getQueryGrammar();
        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
    }
    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }
        return str_replace('\\', '', snake_case(str_plural(get_class($this))));
    }
    /**
     * Set the table associated with the model.
     *
     * @param  string  $table
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
    }
    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }
    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }
    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        return $this->timestamps;
    }
    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }
    /**
     * Set the number of models ot return per page.
     *
     * @param  int   $perPage
     * @return void
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
    }
    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return snake_case(class_basename($this)) . '_id';
    }
    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }
    /**
     * Set the hidden attributes for the model.
     *
     * @param  array  $hidden
     * @return void
     */
    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;
    }
    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }
    /**
     * Set the fillable attributes for the model.
     *
     * @param  array  $fillable
     * @return Illuminate\Database\Eloquent\Model
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;
        return $this;
    }
    /**
     * Set the guarded attributes for the model.
     *
     * @param  array  $guarded
     * @return Illuminate\Database\Eloquent\Model
     */
    public function guard(array $guarded)
    {
        $this->guarded = $guarded;
        return $this;
    }
    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key)
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }
        if (in_array($key, $this->guarded) or $this->guarded == array('*')) {
            return false;
        }
        return empty($this->fillable);
    }
    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return $this->incrementing;
    }
    /**
     * Set whether IDs are incrementing.
     *
     * @param  bool  $value
     * @return void
     */
    public function setIncrementing($value)
    {
        $this->incrementing = $value;
    }
    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = JSON_NUMERIC_CHECK)
    {
        return json_encode($this->toArray(), $options);
    }
    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = array_diff_key($this->attributes, array_flip($this->hidden));
        return array_merge($attributes, $this->relationsToArray());
    }
    /**
     * Get the model's relationships in array form.
     *
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = array();
        foreach ($this->relations as $key => $value) {
            // If the values implements the Arrayable interface we can just call this
            // toArray method on the instances which will convert both models and
            // collections to their proper array form and we'll set the values.
            if ($value instanceof ArrayableInterface) {
                $attributes[$key] = $value->toArray();
            } elseif (is_null($value)) {
                $attributes[$key] = $value;
            }
        }
        return $attributes;
    }
    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $snake = snake_case($key);
        $inAttributes = array_key_exists($snake, $this->attributes);
        // If the key references an attribute, we can just go ahead and return the
        // plain attribute value from the model. This allows every attribute to
        // be dynamically accessed through the _get method without accessors.
        if ($inAttributes or $this->hasGetMutator($snake)) {
            return $this->getPlainAttribute($snake);
        }
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if (array_key_exists($snake, $this->relations)) {
            return $this->relations[$snake];
        }
        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        if (method_exists($this, $key)) {
            $relations = $this->{$key}()->getResults();
            return $this->relations[$snake] = $relations;
        }
    }
    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getPlainAttribute($key)
    {
        $value = $this->getAttributeFromArray($key);
        // If the attribute has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
        if ($this->hasGetMutator($key)) {
            $accessor = 'get' . camel_case($key) . 'Attribute';
            return $this->{$accessor}($value);
        } elseif (in_array($key, $this->dates)) {
            if ($value) {
                return $this->asDateTime($value);
            }
        }
        return $value;
    }
    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }
    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get' . camel_case($key) . 'Attribute');
    }
    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $key = snake_case($key);
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $method = 'set' . camel_case($key) . 'Attribute';
            return $this->{$method}($value);
        } elseif (in_array($key, $this->dates)) {
            if ($value) {
                $this->attributes[$key] = $this->fromDateTime($value);
            }
        }
        $this->attributes[$key] = $value;
    }
    /**
     * Determine if a set mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set' . camel_case($key) . 'Attribute');
    }
    /**
     * Convert a DateTime to a storable string.
     *
     * @param  DateTime  $value
     * @return string
     */
    protected function fromDateTime(DateTime $value)
    {
        return $value->format($this->getDateFormat());
    }
    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return DateTime
     */
    protected function asDateTime($value)
    {
        if (!$value instanceof DateTime) {
            $format = $this->getDateFormat();
            return DateTime::createFromFormat($format, $value);
        }
        return $value;
    }
    /**
     * Get the format for databsae stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->getConnection()->getQueryGrammar()->getDateFormat();
    }
    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    /**
     * Set the array of model attributes. No checking is done.
     *
     * @param  array  $attributes
     * @param  bool   $sync
     * @return void
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;
        if ($sync) {
            $this->syncOriginal();
        }
    }
    /**
     * Get the model's original attribute values.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return array
     */
    public function getOriginal($key = null, $default = null)
    {
        return array_get($this->original, $key, $default);
    }
    /**
     * Sync the original attributes with the current.
     *
     * @return void
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;
    }
    /**
     * Get a specified relationship.
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation)
    {
        return $this->relations[$relation];
    }
    /**
     * Set the specific relationship in the model.
     *
     * @param  string  $relation
     * @param  mixed   $value
     * @return void
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;
    }
    /**
     * Get the database connection for the model.
     *
     * @return Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->connection);
    }
    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }
    /**
     * Set the connection associated with the model.
     *
     * @param  string  $name
     * @return void
     */
    public function setConnection($name)
    {
        $this->connection = $name;
    }
    /**
     * Resolve a connection instance by name.
     *
     * @param  string  $connection
     * @return Illuminate\Database\Connection
     */
    public static function resolveConnection($connection)
    {
        return static::$resolver->connection($connection);
    }
    /**
     * Get the connection resolver instance.
     *
     * @return Illuminate\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }
    /**
     * Set the connection resolver instance.
     *
     * @param  Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }
    /**
     * Get the event dispatcher instance.
     *
     * @return Illuminate\Events\Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispathcer;
    }
    /**
     * Set the event dispatcher instance.
     *
     * @param  Illuminate\Events\Dispatcher
     * @return void
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }
    /**
     * Unset the event dispatcher for models.
     *
     * @return void
     */
    public static function unsetEventDispatcher()
    {
        static::$dispatcher = null;
    }
    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }
    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }
    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }
    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }
    /**
     * Determine if an attribute exists on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]) or isset($this->relations[$key]);
    }
    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
        unset($this->relations[$key]);
    }
    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = $this->newQuery();
        return call_user_func_array(array($query, $method), $parameters);
    }
    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static();
        return call_user_func_array(array($instance, $method), $parameters);
    }
    /**
     * Conver the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
namespace Illuminate\Support\Contracts;

interface ArrayableInterface
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray();
}
namespace Illuminate\Support\Contracts;

interface JsonableInterface
{
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);
}
namespace Illuminate\Database;

use Illuminate\Support\Manager;
use Illuminate\Database\Connectors\ConnectionFactory;
class DatabaseManager implements ConnectionResolverInterface
{
    /**
     * The application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;
    /**
     * The database connection factory instance.
     *
     * @var Illuminate\Database\Connectors\ConnectionFactory
     */
    protected $factory;
    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = array();
    /**
     * The custom connection resolvers.
     *
     * @var array
     */
    protected $extensions = array();
    /**
     * Create a new database manager instance.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @param  Illuminate\Database\Connectors\ConnectionFactory  $factory
     * @return void
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }
    /**
     * Get a database connection instance.
     *
     * @param  string  $name
     * @return Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();
        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (!isset($this->connections[$name])) {
            $connection = $this->makeConnection($name);
            $this->connections[$name] = $this->prepare($connection);
        }
        return $this->connections[$name];
    }
    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return Illuminate\Database\Connection
     */
    protected function makeConnection($name)
    {
        $config = $this->getConfig($name);
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config);
        }
        return $this->factory->make($config, $name);
    }
    /**
     * Prepare the database connection instance.
     *
     * @param  Illuminate\Database\Connection  $connection
     * @return Illuminate\Database\Connection
     */
    protected function prepare(Connection $connection)
    {
        $connection->setFetchMode($this->app['config']['database.fetch']);
        $connection->setEventDispatcher($this->app['events']);
        // We will setup a Closure to resolve the paginator instance on the connection
        // since the Paginator isn't sued on every request and needs quite a few of
        // our dependencies. It'll be more efficient to lazily resolve instances.
        $app = $this->app;
        $connection->setPaginator(function () use($app) {
            return $app['paginator'];
        });
        return $connection;
    }
    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        $name = $name ?: $this->getDefaultConnection();
        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->app['config']['database.connections'];
        if (is_null($config = array_get($connections, $name))) {
            throw new \InvalidArgumentException("Database [{$name}] not configured.");
        }
        return $config;
    }
    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['database.default'];
    }
    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['database.default'] = $name;
    }
    /**
     * Register an extension connection resolver.
     *
     * @param  string    $name
     * @param  callable  $resolver
     * @return void
     */
    public function extend($name, $resolver)
    {
        $this->extensions[$name] = $resolver;
    }
    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->connection(), $method), $parameters);
    }
}
namespace Illuminate\Database;

interface ConnectionResolverInterface
{
    /**
     * Get a database connection instance.
     *
     * @param  string  $name
     * @return Illuminate\Database\Connection
     */
    public function connection($name = null);
    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection();
    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name);
}
namespace Illuminate\Database\Connectors;

use PDO;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;
class ConnectionFactory
{
    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array   $config
     * @param  string  $name
     * @return Illuminate\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        if (!isset($config['prefix'])) {
            $config['prefix'] = '';
        }
        $pdo = $this->createConnector($config)->connect($config);
        return $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $name);
    }
    /**
     * Create a connector instance based on the configuration.
     *
     * @param  array  $config
     * @return Illuminate\Database\Connectors\ConnectorInterface
     */
    public function createConnector(array $config)
    {
        if (!isset($config['driver'])) {
            throw new \InvalidArgumentException('A driver must be specified.');
        }
        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector();
            case 'pgsql':
                return new PostgresConnector();
            case 'sqlite':
                return new SQLiteConnector();
            case 'sqlsrv':
                return new SqlServerConnector();
        }
        throw new \InvalidArgumentException("Unsupported driver [{$config['driver']}");
    }
    /**
     * Create a new connection instance.
     *
     * @param  string  $driver
     * @param  PDO     $connection
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  string  $name
     * @return Illuminate\Database\Connection
     */
    protected function createConnection($driver, PDO $connection, $database, $tablePrefix = '', $name = null)
    {
        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $tablePrefix, $name);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $tablePrefix, $name);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $tablePrefix, $name);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $tablePrefix, $name);
        }
        throw new \InvalidArgumentException("Unsupported driver [{$driver}]");
    }
}
namespace Illuminate\Session;

use Closure;
use ArrayAccess;
use Illuminate\Support\Str;
use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Contracts\SessionStoreInterface;
abstract class Store implements ArrayAccess
{
    /**
     * The current session payload.
     *
     * @var array
     */
    protected $session;
    /**
     * Indicates if the session already existed.
     *
     * @var bool
     */
    protected $exists = true;
    /**
     * The session lifetime in minutes.
     *
     * @var int
     */
    protected $lifetime = 120;
    /**
     * The chances of hitting the sweeper lottery.
     *
     * @var array
     */
    protected $sweep = array(2, 100);
    /**
     * The session cookie options array.
     *
     * @var array
     */
    protected $cookie = array('name' => 'illuminate_session', 'path' => '/', 'domain' => null, 'secure' => false, 'http_only' => true);
    /**
     * Retrieve a session payload from storage.
     *
     * @param  string  $id
     * @return array|null
     */
    public abstract function retrieveSession($id);
    /**
     * Create a new session in storage.
     *
     * @param  string  $id
     * @param  array   $session
     * @param  Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public abstract function createSession($id, array $session, Response $response);
    /**
     * Update an existing session in storage.
     *
     * @param  string  $id
     * @param  array   $session
     * @param  Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public abstract function updateSession($id, array $session, Response $response);
    /**
     * Load the session for the request.
     *
     * @param  Illuminate\CookieJar  $cookies
     * @param  string  $name
     * @return void
     */
    public function start(CookieJar $cookies, $name)
    {
        $id = $cookies->get($name);
        // If the session ID was available via the request cookies we'll just retrieve
        // the session payload from the driver and check the given session to make
        // sure it's valid. All the data fetching is implemented by the driver.
        if (!is_null($id)) {
            $session = $this->retrieveSession($id);
        }
        // If the session is not valid, we will create a new payload and will indicate
        // that the session has not yet been created. These freshly created session
        // payloads will be given a fresh session ID so there are not collisions.
        if (!isset($session) or $this->isInvalid($session)) {
            $this->exists = false;
            $session = $this->createFreshSession();
        }
        // Once the session payloads have been created or loaded we will set it to an
        // internal values that are managed by the driver. The values are not sent
        // back into storage until the sessions are closed after these requests.
        $this->session = $session;
    }
    /**
     * Create a fresh session payload.
     *
     * @return array
     */
    protected function createFreshSession()
    {
        $flash = $this->createData();
        return array('id' => $this->createSessionID(), 'data' => $flash);
    }
    /**
     * Create a new, empty session data array.
     *
     * @return array
     */
    protected function createData()
    {
        $token = $this->createSessionID();
        return array('_token' => $token, ':old:' => array(), ':new:' => array());
    }
    /**
     * Generate a new, random session ID.
     *
     * @return string
     */
    protected function createSessionID()
    {
        return Str::random(40);
    }
    /**
     * Determine if the given session is invalid.
     *
     * @param  array  $session
     * @return bool
     */
    protected function isInvalid($session)
    {
        if (!is_array($session)) {
            return true;
        }
        return $this->isExpired($session);
    }
    /**
     * Determine if the given session is expired.
     *
     * @param  array  $session
     * @return bool
     */
    protected function isExpired($session)
    {
        if ($this->lifetime == 0) {
            return false;
        }
        return time() - $session['last_activity'] > $this->lifetime * 60;
    }
    /**
     * Get the full array of session data, including flash data.
     *
     * @return array
     */
    public function all()
    {
        return $this->session['data'];
    }
    /**
     * Determine if the session contains a given item.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return !is_null($this->get($key));
    }
    /**
     * Get the requested item from the session.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $me = $this;
        // First we will check for the value in the general session data and if it
        // is not present in that array we'll check the session flash datas to
        // get the data from there. If netiher is there we give the default.
        $data = $this->session['data'];
        return array_get($data, $key, function () use($me, $key, $default) {
            return $me->getFlash($key, $default);
        });
    }
    /**
     * Get the request item from the flash data.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function getFlash($key, $default = null)
    {
        $data = $this->session['data'];
        // Session flash data is only persisted for the next request into the app
        // which makes it convenient for temporary status messages or various
        // other strings. We'll check all of this flash data for the items.
        if ($value = array_get($data, ":new:.{$key}")) {
            return $value;
        }
        // The "old" flash data are the data flashed during the previous request
        // while the "new" data is the data flashed during the course of this
        // current request. Usually developers will be retrieving the olds.
        if ($value = array_get($data, ":old:.{$key}")) {
            return $value;
        }
        return value($default);
    }
    /**
     * Determine if the old input contains an item.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasOldInput($key)
    {
        return !is_null($this->getOldInput($key));
    }
    /**
     * Get the requested item from the flashed input array.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function getOldInput($key = null, $default = null)
    {
        $input = $this->get('__old_input', array());
        // Input that is flashed to the session can be easily retrieved by the
        // developer, making repopulating old forms and the like much more
        // convenient, since the request's previous input is available.
        if (is_null($key)) {
            return $input;
        }
        return array_get($input, $key, $default);
    }
    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->get('_token');
    }
    /**
     * Put a key / value pair in the session.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function put($key, $value)
    {
        array_set($this->session['data'], $key, $value);
    }
    /**
     * Flash a key / value pair to the session.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function flash($key, $value)
    {
        array_set($this->session['data'][':new:'], $key, $value);
    }
    /**
     * Flash an input array to the session.
     *
     * @param  array  $value
     * @return void
     */
    public function flashInput(array $value)
    {
        return $this->flash('__old_input', $value);
    }
    /**
     * Keep all of the session flash data from expiring.
     *
     * @return void
     */
    public function reflash()
    {
        $old = $this->session['data'][':old:'];
        $new = $this->session['data'][':new:'];
        $this->session['data'][':new:'] = array_merge($new, $old);
    }
    /**
     * Keep a session flash item from expiring.
     *
     * @param  string|array  $keys
     * @return void
     */
    public function keep($keys)
    {
        foreach ((array) $keys as $key) {
            $this->flash($key, $this->get($key));
        }
    }
    /**
     * Remove an item from the session.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        array_forget($this->session['data'], $key);
    }
    /**
     * Remove all of the items from the session.
     *
     * @return void
     */
    public function flush()
    {
        $this->session['data'] = $this->createData();
    }
    /**
     * Generate a new session identifier.
     *
     * @return string
     */
    public function regenerate()
    {
        $this->exists = false;
        return $this->session['id'] = $this->createSessionID();
    }
    /**
     * Finish the session handling for the request.
     *
     * @param  Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $lifetime
     * @return void
     */
    public function finish(Response $response, $lifetime)
    {
        $time = $this->getCurrentTime();
        // First we will set the last activity timestamp on the session and age the
        // session flash data so the old data is gone when subsequent calls into
        // the application are made. Then we'll call the driver store methods.
        $this->session['last_activity'] = $time;
        $id = $this->getSessionID();
        $this->ageFlashData();
        // We'll distinguish between updating and creating sessions since it might
        // matter to the driver. Most drivers will probably be able to use the
        // same code regardless of whether the session is new or not though.
        if ($this->exists) {
            $this->updateSession($id, $this->session, $response);
        } else {
            $this->createSession($id, $this->session, $response);
        }
        // If this driver implements the "Sweeper" interface and hits the sweepers
        // lottery we will sweep sessoins from storage that are expired so the
        // storage spot does not get junked up with expired session storage.
        if ($this instanceof Sweeper and $this->hitsLottery()) {
            $this->sweep($time - $this->lifetime * 60);
        }
    }
    /**
     * Age the session flash data.
     *
     * @return void
     */
    protected function ageFlashData()
    {
        $this->session['data'][':old:'] = $this->session['data'][':new:'];
        $this->session['data'][':new:'] = array();
    }
    /**
     * Get the current timestamp.
     *
     * @return int
     */
    protected function getCurrentTime()
    {
        return time();
    }
    /**
     * Determine if the request hits the sweeper lottery.
     *
     * @return bool
     */
    public function hitsLottery()
    {
        return mt_rand(1, $this->sweep[1]) <= $this->sweep[0];
    }
    /**
     * Write the session cookie to the response.
     *
     * @param  Illuminate\Cookie\CookieJar  $cookie
     * @param  string  $name
     * @param  int  $lifetime
     * @return void
     */
    public function getCookie(CookieJar $cookie, $name, $lifetime)
    {
        return $cookie->make($name, $this->getSessionId(), $lifetime);
    }
    /**
     * Get the session payload.
     *
     * @var array
     */
    public function getSession()
    {
        return $this->session;
    }
    /**
     * Set the entire session payload.
     *
     * @param  array  $session
     * @return void
     */
    public function setSession($session)
    {
        $this->session = $session;
    }
    /**
     * Get the current session ID.
     *
     * @return string
     */
    public function getSessionID()
    {
        if (isset($this->session['id'])) {
            return $this->session['id'];
        }
    }
    /**
     * Get the session's last activity UNIX timestamp.
     *
     * @return int
     */
    public function getLastActivity()
    {
        if (isset($this->session['last_activity'])) {
            return $this->session['last_activity'];
        }
    }
    /**
     * Determine if the session exists in storage.
     *
     * @return bool
     */
    public function sessionExists()
    {
        return $this->exists;
    }
    /**
     * Set the existence of the session.
     *
     * @param  bool  $value
     * @return void
     */
    public function setExists($value)
    {
        $this->exists = $value;
    }
    /**
     * Set the session cookie name.
     *
     * @param  string  $name
     * @return void
     */
    public function setCookieName($name)
    {
        return $this->setCookieOption('name', $name);
    }
    /**
     * Get the given cookie option.
     *
     * @param  string  $option
     * @return mixed
     */
    public function getCookieOption($option)
    {
        return $this->cookie[$option];
    }
    /**
     * Set the given cookie option.
     *
     * @param  string  $option
     * @param  mixed   $value
     * @return void
     */
    public function setCookieOption($option, $value)
    {
        $this->cookie[$option] = $value;
    }
    /**
     * Set the session lifetime.
     *
     * @param  int   $minutes
     * @return void
     */
    public function setLifetime($minutes)
    {
        $this->lifetime = $minutes;
    }
    /**
     * Set the chances of hitting the Sweeper lottery.
     *
     * @param  array  $values
     * @return void
     */
    public function setSweepLottery(array $values)
    {
        $this->sweep = $values;
    }
    /**
     * Determine if the given offset exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }
    /**
     * Get the value at a given offset.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }
    /**
     * Store a value at the given offset.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        return $this->put($key, $value);
    }
    /**
     * Remove the value at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->forget($key);
    }
}
namespace Illuminate\Session;

use Illuminate\Support\Manager;
class SessionManager extends Manager
{
    /**
     * Create an instance of the cookie session driver.
     *
     * @return Illuminate\Session\CookieStore
     */
    protected function createCookieDriver()
    {
        return new CookieStore($this->app['cookie']);
    }
    /**
     * Create an instance of the file session driver.
     *
     * @return Illuminate\Session\FileStore
     */
    protected function createFileDriver()
    {
        $path = $this->app['config']['session.path'];
        return new FileStore($this->app['files'], $path);
    }
    /**
     * Create an instance of the APC session driver.
     *
     * @return Illuminate\Session\CacheDrivenStore
     */
    protected function createApcDriver()
    {
        return $this->createCacheBased('apc');
    }
    /**
     * Create an instance of the Memcached session driver.
     *
     * @return Illuminate\Session\CacheDrivenStore
     */
    protected function createMemcachedDriver()
    {
        return $this->createCacheBased('memcached');
    }
    /**
     * Create an instance of the Wincache session driver.
     *
     * @return Illuminate\Session\CacheDrivenStore
     */
    protected function createWincacheDriver()
    {
        return $this->createCacheBased('wincache');
    }
    /**
     * Create an instance of the Redis session driver.
     *
     * @return Illuminate\Session\CacheDrivenStore
     */
    protected function createRedisDriver()
    {
        return $this->createCacheBased('redis');
    }
    /**
     * Create an instance of the "array" session driver.
     *
     * @return Illuminate\Session\ArrayStore
     */
    protected function createArrayDriver()
    {
        return new ArrayStore($this->app['cache']->driver('array'));
    }
    /**
     * Create an instance of the database session driver.
     *
     * @return Illuminate\Session\DatabaseStore
     */
    protected function createDatabaseDriver()
    {
        $connection = $this->getDatabaseConnection();
        $table = $this->app['config']['session.table'];
        return new DatabaseStore($connection, $this->app['encrypter'], $table);
    }
    /**
     * Get the database connection for the database driver.
     *
     * @return Illuminate\Database\Connection
     */
    protected function getDatabaseConnection()
    {
        $connection = $this->app['config']['session.connection'];
        return $this->app['db']->connection($connection);
    }
    /**
     * Create an instance of a cache driven driver.
     *
     * @return Illuminate\Session\CacheDrivenStore
     */
    protected function createCacheBased($driver)
    {
        return new CacheDrivenStore($this->app['cache']->driver($driver));
    }
    /**
     * Get the default session driver name.
     *
     * @return string
     */
    protected function getDefaultDriver()
    {
        return $this->app['config']['session.driver'];
    }
}
namespace Illuminate\Support;

use Closure;
abstract class Manager
{
    /**
     * The application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;
    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = array();
    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $drivers = array();
    /**
     * Create a new manager instance.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
    /**
     * Get a driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();
        // If the given driver has not been created before, we will create the instances
        // here and cache it so we can return it next time very quickly. If their is
        // already a driver created by this name, we'll just return that instance.
        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }
        return $this->drivers[$driver];
    }
    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function createDriver($driver)
    {
        $method = 'create' . ucfirst($driver) . 'Driver';
        // We'll check to see if a creator method exists for the given driver. If not we
        // will check for a custom driver creator, which allows developers to create
        // drivers using their own customized driver creator Closure to create it.
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        } elseif (method_exists($this, $method)) {
            return $this->{$method}();
        }
        throw new \InvalidArgumentException("Driver [{$driver}] not supported.");
    }
    /**
     * Call a custom driver creator.
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function callCustomCreator($driver)
    {
        return $this->customCreators[$driver]($this->app);
    }
    /**
     * Register a custom driver creator Closure.
     *
     * @param  string   $driver
     * @param  Closure  $callback
     * @return void
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;
    }
    /**
     * Get all of the created "drivers".
     *
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }
    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->driver(), $method), $parameters);
    }
}
namespace Illuminate\Session;

use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Response;
class CookieStore extends Store
{
    /**
     * The Illuminate cookie creator.
     *
     * @var Illuminate\CookieJar
     */
    protected $cookies;
    /**
     * The name of the session payload cookie.
     *
     * @var string
     */
    protected $payload = 'illuminate_payload';
    /**
     * Create a new Cookie based session store.
     *
     * @param  Illuminate\CookieJar  $cookies
     * @return void
     */
    public function __construct(CookieJar $cookies)
    {
        $this->cookies = $cookies;
    }
    /**
     * Retrieve a session payload from storage.
     *
     * @param  string  $id
     * @return array|null
     */
    public function retrieveSession($id)
    {
        return unserialize($this->cookies->get($this->payload));
    }
    /**
     * Create a new session in storage.
     *
     * @param  string  $id
     * @param  array   $session
     * @param  Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function createSession($id, array $session, Response $response)
    {
        $value = serialize($session);
        $response->headers->setCookie($this->cookies->make($this->payload, $value));
    }
    /**
     * Update an existing session in storage.
     *
     * @param  string  $id
     * @param  array   $session
     * @param  Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function updateSession($id, array $session, Response $response)
    {
        return $this->createSession($id, $session, $response);
    }
    /**
     * Set the name of the session payload cookie.
     *
     * @param  string  $name
     * @return void
     */
    public function setPayloadName($name)
    {
        $this->payload = $name;
    }
    /**
     * Get the cookie jar instance.
     *
     * @return Illuminate\CookieJar
     */
    public function getCookieJar()
    {
        return $this->cookies;
    }
}
namespace Illuminate\Cookie;

use Closure;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
class CookieJar
{
    /*
     * The current request instance.
     *
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request;
    /**
     * The encrypter instance.
     *
     * @var Illuminate\Encryption\Encrypter
     */
    protected $encrypter;
    /**
     * The default cookie options.
     *
     * @var array
     */
    protected $defaults = array();
    /**
     * Create a new cookie manager instance.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @param  Illuminate\Encryption\Encrypter  $encrypter
     * @param  array   $defaults
     * @return void
     */
    public function __construct(Request $request, Encrypter $encrypter, array $defaults)
    {
        $this->request = $request;
        $this->defaults = $defaults;
        $this->encrypter = $encrypter;
    }
    /**
     * Determine if a cookie exists and is not null.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return !is_null($this->get($key));
    }
    /**
     * Get the value of the given cookie.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $value = $this->request->cookies->get($key);
        if (!is_null($value)) {
            return $this->decrypt($value);
        }
        return $default instanceof Closure ? $default() : $default;
    }
    /**
     * Decrypt the given cookie value.
     *
     * @param  string      $value
     * @return mixed|null
     */
    protected function decrypt($value)
    {
        try {
            return $this->encrypter->decrypt($value);
        } catch (\Exception $e) {
            return null;
        }
    }
    /**
     * Create a new cookie instance.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  int     $minutes
     * @return Symfony\Component\HttpFoundation\Cookie
     */
    public function make($name, $value, $minutes = 0)
    {
        extract($this->defaults);
        // Once we calculate the time we can encrypt the message. All cookies will be
        // encrypted using the Illuminate encryption component and will have a MAC
        // assigned to them by the encrypter to make sure they remain authentic.
        $time = $minutes == 0 ? 0 : time() + $minutes * 60;
        $value = $this->encrypter->encrypt($value);
        return new Cookie($name, $value, $time, $path, $domain, $secure, $httpOnly);
    }
    /**
     * Create a cookie that lasts "forever" (five years).
     *
     * @param  string  $name
     * @param  string  $value
     * @return Symfony\Component\HttpFoundation\Cookie
     */
    public function forever($name, $value)
    {
        return $this->make($name, $value, 2628000);
    }
    /**
     * Expire the given cookie.
     *
     * @param  string  $name
     * @return Symfony\Component\HttpFoundation\Cookie
     */
    public function forget($name)
    {
        return $this->make($name, null, -2628000);
    }
    /**
     * Set the value of a cookie otpion.
     *
     * @param  string  $option
     * @param  string  $value
     * @return void
     */
    public function setDefault($option, $value)
    {
        $this->defaults[$option] = $value;
    }
    /**
     * Get the request instance.
     *
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * Get the encrypter instance.
     *
     * @return Illuminate\Encrypter
     */
    public function getEncrypter()
    {
        return $this->encrypter;
    }
}
namespace Illuminate\Encryption;

class DecryptException extends \RuntimeException
{
    
}
class Encrypter
{
    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;
    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher = 'rijndael-256';
    /**
     * The mode used for encrpytion.
     *
     * @var string
     */
    protected $mode = 'ctr';
    /**
     * The block size of the cipher.
     *
     * @var int
     */
    protected $block = 32;
    /**
     * Create a new encrypter instance.
     *
     * @param  string  $key
     * @return void
     */
    public function __construct($key)
    {
        $this->key = $key;
    }
    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    public function encrypt($value)
    {
        $iv = mcrypt_create_iv($this->getIvSize(), $this->getRandomizer());
        $value = base64_encode($this->padAndMcrypt($value, $iv));
        // Once we have the encrypted value we will go ahead base64_encode the input
        // vector and create the MAC for the encrypted value so we can verify its
        // authenticity. Then, we'll JSON encode the data in a "payload" array.
        $iv = base64_encode($iv);
        $mac = $this->hash($value);
        return base64_encode(json_encode(compact('iv', 'value', 'mac')));
    }
    /**
     * Padd and use mcrypt on the given value and input vector.
     *
     * @param  string  $value
     * @param  string  $iv
     * @return string
     */
    protected function padAndMcrypt($value, $iv)
    {
        $value = $this->addPadding(serialize($value));
        return mcrypt_encrypt($this->cipher, $this->key, $value, $this->mode, $iv);
    }
    /**
     * Decrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    public function decrypt($payload)
    {
        $payload = $this->getJsonPayload($payload);
        // We'll go ahead and remove the PKCS7 padding from the encrypted value before
        // we decrypt it. Once we have the de-padded value, we will grab the vector
        // and decrypt the data, passing back the unserialized from of the value.
        $value = $this->stripPadding(base64_decode($payload['value']));
        $iv = base64_decode($payload['iv']);
        return unserialize(rtrim($this->mcryptDecrypt($value, $iv)));
    }
    /**
     * Run the mcrypt decryption routine for the value.
     *
     * @param  string  $value
     * @param  string  $iv
     * @return string
     */
    protected function mcryptDecrypt($value, $iv)
    {
        return mcrypt_decrypt($this->cipher, $this->key, $value, $this->mode, $iv);
    }
    /**
     * Get the JSON array from the given payload.
     *
     * @param  string  $payload
     * @return array
     */
    protected function getJsonPayload($payload)
    {
        $payload = json_decode(base64_decode($payload), true);
        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encrypion.
        if (!$payload or $this->invalidPayload($payload)) {
            throw new DecryptException('Invalid data passed to encrypter.');
        }
        if ($payload['mac'] != $this->hash($payload['value'])) {
            throw new DecryptException('Message authentication code invalid.');
        }
        return $payload;
    }
    /**
     * Create a MAC for the given value.
     *
     * @param  string  $value
     * @return string  
     */
    protected function hash($value)
    {
        return hash_hmac('sha256', $value, $this->key);
    }
    /**
     * Add PKCS7 padding to a given value.
     *
     * @param  string  $value
     * @return string
     */
    protected function addPadding($value)
    {
        $pad = $this->block - strlen($value) % $this->block;
        return $value . str_repeat(chr($pad), $pad);
    }
    /**
     * Remove the padding from the given value.
     *
     * @param  string  $value
     * @return string
     */
    protected function stripPadding($value)
    {
        $pad = ord($value[($len = strlen($value)) - 1]);
        return $this->paddingIsValid($pad, $value) ? substr($value, 0, -$pad) : $value;
    }
    /**
     * Determine if the given padding for a value is valid.
     *
     * @param  string  $pad
     * @param  string  $value
     * @return bool
     */
    protected function paddingIsValid($pad, $value)
    {
        return $pad and $pad < $this->block and preg_match('/' . chr($pad) . '{' . $pad . '}$/', $value);
    }
    /**
     * Verify that the encryption payload is valid.
     *
     * @param  array  $data
     * @return bool
     */
    protected function invalidPayload(array $data)
    {
        return !isset($data['iv']) or !isset($data['value']) or !isset($data['mac']);
    }
    /**
     * Get the IV size for the cipher.
     *
     * @return int
     */
    protected function getIvSize()
    {
        return mcrypt_get_iv_size($this->cipher, $this->mode);
    }
    /**
     * Get the random data source available for the OS.
     *
     * @return int
     */
    protected function getRandomizer()
    {
        if (defined('MCRYPT_DEV_URANDOM')) {
            return MCRYPT_DEV_URANDOM;
        }
        if (defined('MCRYPT_DEV_RANDOM')) {
            return MCRYPT_DEV_RANDOM;
        }
        mt_srand();
        return MCRYPT_RAND;
    }
}
namespace Illuminate\Support\Facades;

class Log extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'log';
    }
}
namespace Illuminate\Log;

use Illuminate\Support\ServiceProvider;
class LogServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $logger = new Writer(new \Monolog\Logger('log'));
        $this->app->instance('log', $logger);
        // If the setup Closure has been bound in the container, we will resolve it
        // and pass in the logger instance. This allows this to defer all of the
        // logger class setup until the last possible second, improving speed.
        if (isset($this->app['log.setup'])) {
            call_user_func($this->app['log.setup'], $logger);
        }
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('log');
    }
}
namespace Illuminate\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
class Writer
{
    /**
     * The Monolog logger instance.
     *
     * @var Monolog\Logger
     */
    protected $monolog;
    /**
     * All of the error levels.
     *
     * @var array
     */
    protected $levels = array('debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency');
    /**
     * Create a new log writer instance.
     *
     * @param  Monolog\Logger  $monolog
     * @return void
     */
    public function __construct(MonologLogger $monolog)
    {
        $this->monolog = $monolog;
    }
    /**
     * Register a file log handler.
     *
     * @param  string  $path
     * @param  string  $level
     * @return void
     */
    public function useFiles($path, $level = 'debug')
    {
        $level = $this->parseLevel($level);
        $this->monolog->pushHandler(new StreamHandler($path, $level));
    }
    /**
     * Register a daily file log handler.
     *
     * @param  string  $path
     * @param  int     $days
     * @param  string  $level
     * @return void
     */
    public function useDailyFiles($path, $days = 0, $level = 'debug')
    {
        $level = $this->parseLevel($level);
        $this->monolog->pushHandler(new RotatingFileHandler($path, $days, $level));
    }
    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string  $level
     * @return int
     */
    protected function parseLevel($level)
    {
        switch ($level) {
            case 'debug':
                return MonologLogger::DEBUG;
            case 'info':
                return MonologLogger::INFO;
            case 'notice':
                return MonologLogger::NOTICE;
            case 'warning':
                return MonologLogger::WARNING;
            case 'error':
                return MonologLogger::ERROR;
            case 'critical':
                return MonologLogger::CRITICAL;
            case 'alert':
                return MonologLogger::ALERT;
            case 'emergency':
                return MonologLogger::EMERGENCY;
            default:
                throw new \InvalidArgumentException('Invalid log level.');
        }
    }
    /**
     * Get the underlying Monolog instance.
     *
     * @return Monolog\Logger
     */
    public function getMonolog()
    {
        return $this->monolog;
    }
    /**
     * Dynamically handle error additions.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->levels)) {
            $method = 'add' . ucfirst($method);
            return call_user_func_array(array($this->monolog, $method), $parameters);
        }
        throw new \BadMethodCallException("Method [{$method}] does not exist.");
    }
}
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;
/**
 * Monolog log channel
 *
 * It contains a stack of Handlers and a stack of Processors,
 * and uses them to store records that are added to it.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Logger implements LoggerInterface
{
    /**
     * Detailed debug information
     */
    const DEBUG = 100;
    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 200;
    /**
     * Uncommon events
     */
    const NOTICE = 250;
    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 300;
    /**
     * Runtime errors
     */
    const ERROR = 400;
    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 500;
    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 550;
    /**
     * Urgent alert.
     */
    const EMERGENCY = 600;
    protected static $levels = array(100 => 'DEBUG', 200 => 'INFO', 250 => 'NOTICE', 300 => 'WARNING', 400 => 'ERROR', 500 => 'CRITICAL', 550 => 'ALERT', 600 => 'EMERGENCY');
    /**
     * @var DateTimeZone
     */
    protected static $timezone;
    protected $name;
    /**
     * The handler stack
     *
     * @var array of Monolog\Handler\HandlerInterface
     */
    protected $handlers = array();
    protected $processors = array();
    /**
     * @param string $name The logging channel
     */
    public function __construct($name)
    {
        $this->name = $name;
        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Pushes a handler on to the stack.
     *
     * @param HandlerInterface $handler
     */
    public function pushHandler(HandlerInterface $handler)
    {
        array_unshift($this->handlers, $handler);
    }
    /**
     * Pops a handler from the stack
     *
     * @return HandlerInterface
     */
    public function popHandler()
    {
        if (!$this->handlers) {
            throw new \LogicException('You tried to pop from an empty handler stack.');
        }
        return array_shift($this->handlers);
    }
    /**
     * Adds a processor on to the stack.
     *
     * @param callable $callback
     */
    public function pushProcessor($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method), ' . var_export($callback, true) . ' given');
        }
        array_unshift($this->processors, $callback);
    }
    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @return callable
     */
    public function popProcessor()
    {
        if (!$this->processors) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }
        return array_shift($this->processors);
    }
    /**
     * Adds a log record.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (!$this->handlers) {
            $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
        }
        $record = array('message' => (string) $message, 'context' => $context, 'level' => $level, 'level_name' => static::getLevelName($level), 'channel' => $this->name, 'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone)->setTimezone(static::$timezone), 'extra' => array());
        // check if any handler will handle this message
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling($record)) {
                $handlerKey = $key;
                break;
            }
        }
        // none found
        if (null === $handlerKey) {
            return false;
        }
        // found at least one, process message and dispatch it
        foreach ($this->processors as $processor) {
            $record = call_user_func($processor, $record);
        }
        while (isset($this->handlers[$handlerKey]) && false === $this->handlers[$handlerKey]->handle($record)) {
            $handlerKey++;
        }
        return true;
    }
    /**
     * Adds a log record at the DEBUG level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addDebug($message, array $context = array())
    {
        return $this->addRecord(static::DEBUG, $message, $context);
    }
    /**
     * Adds a log record at the INFO level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addInfo($message, array $context = array())
    {
        return $this->addRecord(static::INFO, $message, $context);
    }
    /**
     * Adds a log record at the NOTICE level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addNotice($message, array $context = array())
    {
        return $this->addRecord(static::NOTICE, $message, $context);
    }
    /**
     * Adds a log record at the WARNING level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addWarning($message, array $context = array())
    {
        return $this->addRecord(static::WARNING, $message, $context);
    }
    /**
     * Adds a log record at the ERROR level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addError($message, array $context = array())
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }
    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addCritical($message, array $context = array())
    {
        return $this->addRecord(static::CRITICAL, $message, $context);
    }
    /**
     * Adds a log record at the ALERT level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addAlert($message, array $context = array())
    {
        return $this->addRecord(static::ALERT, $message, $context);
    }
    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addEmergency($message, array $context = array())
    {
        return $this->addRecord(static::EMERGENCY, $message, $context);
    }
    /**
     * Gets the name of the logging level.
     *
     * @param  integer $level
     * @return string
     */
    public static function getLevelName($level)
    {
        if (!isset(static::$levels[$level])) {
            throw new InvalidArgumentException('Level "' . $level . '" is not defined, use one of: ' . implode(', ', array_keys(static::$levels)));
        }
        return static::$levels[$level];
    }
    /**
     * Checks whether the Logger has a handler that listens on the given level
     *
     * @param  integer $level
     * @return Boolean
     */
    public function isHandling($level)
    {
        $record = array('level' => $level);
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling($record)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function log($level, $message, array $context = array())
    {
        if (is_string($level) && defined(__CLASS__ . '::' . strtoupper($level))) {
            $level = constant(__CLASS__ . '::' . strtoupper($level));
        }
        return $this->addRecord($level, $message, $context);
    }
    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function debug($message, array $context = array())
    {
        return $this->addRecord(static::DEBUG, $message, $context);
    }
    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function info($message, array $context = array())
    {
        return $this->addRecord(static::INFO, $message, $context);
    }
    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function notice($message, array $context = array())
    {
        return $this->addRecord(static::NOTICE, $message, $context);
    }
    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warn($message, array $context = array())
    {
        return $this->addRecord(static::WARNING, $message, $context);
    }
    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warning($message, array $context = array())
    {
        return $this->addRecord(static::WARNING, $message, $context);
    }
    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function err($message, array $context = array())
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }
    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function error($message, array $context = array())
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }
    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function crit($message, array $context = array())
    {
        return $this->addRecord(static::CRITICAL, $message, $context);
    }
    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function critical($message, array $context = array())
    {
        return $this->addRecord(static::CRITICAL, $message, $context);
    }
    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function alert($message, array $context = array())
    {
        return $this->addRecord(static::ALERT, $message, $context);
    }
    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emerg($message, array $context = array())
    {
        return $this->addRecord(static::EMERGENCY, $message, $context);
    }
    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emergency($message, array $context = array())
    {
        return $this->addRecord(static::EMERGENCY, $message, $context);
    }
}
namespace Psr\Log;

/**
 * Describes a logger instance
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo
 * will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data, the only assumption that
 * can be made by implementors is that if an Exception instance is given
 * to produce a stack trace, it MUST be in a key named "exception".
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function emergency($message, array $context = array());
    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function alert($message, array $context = array());
    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function critical($message, array $context = array());
    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function error($message, array $context = array());
    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function warning($message, array $context = array());
    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function notice($message, array $context = array());
    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function info($message, array $context = array());
    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function debug($message, array $context = array());
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array());
}
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monolog\Handler;

use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
/**
 * Base Handler class providing the Handler structure
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
abstract class AbstractHandler implements HandlerInterface
{
    protected $level = Logger::DEBUG;
    protected $bubble = false;
    /**
     * @var FormatterInterface
     */
    protected $formatter;
    protected $processors = array();
    /**
     * @param integer $level  The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        $this->level = $level;
        $this->bubble = $bubble;
    }
    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return $record['level'] >= $this->level;
    }
    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records)
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }
    /**
     * Closes the handler.
     *
     * This will be called automatically when the object is destroyed
     */
    public function close()
    {
        
    }
    /**
     * {@inheritdoc}
     */
    public function pushProcessor($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method), ' . var_export($callback, true) . ' given');
        }
        array_unshift($this->processors, $callback);
    }
    /**
     * {@inheritdoc}
     */
    public function popProcessor()
    {
        if (!$this->processors) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }
        return array_shift($this->processors);
    }
    /**
     * {@inheritdoc}
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }
    /**
     * {@inheritdoc}
     */
    public function getFormatter()
    {
        if (!$this->formatter) {
            $this->formatter = $this->getDefaultFormatter();
        }
        return $this->formatter;
    }
    /**
     * Sets minimum logging level at which this handler will be triggered.
     *
     * @param integer $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
    /**
     * Gets minimum logging level at which this handler will be triggered.
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }
    /**
     * Sets the bubbling behavior.
     *
     * @param Boolean $bubble True means that bubbling is not permitted.
     *                        False means that this handler allows bubbling.
     */
    public function setBubble($bubble)
    {
        $this->bubble = $bubble;
    }
    /**
     * Gets the bubbling behavior.
     *
     * @return Boolean True means that bubbling is not permitted.
     *                 False means that this handler allows bubbling.
     */
    public function getBubble()
    {
        return $this->bubble;
    }
    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Exception $e) {
            
        }
    }
    /**
     * Gets the default formatter.
     *
     * @return FormatterInterface
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }
}
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monolog\Handler;

/**
 * Base Handler class providing the Handler structure
 *
 * Classes extending it should (in most cases) only implement write($record)
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Christophe Coevoet <stof@notk.org>
 */
abstract class AbstractProcessingHandler extends AbstractHandler
{
    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if ($record['level'] < $this->level) {
            return false;
        }
        $record = $this->processRecord($record);
        $record['formatted'] = $this->getFormatter()->format($record);
        $this->write($record);
        return false === $this->bubble;
    }
    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected abstract function write(array $record);
    /**
     * Processes a record.
     *
     * @param  array $record
     * @return array
     */
    protected function processRecord(array $record)
    {
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }
        return $record;
    }
}
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monolog\Handler;

use Monolog\Logger;
/**
 * Stores to any stream resource
 *
 * Can be used to store into php://stderr, remote and local files, etc.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class StreamHandler extends AbstractProcessingHandler
{
    protected $stream;
    protected $url;
    /**
     * @param string  $stream
     * @param integer $level  The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($stream, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->url = $stream;
        }
    }
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }
    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (null === $this->stream) {
            if (!$this->url) {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $errorMessage = null;
            set_error_handler(function ($code, $msg) use(&$errorMessage) {
                $errorMessage = preg_replace('{^fopen\\(.*?\\): }', '', $msg);
            });
            $this->stream = fopen($this->url, 'a');
            restore_error_handler();
            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $errorMessage, $this->url));
            }
        }
        fwrite($this->stream, (string) $record['formatted']);
    }
}
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monolog\Handler;

use Monolog\Logger;
/**
 * Stores logs to files that are rotated every day and a limited number of files are kept.
 *
 * This rotation is only intended to be used as a workaround. Using logrotate to
 * handle the rotation is strongly encouraged when you can use it.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class RotatingFileHandler extends StreamHandler
{
    protected $filename;
    protected $maxFiles;
    protected $mustRotate;
    /**
     * @param string  $filename
     * @param integer $maxFiles The maximal amount of files to keep (0 means unlimited)
     * @param integer $level    The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble   Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($filename, $maxFiles = 0, $level = Logger::DEBUG, $bubble = true)
    {
        $this->filename = $filename;
        $this->maxFiles = (int) $maxFiles;
        $fileInfo = pathinfo($this->filename);
        $timedFilename = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '-' . date('Y-m-d');
        if (!empty($fileInfo['extension'])) {
            $timedFilename .= '.' . $fileInfo['extension'];
        }
        // disable rotation upfront if files are unlimited
        if (0 === $this->maxFiles) {
            $this->mustRotate = false;
        }
        parent::__construct($timedFilename, $level, $bubble);
    }
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        parent::close();
        if (true === $this->mustRotate) {
            $this->rotate();
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        // on the first record written, if the log is new, we should rotate (once per day)
        if (null === $this->mustRotate) {
            $this->mustRotate = !file_exists($this->url);
        }
        parent::write($record);
    }
    /**
     * Rotates the files.
     */
    protected function rotate()
    {
        $fileInfo = pathinfo($this->filename);
        $glob = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '-*';
        if (!empty($fileInfo['extension'])) {
            $glob .= '.' . $fileInfo['extension'];
        }
        $iterator = new \GlobIterator($glob);
        $count = $iterator->count();
        if ($this->maxFiles >= $count) {
            // no files to remove
            return;
        }
        // Sorting the files by name to remove the older ones
        $array = iterator_to_array($iterator);
        usort($array, function ($a, $b) {
            return strcmp($b->getFilename(), $a->getFilename());
        });
        foreach (array_slice($array, $this->maxFiles) as $file) {
            if ($file->isWritable()) {
                unlink($file->getRealPath());
            }
        }
    }
}
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
/**
 * Interface that all Monolog Handlers must implement
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
interface HandlerInterface
{
    /**
     * Checks whether the given record will be handled by this handler.
     *
     * This is mostly done for performance reasons, to avoid calling processors for nothing.
     *
     * Handlers should still check the record levels within handle(), returning false in isHandling()
     * is no guarantee that handle() will not be called, and isHandling() might not be called
     * for a given record.
     *
     * @param array $record
     *
     * @return Boolean
     */
    public function isHandling(array $record);
    /**
     * Handles a record.
     *
     * All records may be passed to this method, and the handler should discard
     * those that it does not want to handle.
     *
     * The return value of this function controls the bubbling process of the handler stack.
     * Unless the bubbling is interrupted (by returning true), the Logger class will keep on
     * calling further handlers in the stack with a given log record.
     *
     * @param  array   $record The record to handle
     * @return Boolean True means that this handler handled the record, and that bubbling is not permitted.
     *                 False means the record was either not processed or that this handler allows bubbling.
     */
    public function handle(array $record);
    /**
     * Handles a set of records at once.
     *
     * @param array $records The records to handle (an array of record arrays)
     */
    public function handleBatch(array $records);
    /**
     * Adds a processor in the stack.
     *
     * @param callable $callback
     */
    public function pushProcessor($callback);
    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @return callable
     */
    public function popProcessor();
    /**
     * Sets the formatter.
     *
     * @param FormatterInterface $formatter
     */
    public function setFormatter(FormatterInterface $formatter);
    /**
     * Gets the formatter.
     *
     * @return FormatterInterface
     */
    public function getFormatter();
}
namespace Illuminate\Support\Facades;

class App extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return static::$app;
    }
}
namespace Illuminate\Exception;

use Closure;
use ReflectionFunction;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
class Handler
{
    /**
     * All of the register exception handlers.
     *
     * @var array
     */
    protected $handlers = array();
    /**
     * Handle a console exception.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function handleConsole($exception)
    {
        return $this->handle($exception, true);
    }
    /**
     * Handle the given exception.
     *
     * @param  Exception  $exception
     * @param  bool  $fromConsole
     * @return void
     */
    public function handle($exception, $fromConsole = false)
    {
        foreach ($this->handlers as $handler) {
            // If this exception handler does not handle the given exception, we will
            // just go the next one. A Handler may type-hint the exception that it
            // will handle, allowing for more granularity on the error handling.
            if (!$this->handlesException($handler, $exception)) {
                continue;
            }
            if ($exception instanceof HttpExceptionInterface) {
                $code = $exception->getStatusCode();
            } else {
                $code = 500;
            }
            $response = $handler($exception, $code, $fromConsole);
            // If the handler returns a "non-null" response, we will return it so it
            // will get sent back to the browsers. Once a handler returns a valid
            // response we will cease iterating and calling the other handlers.
            if (!is_null($response)) {
                return $response;
            }
        }
    }
    /**
     * Determine if the given handler handles this exception.
     *
     * @param  Closure    $handler
     * @param  Exception  $exception
     * @return bool
     */
    protected function handlesException(Closure $handler, $exception)
    {
        $reflection = new ReflectionFunction($handler);
        return $reflection->getNumberOfParameters() == 0 or $this->hints($reflection, $exception);
    }
    /**
     * Determine if the given handler type hints the exception.
     *
     * @param  ReflectionFunction  $reflection
     * @param  Exception  $exception
     * @return bool
     */
    protected function hints(ReflectionFunction $reflection, $exception)
    {
        $parameters = $reflection->getParameters();
        $expected = $parameters[0];
        return !$expected->getClass() or $expected->getClass()->isInstance($exception);
    }
    /**
     * Register an application error handler.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        array_unshift($this->handlers, $callback);
    }
}
namespace Illuminate\Support\Facades;

class Route extends Facade
{
    /**
     * Register a new filter with the application.
     *
     * @param  string   $name
     * @param  Closure|string  $callback
     * @return void
     */
    public static function filter($name, $callback)
    {
        return static::$app['router']->addFilter($name, $callback);
    }
    /**
     * Tie a registered middleware to a URI pattern.
     *
     * @param  string  $pattern
     * @param  string|array  $name
     * @return void
     */
    public static function when($pattern, $name)
    {
        return static::$app['router']->matchFilter($pattern, $name);
    }
    /**
     * Determine if the current route matches a given name.
     *
     * @param  string  $name
     * @return bool
     */
    public static function is($name)
    {
        return static::$app['router']->currentRouteNamed($name);
    }
    /**
     * Determine if the current route uses a given controller action.
     *
     * @param  string  $action
     * @return bool
     */
    public static function uses($action)
    {
        return static::$app['router']->currentRouteUses($action);
    }
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing;

/**
 * A Route describes a route and its parameters.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 *
 * @api
 */
class Route implements \Serializable
{
    /**
     * @var string
     */
    private $path = '/';
    /**
     * @var string
     */
    private $host = '';
    /**
     * @var array
     */
    private $schemes = array();
    /**
     * @var array
     */
    private $methods = array();
    /**
     * @var array
     */
    private $defaults = array();
    /**
     * @var array
     */
    private $requirements = array();
    /**
     * @var array
     */
    private $options = array();
    /**
     * @var null|RouteCompiler
     */
    private $compiled;
    /**
     * Constructor.
     *
     * Available options:
     *
     *  * compiler_class: A class name able to compile this route instance (RouteCompiler by default)
     *
     * @param string       $path         The path pattern to match
     * @param array        $defaults     An array of default parameter values
     * @param array        $requirements An array of requirements for parameters (regexes)
     * @param array        $options      An array of options
     * @param string       $host         The host pattern to match
     * @param string|array $schemes      A required URI scheme or an array of restricted schemes
     * @param string|array $methods      A required HTTP method or an array of restricted methods
     *
     * @api
     */
    public function __construct($path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array())
    {
        $this->setPath($path);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);
        $this->setOptions($options);
        $this->setHost($host);
        // The conditions make sure that an initial empty $schemes/$methods does not override the corresponding requirement.
        // They can be removed when the BC layer is removed.
        if ($schemes) {
            $this->setSchemes($schemes);
        }
        if ($methods) {
            $this->setMethods($methods);
        }
    }
    public function serialize()
    {
        return serialize(array('path' => $this->path, 'host' => $this->host, 'defaults' => $this->defaults, 'requirements' => $this->requirements, 'options' => $this->options, 'schemes' => $this->schemes, 'methods' => $this->methods));
    }
    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->path = $data['path'];
        $this->host = $data['host'];
        $this->defaults = $data['defaults'];
        $this->requirements = $data['requirements'];
        $this->options = $data['options'];
        $this->schemes = $data['schemes'];
        $this->methods = $data['methods'];
    }
    /**
     * Returns the pattern for the path.
     *
     * @return string The pattern
     *
     * @deprecated Deprecated in 2.2, to be removed in 3.0. Use getPath instead.
     */
    public function getPattern()
    {
        return $this->path;
    }
    /**
     * Sets the pattern for the path.
     *
     * This method implements a fluent interface.
     *
     * @param string $pattern The path pattern
     *
     * @return Route The current Route instance
     *
     * @deprecated Deprecated in 2.2, to be removed in 3.0. Use setPath instead.
     */
    public function setPattern($pattern)
    {
        return $this->setPath($pattern);
    }
    /**
     * Returns the pattern for the path.
     *
     * @return string The path pattern
     */
    public function getPath()
    {
        return $this->path;
    }
    /**
     * Sets the pattern for the path.
     *
     * This method implements a fluent interface.
     *
     * @param string $pattern The path pattern
     *
     * @return Route The current Route instance
     */
    public function setPath($pattern)
    {
        // A pattern must start with a slash and must not have multiple slashes at the beginning because the
        // generated path for this route would be confused with a network path, e.g. '//domain.com/path'.
        $this->path = '/' . ltrim(trim($pattern), '/');
        $this->compiled = null;
        return $this;
    }
    /**
     * Returns the pattern for the host.
     *
     * @return string The host pattern
     */
    public function getHost()
    {
        return $this->host;
    }
    /**
     * Sets the pattern for the host.
     *
     * This method implements a fluent interface.
     *
     * @param string $pattern The host pattern
     *
     * @return Route The current Route instance
     */
    public function setHost($pattern)
    {
        $this->host = (string) $pattern;
        $this->compiled = null;
        return $this;
    }
    /**
     * Returns the lowercased schemes this route is restricted to.
     * So an empty array means that any scheme is allowed.
     *
     * @return array The schemes
     */
    public function getSchemes()
    {
        return $this->schemes;
    }
    /**
     * Sets the schemes (e.g. 'https') this route is restricted to.
     * So an empty array means that any scheme is allowed.
     *
     * This method implements a fluent interface.
     *
     * @param string|array $schemes The scheme or an array of schemes
     *
     * @return Route The current Route instance
     */
    public function setSchemes($schemes)
    {
        $this->schemes = array_map('strtolower', (array) $schemes);
        // this is to keep BC and will be removed in a future version
        if ($this->schemes) {
            $this->requirements['_scheme'] = implode('|', $this->schemes);
        } else {
            unset($this->requirements['_scheme']);
        }
        $this->compiled = null;
        return $this;
    }
    /**
     * Returns the uppercased HTTP methods this route is restricted to.
     * So an empty array means that any method is allowed.
     *
     * @return array The schemes
     */
    public function getMethods()
    {
        return $this->methods;
    }
    /**
     * Sets the HTTP methods (e.g. 'POST') this route is restricted to.
     * So an empty array means that any method is allowed.
     *
     * This method implements a fluent interface.
     *
     * @param string|array $methods The method or an array of methods
     *
     * @return Route The current Route instance
     */
    public function setMethods($methods)
    {
        $this->methods = array_map('strtoupper', (array) $methods);
        // this is to keep BC and will be removed in a future version
        if ($this->methods) {
            $this->requirements['_method'] = implode('|', $this->methods);
        } else {
            unset($this->requirements['_method']);
        }
        $this->compiled = null;
        return $this;
    }
    /**
     * Returns the options.
     *
     * @return array The options
     */
    public function getOptions()
    {
        return $this->options;
    }
    /**
     * Sets the options.
     *
     * This method implements a fluent interface.
     *
     * @param array $options The options
     *
     * @return Route The current Route instance
     */
    public function setOptions(array $options)
    {
        $this->options = array('compiler_class' => 'Symfony\\Component\\Routing\\RouteCompiler');
        return $this->addOptions($options);
    }
    /**
     * Adds options.
     *
     * This method implements a fluent interface.
     *
     * @param array $options The options
     *
     * @return Route The current Route instance
     */
    public function addOptions(array $options)
    {
        foreach ($options as $name => $option) {
            $this->options[$name] = $option;
        }
        $this->compiled = null;
        return $this;
    }
    /**
     * Sets an option value.
     *
     * This method implements a fluent interface.
     *
     * @param string $name  An option name
     * @param mixed  $value The option value
     *
     * @return Route The current Route instance
     *
     * @api
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        $this->compiled = null;
        return $this;
    }
    /**
     * Get an option value.
     *
     * @param string $name An option name
     *
     * @return mixed The option value or null when not given
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
    /**
     * Checks if a an option has been set
     *
     * @param string $name An option name
     *
     * @return Boolean true if the option is set, false otherwise
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }
    /**
     * Returns the defaults.
     *
     * @return array The defaults
     */
    public function getDefaults()
    {
        return $this->defaults;
    }
    /**
     * Sets the defaults.
     *
     * This method implements a fluent interface.
     *
     * @param array $defaults The defaults
     *
     * @return Route The current Route instance
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = array();
        return $this->addDefaults($defaults);
    }
    /**
     * Adds defaults.
     *
     * This method implements a fluent interface.
     *
     * @param array $defaults The defaults
     *
     * @return Route The current Route instance
     */
    public function addDefaults(array $defaults)
    {
        foreach ($defaults as $name => $default) {
            $this->defaults[$name] = $default;
        }
        $this->compiled = null;
        return $this;
    }
    /**
     * Gets a default value.
     *
     * @param string $name A variable name
     *
     * @return mixed The default value or null when not given
     */
    public function getDefault($name)
    {
        return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
    }
    /**
     * Checks if a default value is set for the given variable.
     *
     * @param string $name A variable name
     *
     * @return Boolean true if the default value is set, false otherwise
     */
    public function hasDefault($name)
    {
        return array_key_exists($name, $this->defaults);
    }
    /**
     * Sets a default value.
     *
     * @param string $name    A variable name
     * @param mixed  $default The default value
     *
     * @return Route The current Route instance
     *
     * @api
     */
    public function setDefault($name, $default)
    {
        $this->defaults[$name] = $default;
        $this->compiled = null;
        return $this;
    }
    /**
     * Returns the requirements.
     *
     * @return array The requirements
     */
    public function getRequirements()
    {
        return $this->requirements;
    }
    /**
     * Sets the requirements.
     *
     * This method implements a fluent interface.
     *
     * @param array $requirements The requirements
     *
     * @return Route The current Route instance
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = array();
        return $this->addRequirements($requirements);
    }
    /**
     * Adds requirements.
     *
     * This method implements a fluent interface.
     *
     * @param array $requirements The requirements
     *
     * @return Route The current Route instance
     */
    public function addRequirements(array $requirements)
    {
        foreach ($requirements as $key => $regex) {
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        }
        $this->compiled = null;
        return $this;
    }
    /**
     * Returns the requirement for the given key.
     *
     * @param string $key The key
     *
     * @return string|null The regex or null when not given
     */
    public function getRequirement($key)
    {
        return isset($this->requirements[$key]) ? $this->requirements[$key] : null;
    }
    /**
     * Checks if a requirement is set for the given key.
     *
     * @param string $key A variable name
     *
     * @return Boolean true if a requirement is specified, false otherwise
     */
    public function hasRequirement($key)
    {
        return array_key_exists($key, $this->requirements);
    }
    /**
     * Sets a requirement for the given key.
     *
     * @param string $key   The key
     * @param string $regex The regex
     *
     * @return Route The current Route instance
     *
     * @api
     */
    public function setRequirement($key, $regex)
    {
        $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        $this->compiled = null;
        return $this;
    }
    /**
     * Compiles the route.
     *
     * @return CompiledRoute A CompiledRoute instance
     *
     * @throws \LogicException If the Route cannot be compiled because the
     *                         path or host pattern is invalid
     *
     * @see RouteCompiler which is responsible for the compilation process
     */
    public function compile()
    {
        if (null !== $this->compiled) {
            return $this->compiled;
        }
        $class = $this->getOption('compiler_class');
        return $this->compiled = $class::compile($this);
    }
    private function sanitizeRequirement($key, $regex)
    {
        if (!is_string($regex)) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" must be a string.', $key));
        }
        if ('' !== $regex && '^' === $regex[0]) {
            $regex = (string) substr($regex, 1);
        }
        if ('$' === substr($regex, -1)) {
            $regex = substr($regex, 0, -1);
        }
        if ('' === $regex) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" cannot be empty.', $key));
        }
        // this is to keep BC and will be removed in a future version
        if ('_scheme' === $key) {
            $this->setSchemes(explode('|', $regex));
        } elseif ('_method' === $key) {
            $this->setMethods(explode('|', $regex));
        }
        return $regex;
    }
}
namespace Illuminate\Routing;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route as BaseRoute;
class Route extends BaseRoute
{
    /**
     * The router instance.
     *
     * @var  Illuminate\Routing\Router
     */
    protected $router;
    /**
     * The matching parameter array.
     *
     * @var array
     */
    protected $parameters;
    /**
     * The parsed parameter array.
     *
     * @var array
     */
    protected $parsedParameters;
    /**
     * Execute the route and return the response.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return mixed
     */
    public function run(Request $request)
    {
        $this->parsedParameters = null;
        $response = $this->callBeforeFilters($request);
        // We will only call the router callable if no "before" middlewares returned
        // a response. If they do, we will consider that the response to requests
        // so that the request "lifecycle" will be easily halted for filtering.
        if (!isset($response)) {
            $response = $this->callCallable();
        }
        $response = $this->router->prepare($response, $request);
        // Once we have the "prepared" response, we will iterate through every after
        // filter and call each of them with the request and the response so they
        // can perform any final work that needs to be done after a route call.
        foreach ($this->getAfterFilters() as $filter) {
            $this->callFilter($filter, $request, array($response));
        }
        return $response;
    }
    /**
     * Call the callable Closure attached to the route.
     *
     * @return mixed
     */
    protected function callCallable()
    {
        $variables = array_values($this->getParametersWithoutDefaults());
        return call_user_func_array($this->getOption('_call'), $variables);
    }
    /**
     * Call all of the before filters on the route.
     *
     * @param  Symfony\Component\HttpFoundation\Request   $request
     * @return mixed
     */
    protected function callBeforeFilters(Request $request)
    {
        $before = $this->getAllBeforeFilters($request);
        $response = null;
        // Once we have each middlewares, we will simply iterate through them and call
        // each one of them with the request. We will set the response variable to
        // whatever it may return so that it may override the request processes.
        foreach ($before as $filter) {
            $response = $this->callFilter($filter, $request);
            if (!is_null($response)) {
                return $response;
            }
        }
    }
    /**
     * Get all of the before filters to run on the route.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return array
     */
    protected function getAllBeforeFilters(Request $request)
    {
        $before = $this->getBeforeFilters();
        return array_merge($before, $this->router->findPatternFilters($request));
    }
    /**
     * Call a given filter with the parameters.
     *
     * @param  string  $name
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @param  array   $params
     * @return mixed
     */
    public function callFilter($name, Request $request, array $params = array())
    {
        if (!$this->router->filtersEnabled()) {
            return;
        }
        $merge = array($this->router->getCurrentRoute(), $request);
        $params = array_merge($merge, $params);
        // Next we will parse the filter name to extract out any parameters and adding
        // any parameters specified in a filter name to the end of the lists of our
        // parameters, since the ones at the beginning are typically very static.
        list($name, $params) = $this->parseFilter($name, $params);
        if (!is_null($callable = $this->router->getFilter($name))) {
            return call_user_func_array($callable, $params);
        }
    }
    /**
     * Parse a filter name and add any parameters to the array.
     *
     * @param  string  $name
     * @param  array   $parameters
     * @return array
     */
    protected function parseFilter($name, $parameters = array())
    {
        if (str_contains($name, ':')) {
            // If the filter name contains a colon, we will assume that the developer
            // is passing along some parameters with the name, and we will explode
            // out the name and paramters, merging the parameters onto the list.
            $segments = explode(':', $name);
            $name = $segments[0];
            // We will merge the arguments specified in the filter name into the list
            // of existing parameters. We'll send them at the end since any values
            // at the front are usually static such as request, response, route.
            $arguments = explode(',', $segments[1]);
            $parameters = array_merge($parameters, $arguments);
        }
        return array($name, $parameters);
    }
    /**
     * Get a parameter by name from the route.
     *
     * @param  string  $name
     * @param  mixed   $default
     * @return string
     */
    public function getParameter($name, $default = null)
    {
        return array_get($this->getParameters(), $name, $default);
    }
    /**
     * Get the parameters to the callback.
     *
     * @return array
     */
    public function getParameters()
    {
        // If we have already parsed the parameters, we will just return the listing
        // the we already parsed, as some of these may have been resolved through
        // a binder that uses a database repository and should'nt be run again.
        if (isset($this->parsedParameters)) {
            return $this->parsedParameters;
        }
        $variables = $this->compile()->getVariables();
        // To get the parameter array, we need to spin the names of the variables on
        // the compiled route and match them to the parameters that we got when a
        // route is matched by the router, as routes instances don't have them.
        $parameters = array();
        foreach ($variables as $variable) {
            $parameters[$variable] = $this->resolveParameter($variable);
        }
        return $this->parsedParameters = $parameters;
    }
    /**
     * Resolve a parameter value for the route.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function resolveParameter($key)
    {
        $value = $this->parameters[$key];
        // If the parameter has a binder, we will call the binder to resolve the real
        // value for the parameters. The binders could make a database call to get
        // a User object for example or may transform the input in some fashion.
        if ($this->router->hasBinder($key)) {
            return $this->router->performBinding($key, $value, $this);
        }
        return $value;
    }
    /**
     * Get the route parameters without missing defaults.
     *
     * @return array
     */
    public function getParametersWithoutDefaults()
    {
        $parameters = $this->getParameters();
        foreach ($parameters as $key => $value) {
            // When calling functions using call_user_func_array, we don't want to write
            // over any existing default parameters, so we will remove every optional
            // parameter from the list that did not get a specified value on route.
            if ($this->isMissingDefault($key, $value)) {
                unset($parameters[$key]);
            }
        }
        return $parameters;
    }
    /**
     * Determine if a route parameter is really a missing default.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    protected function isMissingDefault($key, $value)
    {
        return $this->isOptional($key) and is_null($value);
    }
    /**
     * Determine if a given key is optional.
     *
     * @param  string  $key
     * @return bool
     */
    public function isOptional($key)
    {
        return array_key_exists($key, $this->getDefaults());
    }
    /**
     * Get the keys of the variables on the route.
     *
     * @return array
     */
    public function getParameterKeys()
    {
        return $this->compile()->getVariables();
    }
    /**
     * Force a given parameter to match a regular expression.
     *
     * @param  string  $name
     * @param  string  $expression
     * @return Illuminate\Routing\Route
     */
    public function where($name, $expression)
    {
        $this->setRequirement($name, $expression);
        return $this;
    }
    /**
     * Set the default value for a parameter.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return Illuminate\Routing\Route
     */
    public function defaults($key, $value)
    {
        $this->setDefault($key, $value);
        return $this;
    }
    /**
     * Set the before filters on the route.
     *
     * @param  dynamic
     * @return Illuminate\Routing\Route
     */
    public function before()
    {
        $current = $this->getBeforeFilters();
        $before = array_unique(array_merge($current, func_get_args()));
        $this->setOption('_before', $before);
        return $this;
    }
    /**
     * Set the after filters on the route.
     *
     * @param  dynamic
     * @return Illuminate\Routing\Route
     */
    public function after()
    {
        $current = $this->getAfterFilters();
        $after = array_unique(array_merge($current, func_get_args()));
        $this->setOption('_after', $after);
        return $this;
    }
    /**
     * Get the before filters on the route.
     *
     * @return array
     */
    public function getBeforeFilters()
    {
        return $this->getOption('_before') ?: array();
    }
    /**
     * Set the before filters on the route.
     *
     * @param  string  $value
     * @return void
     */
    public function setBeforeFilters($value)
    {
        $filters = is_string($value) ? explode('|', $value) : (array) $value;
        $this->setOption('_before', $filters);
    }
    /**
     * Get the after filters on the route.
     *
     * @return array
     */
    public function getAfterFilters()
    {
        return $this->getOption('_after') ?: array();
    }
    /**
     * Set the after filters on the route.
     *
     * @param  string  $value
     * @return void
     */
    public function setAfterFilters($value)
    {
        $filters = is_string($value) ? explode('|', $value) : (array) $value;
        $this->setOption('_after', $filters);
    }
    /**
     * Set the matching parameter array on the route.
     *
     * @param  array  $parameters
     * @return void
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }
    /**
     * Set the Router instance on the route.
     *
     * @param  Illuminate\Routing\Router  $router
     * @return Illuminate\Routing\Route
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
        return $this;
    }
}
namespace Illuminate\View\Engines;

use Closure;
class EngineResolver
{
    /**
     * The array of engine resolvers.
     *
     * @var array
     */
    protected $resolvers = array();
    /**
     * The resolved engine instances.
     *
     * @var array
     */
    protected $resolved = array();
    /**
     * Register a new engine resolver.
     *
     * @param  string   $engine
     * @param  Closure  $resolver
     * @return void
     */
    public function register($engine, Closure $resolver)
    {
        $this->resolvers[$engine] = $resolver;
    }
    /**
     * Resolver an engine instance by name.
     *
     * @param  string  $engine
     * @return Illuminate\View\Engines\EngineInterface
     */
    public function resolve($engine)
    {
        if (!isset($this->resolved[$engine])) {
            $this->resolved[$engine] = call_user_func($this->resolvers[$engine]);
        }
        return $this->resolved[$engine];
    }
}
namespace Illuminate\View;

interface ViewFinderInterface
{
    /**
     * Get the fully qualified location of the view.
     *
     * @param  string  $name
     * @return string
     */
    public function find($view);
    /**
     * Add a location to the finder.
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location);
    /**
     * Add a namespace hint to the finder.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint);
    /**
     * Add a valid view extension to the finder.
     *
     * @param  string  $extension
     * @return void
     */
    public function addExtension($extension);
}
namespace Illuminate\View;

use Illuminate\Filesystem\Filesystem;
class FileViewFinder implements ViewFinderInterface
{
    /**
     * The filesystem instance.
     *
     * @var Illuminate\Filesystem
     */
    protected $files;
    /**
     * The array of active view paths.
     *
     * @var array
     */
    protected $paths;
    /**
     * The namespace to file path hints.
     *
     * @var array
     */
    protected $hints = array();
    /**
     * Register a view extension with the finder.
     *
     * @var array
     */
    protected $extensions = array('php', 'blade.php');
    /**
     * Create a new file view loader instance.
     *
     * @param  Illuminate\Filesystem  $files
     * @param  array  $paths
     * @param  array  $extensions
     * @return void
     */
    public function __construct(Filesystem $files, array $paths, array $extensions = null)
    {
        $this->files = $files;
        $this->paths = $paths;
        if (isset($extensions)) {
            $this->extensions = $extensions;
        }
    }
    /**
     * Get the fully qualified location of the view.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        if (strpos($name, '::') !== false) {
            return $this->findNamedPathView($name);
        }
        return $this->findInPaths($name, $this->paths);
    }
    /**
     * Get the path to a template with a named path.
     *
     * @param  string  $name
     * @return string
     */
    protected function findNamedPathView($name)
    {
        list($namespace, $view) = $this->getNamespaceSegments($name);
        return $this->findInPaths($view, $this->hints[$namespace]);
    }
    /**
     * Get the segments of a template with a named path.
     *
     * @param  string  $name
     * @return array
     */
    protected function getNamespaceSegments($name)
    {
        $segments = explode('::', $name);
        if (count($segments) != 2) {
            throw new \InvalidArgumentException("View [{$name}] has an invalid name.");
        }
        if (!isset($this->hints[$segments[0]])) {
            throw new \InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }
        return $segments;
    }
    /**
     * Find the given view in the list of paths.
     *
     * @param  string  $name
     * @param  array   $paths
     * @return string
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if ($this->files->exists($viewPath = $path . '/' . $file)) {
                    return $viewPath;
                }
            }
        }
        throw new \InvalidArgumentException("View [{$name}] not found.");
    }
    /**
     * Get an array of possible view files.
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        return array_map(function ($extension) use($name) {
            return str_replace('.', '/', $name) . '.' . $extension;
        }, $this->extensions);
    }
    /**
     * Add a location to the finder.
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->paths[] = $location;
    }
    /**
     * Add a namespace hint to the finder.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addNamespace($namespace, $hints)
    {
        $hints = (array) $hints;
        if (isset($this->hints[$namespace])) {
            $hints = array_merge($this->hints[$namespace], $hints);
        }
        $this->hints[$namespace] = $hints;
    }
    /**
     * Register an extension with the view finder.
     *
     * @param  string  $extension
     * @return void
     */
    public function addExtension($extension)
    {
        $this->extensions[] = $extension;
    }
    /**
     * Get the filesystem instance.
     *
     * @return Illuminate\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
    /**
     * Get the active view paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }
    /**
     * Get the namespace to file path hints.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }
    /**
     * Get registered extensions.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}
namespace Illuminate\View;

use Closure;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\View\Engines\EngineResolver;
class Environment
{
    /**
     * The engine implementation.
     *
     * @var Illuminate\View\Engines\EngineResolver
     */
    protected $engines;
    /**
     * The view finder implementation.
     *
     * @var Illuminate\View\ViewFinderInterface
     */
    protected $finder;
    /**
     * The event dispatcher instance.
     *
     * @var Illuminate\Events\Dispatcher
     */
    protected $events;
    /**
     * The IoC container instance.
     *
     * @var Illuminate\Container
     */
    protected $container;
    /**
     * Data that should be available to all templates.
     *
     * @var array
     */
    protected $shared = array();
    /**
     * The extension to engine bindings.
     *
     * @var array
     */
    protected $extensions = array('blade.php' => 'blade', 'php' => 'php');
    /**
     * The view composer events.
     *
     * @var array
     */
    protected $composers = array();
    /**
     * All of the finished, captured sections.
     *
     * @var array
     */
    protected $sections = array();
    /**
     * The stack of in-progress sections.
     *
     * @var array
     */
    protected $sectionStack = array();
    /**
     * The number of active rendering operations.
     *
     * @var int
     */
    protected $renderCount = 0;
    /**
     * Create a new view environment instance.
     *
     * @param  Illuminate\View\Engines\EngineResolver  $engines
     * @param  Illuminate\View\ViewFinderInterface  $finder
     * @param  Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(EngineResolver $engines, ViewFinderInterface $finder, Dispatcher $events)
    {
        $this->finder = $finder;
        $this->events = $events;
        $this->engines = $engines;
        $this->share('__env', $this);
    }
    /**
     * Get a evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  array   $data
     * @return Illuminate\View\View
     */
    public function make($view, array $data = array())
    {
        $path = $this->finder->find($view);
        return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
    }
    /**
     * Determine if a given view exists.
     *
     * @param  string  $view
     * @return bool
     */
    public function exists($view)
    {
        try {
            $this->finder->find($view);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }
    /**
     * Get the rendered contents of a partial from a loop.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  string  $iterator
     * @param  string  $empty
     * @return string
     */
    public function renderEach($view, $data, $iterator, $empty = 'raw|')
    {
        $result = '';
        // If is actually data in the array, we will loop through the data and append
        // an instance of the partial view to the final result HTML passing in the
        // iterated value of this data array, allowing the views to access them.
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $data = array('key' => $key, $iterator => $value);
                $result .= $this->make($view, $data)->render();
            }
        } else {
            if (starts_with($empty, 'raw|')) {
                $result = substr($empty, 4);
            } else {
                $result = $this->make($empty)->render();
            }
        }
        return $result;
    }
    /**
     * Get the appropriate view engine for the given path.
     *
     * @param  string  $path
     * @return Illuminate\View\Engines\EngineInterface
     */
    protected function getEngineFromPath($path)
    {
        $engine = $this->extensions[$this->getExtension($path)];
        return $this->engines->resolve($engine);
    }
    /**
     * Get the extension used by the view file.
     *
     * @param  string  $path
     * @return string
     */
    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);
        return array_first($extensions, function ($key, $value) use($path) {
            return ends_with($path, $value);
        });
    }
    /**
     * Add a piece of shared data to the environment.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function share($key, $value)
    {
        $this->shared[$key] = $value;
    }
    /**
     * Register a view composer event.
     *
     * @param  array|string  $views
     * @param  Closure|string  $callback
     * @return Closure
     */
    public function composer($views, $callback)
    {
        $composers = array();
        foreach ((array) $views as $view) {
            $composers[] = $this->addComposer($view, $callback);
        }
        return $composers;
    }
    /**
     * Add a composer for a given view.
     *
     * @param  string  $view
     * @param  Closure|string  $callback
     * @return Closure
     */
    protected function addComposer($view, $callback)
    {
        if ($callback instanceof Closure) {
            $this->events->listen('composing: ' . $view, $callback);
            return $callback;
        } elseif (is_string($callback)) {
            return $this->addClassComposer($view, $callback);
        }
    }
    /**
     * Register a class based view composer.
     *
     * @param  string   $view
     * @param  string   $class
     * @return Closure
     */
    protected function addClassComposer($view, $class)
    {
        $name = 'composing: ' . $view;
        // When registering a class based view "composer", we will simply resolve the
        // classes from the application IoC container then call the compose method
        // on the instance. This allows for convenient, testable view composers.
        $callback = $this->buildClassComposerCallback($class);
        $this->events->listen($name, $callback);
        return $callback;
    }
    /**
     * Build a class based container callback Closure.
     *
     * @param  string   $class
     * @return Closure
     */
    protected function buildClassComposerCallback($class)
    {
        $container = $this->container;
        list($class, $method) = $this->parseClassComposer($class);
        // Once we have the class and method name, we can build the Closure to resolve
        // the instance out of the IoC container and call the method on it with the
        // given arguments that are passed to the Closure as the composer's data.
        return function () use($class, $method, $container) {
            $callable = array($container->make($class), $method);
            return call_user_func_array($callable, func_get_args());
        };
    }
    /**
     * Parse a class based composer name.
     *
     * @param  string  $class
     * @return array
     */
    protected function parseClassComposer($class)
    {
        return str_contains($class, '@') ? explode('@', $class) : array($class, 'compose');
    }
    /**
     * Call the composer for a given view.
     *
     * @param  Illuminate\View\View  $view
     * @return void
     */
    public function callComposer(View $view)
    {
        $this->events->fire('composing: ' . $view->getName(), array($view));
    }
    /**
     * Start injecting content into a section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startSection($section, $content = '')
    {
        if ($content === '') {
            ob_start() and $this->sectionStack[] = $section;
        } else {
            $this->extendSection($section, $content);
        }
    }
    /**
     * Inject inline content into a section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function inject($section, $content)
    {
        return $this->startSection($section, $content);
    }
    /**
     * Stop injecting content into a section and return its contents.
     *
     * @return string
     */
    public function yieldSection()
    {
        return $this->yieldContent($this->stopSection());
    }
    /**
     * Stop injecting content into a section.
     *
     * @return string
     */
    public function stopSection()
    {
        $last = array_pop($this->sectionStack);
        $this->extendSection($last, ob_get_clean());
        return $last;
    }
    /**
     * Append content to a given section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendSection($section, $content)
    {
        if (isset($this->sections[$section])) {
            $content = str_replace('@parent', $content, $this->sections[$section]);
            $this->sections[$section] = $content;
        } else {
            $this->sections[$section] = $content;
        }
    }
    /**
     * Get the string contents of a section.
     *
     * @param  string  $section
     * @return string
     */
    public function yieldContent($section)
    {
        return isset($this->sections[$section]) ? $this->sections[$section] : '';
    }
    /**
     * Flush all of the section contents.
     *
     * @return void
     */
    public function flushSections()
    {
        $this->sections = array();
        $this->sectionStack = array();
    }
    /**
     * Increment the rendering counter.
     *
     * @return void
     */
    public function incrementRender()
    {
        $this->renderCount++;
    }
    /**
     * Decrement the rendering counter.
     *
     * @return void
     */
    public function decrementRender()
    {
        $this->renderCount--;
    }
    /**
     * Check if there are no active render operations.
     *
     * @return bool
     */
    public function doneRendering()
    {
        return $this->renderCount == 0;
    }
    /**
     * Add a location to the array of view locations.
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->finder->addLocation($location);
    }
    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addNamespace($namespace, $hints)
    {
        $this->finder->addNamespace($namespace, $hints);
    }
    /**
     * Register a valid view extension and its engine.
     *
     * @param  string   $extension
     * @param  string   $engine
     * @param  Closure  $resolver
     * @return void
     */
    public function addExtension($extension, $engine, $resolver = null)
    {
        $this->finder->addExtension($extension);
        if (isset($resolver)) {
            $this->engines->register($engine, $resolver);
        }
        $this->extensions[$extension] = $engine;
    }
    /**
     * Get the engine resolver instance.
     *
     * @return Illuminate\View\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return $this->engines;
    }
    /**
     * Get the view finder instance.
     *
     * @return Illuminate\View\ViewFinder
     */
    public function getFinder()
    {
        return $this->finder;
    }
    /**
     * Get the event dispatcher instance.
     *
     * @return Illuminate\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }
    /**
     * Get the IoC container instance.
     *
     * @return Illuminate\Container
     */
    public function getContainer()
    {
        return $this->container;
    }
    /**
     * Set the IoC container instance.
     *
     * @param  Illuminate\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
    /**
     * Get all of the shared data for the environment.
     *
     * @return array
     */
    public function getShared()
    {
        return $this->shared;
    }
    /**
     * Get the entire array of sections.
     *
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }
}
namespace Illuminate\Support\Contracts;

interface MessageProviderInterface
{
    /**
     * Get the messages for the instance.
     *
     * @return ILluminate\Support\MessageBag
     */
    public function getMessageBag();
}
namespace Illuminate\Support;

use Countable;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\MessageProviderInterface;
class MessageBag implements ArrayableInterface, Countable, JsonableInterface, MessageProviderInterface
{
    /**
     * All of the registered messages.
     *
     * @var array
     */
    protected $messages = array();
    /**
     * Default format for message output.
     *
     * @var string
     */
    protected $format = '<span class="help-inline">:message</span>';
    /**
     * Create a new message bag instance.
     *
     * @param  array  $messages
     * @return void
     */
    public function __construct(array $messages = array())
    {
        foreach ($messages as $key => $value) {
            $this->messages[$key] = (array) $value;
        }
    }
    /**
     * Add a message to the bag.
     *
     * @param  string  $key
     * @param  string  $message
     * @return void
     */
    public function add($key, $message)
    {
        if ($this->isUnique($key, $message)) {
            $this->messages[$key][] = $message;
        }
    }
    /**
     * Merge a new array of messages into the bag.
     *
     * @param  array  $messages
     * @return void
     */
    public function merge(array $messages)
    {
        $this->messages = array_merge_recursive($this->messages, $messages);
    }
    /**
     * Determine if a key and message combination already exists.
     *
     * @param  string  $key
     * @param  string  $message
     * @return bool
     */
    protected function isUnique($key, $message)
    {
        $messages = (array) $this->messages;
        return !isset($messages[$key]) or !in_array($message, $messages[$key]);
    }
    /**
     * Determine if messages exist for a given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key = null)
    {
        return $this->first($key) !== '';
    }
    /**
     * Get the first message from the bag for a given key.
     *
     * @param  string  $key
     * @param  string  $format
     * @return string
     */
    public function first($key = null, $format = null)
    {
        $messages = $this->get($key, $format);
        return count($messages) > 0 ? $messages[0] : '';
    }
    /**
     * Get all of the messages from the bag for a given key.
     *
     * @param  string  $key
     * @param  string  $format
     * @return array
     */
    public function get($key, $format = null)
    {
        $format = $this->checkFormat($format);
        // If the message exists in the container, we will transform it and return
        // the message. Otherwise, we'll return an empty array since the entire
        // methods is to return back an array of messages in the first place.
        if (array_key_exists($key, $this->messages)) {
            return $this->transform($this->messages[$key], $format, $key);
        }
        return array();
    }
    /**
     * Get all of the messages for every key in the bag.
     *
     * @param  string  $format
     * @return array
     */
    public function all($format = null)
    {
        $format = $this->checkFormat($format);
        $all = array();
        foreach ($this->messages as $key => $messages) {
            $all = array_merge($all, $this->transform($messages, $format, $key));
        }
        return $all;
    }
    /**
     * Format an array of messages.
     *
     * @param  array   $messages
     * @param  string  $format
     * @param  string  $messageKey
     * @return array
     */
    protected function transform($messages, $format, $messageKey)
    {
        $messages = (array) $messages;
        // We will simply spin through the given messages and transform each one
        // replacing the :message place holder with the real message allowing
        // the messages to be easily formatted to each developer's desires.
        foreach ($messages as $key => &$message) {
            $replace = array(':message', ':key');
            $message = str_replace($replace, array($message, $messageKey), $format);
        }
        return $messages;
    }
    /**
     * Get the appropriate format based on the given format.
     *
     * @param  string  $format
     * @return string
     */
    protected function checkFormat($format)
    {
        return $format === null ? $this->format : $format;
    }
    /**
     * Get the raw messages in the container.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
    /**
     * Get the messages for the instance.
     *
     * @return ILluminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this;
    }
    /**
     * Get the default message format.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
    /**
     * Set the default message format.
     *
     * @param  string  $format
     */
    public function setFormat($format = ':message')
    {
        $this->format = $format;
    }
    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function any()
    {
        return $this->count() > 0;
    }
    /**
     * Get the number of messages in the container.
     *
     * @return int
     */
    public function count()
    {
        return count($this->messages);
    }
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getMessages();
    }
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    /**
     * Convert the message bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing;

use Symfony\Component\HttpFoundation\Request;
/**
 * Holds information about the current request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class RequestContext
{
    private $baseUrl;
    private $pathInfo;
    private $method;
    private $host;
    private $scheme;
    private $httpPort;
    private $httpsPort;
    /**
     * @var array
     */
    private $parameters = array();
    /**
     * Constructor.
     *
     * @param string  $baseUrl   The base URL
     * @param string  $method    The HTTP method
     * @param string  $host      The HTTP host name
     * @param string  $scheme    The HTTP scheme
     * @param integer $httpPort  The HTTP port
     * @param integer $httpsPort The HTTPS port
     * @param string  $path      The path
     *
     * @api
     */
    public function __construct($baseUrl = '', $method = 'GET', $host = 'localhost', $scheme = 'http', $httpPort = 80, $httpsPort = 443, $path = '/')
    {
        $this->baseUrl = $baseUrl;
        $this->method = strtoupper($method);
        $this->host = $host;
        $this->scheme = strtolower($scheme);
        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
        $this->pathInfo = $path;
    }
    public function fromRequest(Request $request)
    {
        $this->setBaseUrl($request->getBaseUrl());
        $this->setPathInfo($request->getPathInfo());
        $this->setMethod($request->getMethod());
        $this->setHost($request->getHost());
        $this->setScheme($request->getScheme());
        $this->setHttpPort($request->isSecure() ? $this->httpPort : $request->getPort());
        $this->setHttpsPort($request->isSecure() ? $request->getPort() : $this->httpsPort);
    }
    /**
     * Gets the base URL.
     *
     * @return string The base URL
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
    /**
     * Sets the base URL.
     *
     * @param string $baseUrl The base URL
     *
     * @api
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
    /**
     * Gets the path info.
     *
     * @return string The path info
     */
    public function getPathInfo()
    {
        return $this->pathInfo;
    }
    /**
     * Sets the path info.
     *
     * @param string $pathInfo The path info
     */
    public function setPathInfo($pathInfo)
    {
        $this->pathInfo = $pathInfo;
    }
    /**
     * Gets the HTTP method.
     *
     * The method is always an uppercased string.
     *
     * @return string The HTTP method
     */
    public function getMethod()
    {
        return $this->method;
    }
    /**
     * Sets the HTTP method.
     *
     * @param string $method The HTTP method
     *
     * @api
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }
    /**
     * Gets the HTTP host.
     *
     * @return string The HTTP host
     */
    public function getHost()
    {
        return $this->host;
    }
    /**
     * Sets the HTTP host.
     *
     * @param string $host The HTTP host
     *
     * @api
     */
    public function setHost($host)
    {
        $this->host = $host;
    }
    /**
     * Gets the HTTP scheme.
     *
     * @return string The HTTP scheme
     */
    public function getScheme()
    {
        return $this->scheme;
    }
    /**
     * Sets the HTTP scheme.
     *
     * @param string $scheme The HTTP scheme
     *
     * @api
     */
    public function setScheme($scheme)
    {
        $this->scheme = strtolower($scheme);
    }
    /**
     * Gets the HTTP port.
     *
     * @return string The HTTP port
     */
    public function getHttpPort()
    {
        return $this->httpPort;
    }
    /**
     * Sets the HTTP port.
     *
     * @param string $httpPort The HTTP port
     *
     * @api
     */
    public function setHttpPort($httpPort)
    {
        $this->httpPort = $httpPort;
    }
    /**
     * Gets the HTTPS port.
     *
     * @return string The HTTPS port
     */
    public function getHttpsPort()
    {
        return $this->httpsPort;
    }
    /**
     * Sets the HTTPS port.
     *
     * @param string $httpsPort The HTTPS port
     *
     * @api
     */
    public function setHttpsPort($httpsPort)
    {
        $this->httpsPort = $httpsPort;
    }
    /**
     * Returns the parameters.
     *
     * @return array The parameters
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    /**
     * Sets the parameters.
     *
     * This method implements a fluent interface.
     *
     * @param array $parameters The parameters
     *
     * @return Route The current Route instance
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }
    /**
     * Gets a parameter value.
     *
     * @param string $name A parameter name
     *
     * @return mixed The parameter value
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }
    /**
     * Checks if a parameter value is set for the given parameter.
     *
     * @param string $name A parameter name
     *
     * @return Boolean true if the parameter value is set, false otherwise
     */
    public function hasParameter($name)
    {
        return array_key_exists($name, $this->parameters);
    }
    /**
     * Sets a parameter value.
     *
     * @param string $name      A parameter name
     * @param mixed  $parameter The parameter value
     *
     * @api
     */
    public function setParameter($name, $parameter)
    {
        $this->parameters[$name] = $parameter;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing\Matcher;

use Symfony\Component\Routing\RequestContextAwareInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
/**
 * UrlMatcherInterface is the interface that all URL matcher classes must implement.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
interface UrlMatcherInterface extends RequestContextAwareInterface
{
    /**
     * Tries to match a URL path with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     *
     * @api
     */
    public function match($pathinfo);
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing\Matcher;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
/**
 * UrlMatcher matches URL based on a set of routes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class UrlMatcher implements UrlMatcherInterface
{
    const REQUIREMENT_MATCH = 0;
    const REQUIREMENT_MISMATCH = 1;
    const ROUTE_MATCH = 2;
    /**
     * @var RequestContext
     */
    protected $context;
    /**
     * @var array
     */
    protected $allow = array();
    /**
     * @var RouteCollection
     */
    protected $routes;
    /**
     * Constructor.
     *
     * @param RouteCollection $routes  A RouteCollection instance
     * @param RequestContext  $context The context
     *
     * @api
     */
    public function __construct(RouteCollection $routes, RequestContext $context)
    {
        $this->routes = $routes;
        $this->context = $context;
    }
    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }
    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }
    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $this->allow = array();
        if ($ret = $this->matchCollection(rawurldecode($pathinfo), $this->routes)) {
            return $ret;
        }
        throw 0 < count($this->allow) ? new MethodNotAllowedException(array_unique(array_map('strtoupper', $this->allow))) : new ResourceNotFoundException();
    }
    /**
     * Tries to match a URL with a set of routes.
     *
     * @param string          $pathinfo The path info to be parsed
     * @param RouteCollection $routes   The set of routes
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    protected function matchCollection($pathinfo, RouteCollection $routes)
    {
        foreach ($routes as $name => $route) {
            $compiledRoute = $route->compile();
            // check the static prefix of the URL first. Only use the more expensive preg_match when it matches
            if ('' !== $compiledRoute->getStaticPrefix() && 0 !== strpos($pathinfo, $compiledRoute->getStaticPrefix())) {
                continue;
            }
            if (!preg_match($compiledRoute->getRegex(), $pathinfo, $matches)) {
                continue;
            }
            $hostMatches = array();
            if ($compiledRoute->getHostRegex() && !preg_match($compiledRoute->getHostRegex(), $this->context->getHost(), $hostMatches)) {
                continue;
            }
            // check HTTP method requirement
            if ($req = $route->getRequirement('_method')) {
                // HEAD and GET are equivalent as per RFC
                if ('HEAD' === ($method = $this->context->getMethod())) {
                    $method = 'GET';
                }
                if (!in_array($method, $req = explode('|', strtoupper($req)))) {
                    $this->allow = array_merge($this->allow, $req);
                    continue;
                }
            }
            $status = $this->handleRouteRequirements($pathinfo, $name, $route);
            if (self::ROUTE_MATCH === $status[0]) {
                return $status[1];
            }
            if (self::REQUIREMENT_MISMATCH === $status[0]) {
                continue;
            }
            return $this->getAttributes($route, $name, array_replace($matches, $hostMatches));
        }
    }
    /**
     * Returns an array of values to use as request attributes.
     *
     * As this method requires the Route object, it is not available
     * in matchers that do not have access to the matched Route instance
     * (like the PHP and Apache matcher dumpers).
     *
     * @param Route  $route      The route we are matching against
     * @param string $name       The name of the route
     * @param array  $attributes An array of attributes from the matcher
     *
     * @return array An array of parameters
     */
    protected function getAttributes(Route $route, $name, array $attributes)
    {
        $attributes['_route'] = $name;
        return $this->mergeDefaults($attributes, $route->getDefaults());
    }
    /**
     * Handles specific route requirements.
     *
     * @param string $pathinfo The path
     * @param string $name     The route name
     * @param Route  $route    The route
     *
     * @return array The first element represents the status, the second contains additional information
     */
    protected function handleRouteRequirements($pathinfo, $name, Route $route)
    {
        // check HTTP scheme requirement
        $scheme = $route->getRequirement('_scheme');
        $status = $scheme && $scheme !== $this->context->getScheme() ? self::REQUIREMENT_MISMATCH : self::REQUIREMENT_MATCH;
        return array($status, null);
    }
    /**
     * Get merged default parameters.
     *
     * @param array $params   The parameters
     * @param array $defaults The defaults
     *
     * @return array Merged default parameters
     */
    protected function mergeDefaults($params, $defaults)
    {
        foreach ($params as $key => $value) {
            if (!is_int($key)) {
                $defaults[$key] = $value;
            }
        }
        return $defaults;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing;

/**
 * @api
 */
interface RequestContextAwareInterface
{
    /**
     * Sets the request context.
     *
     * @param RequestContext $context The context
     *
     * @api
     */
    public function setContext(RequestContext $context);
    /**
     * Gets the request context.
     *
     * @return RequestContext The context
     *
     * @api
     */
    public function getContext();
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing;

/**
 * RouteCompilerInterface is the interface that all RouteCompiler classes must implement.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface RouteCompilerInterface
{
    /**
     * Compiles the current route instance.
     *
     * @param Route $route A Route instance
     *
     * @return CompiledRoute A CompiledRoute instance
     *
     * @throws \LogicException If the Route cannot be compiled because the
     *                         path or host pattern is invalid
     */
    public static function compile(Route $route);
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing;

/**
 * RouteCompiler compiles Route instances to CompiledRoute instances.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 */
class RouteCompiler implements RouteCompilerInterface
{
    const REGEX_DELIMITER = '#';
    /**
     * This string defines the characters that are automatically considered separators in front of
     * optional placeholders (with default and no static text following). Such a single separator
     * can be left out together with the optional placeholder from matching and generating URLs.
     */
    const SEPARATORS = '/,;.:-_~+*=@|';
    /**
     * {@inheritDoc}
     *
     * @throws \LogicException  If a variable is referenced more than once
     * @throws \DomainException If a variable name is numeric because PHP raises an error for such
     *                          subpatterns in PCRE and thus would break matching, e.g. "(?P<123>.+)".
     */
    public static function compile(Route $route)
    {
        $staticPrefix = null;
        $hostVariables = array();
        $pathVariables = array();
        $variables = array();
        $tokens = array();
        $regex = null;
        $hostRegex = null;
        $hostTokens = array();
        if ('' !== ($host = $route->getHost())) {
            $result = self::compilePattern($route, $host, true);
            $hostVariables = $result['variables'];
            $variables = array_merge($variables, $hostVariables);
            $hostTokens = $result['tokens'];
            $hostRegex = $result['regex'];
        }
        $path = $route->getPath();
        $result = self::compilePattern($route, $path, false);
        $staticPrefix = $result['staticPrefix'];
        $pathVariables = $result['variables'];
        $variables = array_merge($variables, $pathVariables);
        $tokens = $result['tokens'];
        $regex = $result['regex'];
        return new CompiledRoute($staticPrefix, $regex, $tokens, $pathVariables, $hostRegex, $hostTokens, $hostVariables, array_unique($variables));
    }
    private static function compilePattern(Route $route, $pattern, $isHost)
    {
        $tokens = array();
        $variables = array();
        $matches = array();
        $pos = 0;
        $defaultSeparator = $isHost ? '.' : '/';
        // Match all variables enclosed in "{}" and iterate over them. But we only want to match the innermost variable
        // in case of nested "{}", e.g. {foo{bar}}. This in ensured because \w does not match "{" or "}" itself.
        preg_match_all('#\\{\\w+\\}#', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            $varName = substr($match[0][0], 1, -1);
            // get all static text preceding the current variable
            $precedingText = substr($pattern, $pos, $match[0][1] - $pos);
            $pos = $match[0][1] + strlen($match[0][0]);
            $precedingChar = strlen($precedingText) > 0 ? substr($precedingText, -1) : '';
            $isSeparator = '' !== $precedingChar && false !== strpos(static::SEPARATORS, $precedingChar);
            if (is_numeric($varName)) {
                throw new \DomainException(sprintf('Variable name "%s" cannot be numeric in route pattern "%s". Please use a different name.', $varName, $pattern));
            }
            if (in_array($varName, $variables)) {
                throw new \LogicException(sprintf('Route pattern "%s" cannot reference variable name "%s" more than once.', $pattern, $varName));
            }
            if ($isSeparator && strlen($precedingText) > 1) {
                $tokens[] = array('text', substr($precedingText, 0, -1));
            } elseif (!$isSeparator && strlen($precedingText) > 0) {
                $tokens[] = array('text', $precedingText);
            }
            $regexp = $route->getRequirement($varName);
            if (null === $regexp) {
                $followingPattern = (string) substr($pattern, $pos);
                // Find the next static character after the variable that functions as a separator. By default, this separator and '/'
                // are disallowed for the variable. This default requirement makes sure that optional variables can be matched at all
                // and that the generating-matching-combination of URLs unambiguous, i.e. the params used for generating the URL are
                // the same that will be matched. Example: new Route('/{page}.{_format}', array('_format' => 'html'))
                // If {page} would also match the separating dot, {_format} would never match as {page} will eagerly consume everything.
                // Also even if {_format} was not optional the requirement prevents that {page} matches something that was originally
                // part of {_format} when generating the URL, e.g. _format = 'mobile.html'.
                $nextSeparator = self::findNextSeparator($followingPattern);
                $regexp = sprintf('[^%s%s]+', preg_quote($defaultSeparator, self::REGEX_DELIMITER), $defaultSeparator !== $nextSeparator && '' !== $nextSeparator ? preg_quote($nextSeparator, self::REGEX_DELIMITER) : '');
                if ('' !== $nextSeparator && !preg_match('#^\\{\\w+\\}#', $followingPattern) || '' === $followingPattern) {
                    // When we have a separator, which is disallowed for the variable, we can optimize the regex with a possessive
                    // quantifier. This prevents useless backtracking of PCRE and improves performance by 20% for matching those patterns.
                    // Given the above example, there is no point in backtracking into {page} (that forbids the dot) when a dot must follow
                    // after it. This optimization cannot be applied when the next char is no real separator or when the next variable is
                    // directly adjacent, e.g. '/{x}{y}'.
                    $regexp .= '+';
                }
            }
            $tokens[] = array('variable', $isSeparator ? $precedingChar : '', $regexp, $varName);
            $variables[] = $varName;
        }
        if ($pos < strlen($pattern)) {
            $tokens[] = array('text', substr($pattern, $pos));
        }
        // find the first optional token
        $firstOptional = INF;
        if (!$isHost) {
            for ($i = count($tokens) - 1; $i >= 0; $i--) {
                $token = $tokens[$i];
                if ('variable' === $token[0] && $route->hasDefault($token[3])) {
                    $firstOptional = $i;
                } else {
                    break;
                }
            }
        }
        // compute the matching regexp
        $regexp = '';
        for ($i = 0, $nbToken = count($tokens); $i < $nbToken; $i++) {
            $regexp .= self::computeRegexp($tokens, $i, $firstOptional);
        }
        return array('staticPrefix' => 'text' === $tokens[0][0] ? $tokens[0][1] : '', 'regex' => self::REGEX_DELIMITER . '^' . $regexp . '$' . self::REGEX_DELIMITER . 's', 'tokens' => array_reverse($tokens), 'variables' => $variables);
    }
    /**
     * Returns the next static character in the Route pattern that will serve as a separator.
     *
     * @param string $pattern The route pattern
     *
     * @return string The next static character that functions as separator (or empty string when none available)
     */
    private static function findNextSeparator($pattern)
    {
        if ('' == $pattern) {
            // return empty string if pattern is empty or false (false which can be returned by substr)
            return '';
        }
        // first remove all placeholders from the pattern so we can find the next real static character
        $pattern = preg_replace('#\\{\\w+\\}#', '', $pattern);
        return isset($pattern[0]) && false !== strpos(static::SEPARATORS, $pattern[0]) ? $pattern[0] : '';
    }
    /**
     * Computes the regexp used to match a specific token. It can be static text or a subpattern.
     *
     * @param array   $tokens        The route tokens
     * @param integer $index         The index of the current token
     * @param integer $firstOptional The index of the first optional token
     *
     * @return string The regexp pattern for a single token
     */
    private static function computeRegexp(array $tokens, $index, $firstOptional)
    {
        $token = $tokens[$index];
        if ('text' === $token[0]) {
            // Text tokens
            return preg_quote($token[1], self::REGEX_DELIMITER);
        } else {
            // Variable tokens
            if (0 === $index && 0 === $firstOptional) {
                // When the only token is an optional variable token, the separator is required
                return sprintf('%s(?P<%s>%s)?', preg_quote($token[1], self::REGEX_DELIMITER), $token[3], $token[2]);
            } else {
                $regexp = sprintf('%s(?P<%s>%s)', preg_quote($token[1], self::REGEX_DELIMITER), $token[3], $token[2]);
                if ($index >= $firstOptional) {
                    // Enclose each optional token in a subpattern to make it optional.
                    // "?:" means it is non-capturing, i.e. the portion of the subject string that
                    // matched the optional subpattern is not passed back.
                    $regexp = "(?:{$regexp}";
                    $nbTokens = count($tokens);
                    if ($nbTokens - 1 == $index) {
                        // Close the optional subpatterns
                        $regexp .= str_repeat(')?', $nbTokens - $firstOptional - (0 === $firstOptional ? 1 : 0));
                    }
                }
                return $regexp;
            }
        }
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Routing;

/**
 * CompiledRoutes are returned by the RouteCompiler class.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CompiledRoute
{
    private $variables;
    private $tokens;
    private $staticPrefix;
    private $regex;
    private $pathVariables;
    private $hostVariables;
    private $hostRegex;
    private $hostTokens;
    /**
     * Constructor.
     *
     * @param string      $staticPrefix       The static prefix of the compiled route
     * @param string      $regex              The regular expression to use to match this route
     * @param array       $tokens             An array of tokens to use to generate URL for this route
     * @param array       $pathVariables      An array of path variables
     * @param string|null $hostRegex          Host regex
     * @param array       $hostTokens         Host tokens
     * @param array       $hostVariables      An array of host variables
     * @param array       $variables          An array of variables (variables defined in the path and in the host patterns)
     */
    public function __construct($staticPrefix, $regex, array $tokens, array $pathVariables, $hostRegex = null, array $hostTokens = array(), array $hostVariables = array(), array $variables = array())
    {
        $this->staticPrefix = (string) $staticPrefix;
        $this->regex = $regex;
        $this->tokens = $tokens;
        $this->pathVariables = $pathVariables;
        $this->hostRegex = $hostRegex;
        $this->hostTokens = $hostTokens;
        $this->hostVariables = $hostVariables;
        $this->variables = $variables;
    }
    /**
     * Returns the static prefix.
     *
     * @return string The static prefix
     */
    public function getStaticPrefix()
    {
        return $this->staticPrefix;
    }
    /**
     * Returns the regex.
     *
     * @return string The regex
     */
    public function getRegex()
    {
        return $this->regex;
    }
    /**
     * Returns the host regex
     *
     * @return string|null The host regex or null
     */
    public function getHostRegex()
    {
        return $this->hostRegex;
    }
    /**
     * Returns the tokens.
     *
     * @return array The tokens
     */
    public function getTokens()
    {
        return $this->tokens;
    }
    /**
     * Returns the host tokens.
     *
     * @return array The tokens
     */
    public function getHostTokens()
    {
        return $this->hostTokens;
    }
    /**
     * Returns the variables.
     *
     * @return array The variables
     */
    public function getVariables()
    {
        return $this->variables;
    }
    /**
     * Returns the path variables.
     *
     * @return array The variables
     */
    public function getPathVariables()
    {
        return $this->pathVariables;
    }
    /**
     * Returns the host variables.
     *
     * @return array The variables
     */
    public function getHostVariables()
    {
        return $this->hostVariables;
    }
}
namespace Illuminate\Support\Facades;

class View extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'view';
    }
}
namespace Illuminate\Support\Contracts;

interface RenderableInterface
{
    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render();
}
namespace Illuminate\View;

use ArrayAccess;
use Illuminate\View\Engines\EngineInterface;
use Illuminate\Support\Contracts\RenderableInterface as Renderable;
class View implements ArrayAccess, Renderable
{
    /**
     * The view environment instance.
     *
     * @var Illuminate\View\Environment
     */
    protected $environment;
    /**
     * The engine implementation.
     *
     * @var Illuminate\View\Engines\EngineInterface
     */
    protected $engine;
    /**
     * The name of the view.
     *
     * @var string
     */
    protected $view;
    /**
     * The array of view data.
     *
     * @var array
     */
    protected $data;
    /**
     * The path to the view file.
     *
     * @var string
     */
    protected $path;
    /**
     * Create a new view instance.
     *
     * @param  Illuminate\View\Environment  $environment
     * @param  Illuminate\View\Engines\EngineInterface  $engine
     * @param  string  $view
     * @param  string  $path
     * @param  array   $data
     * @return void
     */
    public function __construct(Environment $environment, EngineInterface $engine, $view, $path, array $data = array())
    {
        $this->view = $view;
        $this->path = $path;
        $this->data = $data;
        $this->engine = $engine;
        $this->environment = $environment;
    }
    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function render()
    {
        $env = $this->environment;
        // We will keep track of the amount of views being rendered so we can flush
        // the section after the complete rendering operation is done. This will
        // clear out the sections for any separate views that may be rendered.
        $env->incrementRender();
        $env->callComposer($this);
        $contents = trim($this->getContents());
        // Once we've finished rendering the view, we'll decrement the render count
        // then if we are at the bottom of the stack we'll flush out sections as
        // they might interfere with totally separate view's evaluations later.
        $env->decrementRender();
        if ($env->doneRendering()) {
            $env->flushSections();
        }
        return $contents;
    }
    /**
     * Get the evaluated contents of the view.
     *
     * @return string
     */
    protected function getContents()
    {
        return $this->engine->get($this->path, $this->gatherData());
    }
    /**
     * Get the data bound to the view instance.
     *
     * @return array
     */
    protected function gatherData()
    {
        $data = array_merge($this->environment->getShared(), $this->data);
        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }
        return $data;
    }
    /**
     * Add a piece of data to the view.
     *
     * @param  string|array  $key
     * @param  mixed   $value
     * @return Illuminate\View\View
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }
    /**
     * Add a view instance to the view data.
     *
     * @param  string  $key
     * @param  string  $view
     * @param  array   $data
     * @return Illuminate\View\View
     */
    public function nest($key, $view, array $data = array())
    {
        return $this->with($key, $this->environment->make($view, $data));
    }
    /**
     * Get the view environment instance.
     *
     * @return Illuminate\View\Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
    /**
     * Get the view's rendering engine.
     *
     * @return Illuminate\View\Engines\EngineInterface
     */
    public function getEngine()
    {
        return $this->engine;
    }
    /**
     * Get the name of the view.
     *
     * @return string
     */
    public function getName()
    {
        return $this->view;
    }
    /**
     * Get the array of view data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * Get the path to the view file.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    /**
     * Set the path to the view.
     *
     * @param  string  $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }
    /**
     * Determine if a piece of data is bound.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->data);
    }
    /**
     * Get a piece of bound data to the view.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->data[$key];
    }
    /**
     * Set a piece of data on the view.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->with($key, $value);
    }
    /**
     * Unset a piece of data from the view.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }
    /**
     * Get a piece of data from the view.
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->data[$key];
    }
    /**
     * Set a piece of data on the view.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->with($key, $value);
    }
    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
namespace Illuminate\View\Engines;

interface EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array   $data
     * @return string
     */
    public function get($path, array $data = array());
}
namespace Illuminate\View\Engines;

use Illuminate\View\Exception;
use Illuminate\View\Environment;
class PhpEngine implements EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array   $data
     * @return string
     */
    public function get($path, array $data = array())
    {
        return $this->evaluatePath($path, $data);
    }
    /**
     * Get the evaluated contents of the view at the given path.
     *
     * @param  string  $path
     * @param  array   $data
     * @return string
     */
    protected function evaluatePath($__path, $__data)
    {
        ob_start();
        extract($__data);
        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        try {
            include $__path;
        } catch (\Exception $e) {
            $this->handleViewException($e);
        }
        return ob_get_clean();
    }
    /**
     * Handle a view exception.
     *
     * @param  Exception  $e
     * @return void
     */
    protected function handleViewException($e)
    {
        ob_get_clean();
        throw $e;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

/**
 * Response represents an HTTP response.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class Response
{
    /**
     * @var \Symfony\Component\HttpFoundation\ResponseHeaderBag
     */
    public $headers;
    /**
     * @var string
     */
    protected $content;
    /**
     * @var string
     */
    protected $version;
    /**
     * @var integer
     */
    protected $statusCode;
    /**
     * @var string
     */
    protected $statusText;
    /**
     * @var string
     */
    protected $charset;
    /**
     * Status codes translation table.
     *
     * The list of codes is complete according to the
     * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
     * (last updated 2012-02-13).
     *
     * Unless otherwise noted, the status code is defined in RFC2616.
     *
     * @var array
     */
    public static $statusTexts = array(100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Reserved', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Reserved for WebDAV advanced collections expired proposal', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates (Experimental)', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required');
    /**
     * Constructor.
     *
     * @param string  $content The response content
     * @param integer $status  The response status code
     * @param array   $headers An array of response headers
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     *
     * @api
     */
    public function __construct($content = '', $status = 200, $headers = array())
    {
        $this->headers = new ResponseHeaderBag($headers);
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setProtocolVersion('1.0');
        if (!$this->headers->has('Date')) {
            $this->setDate(new \DateTime(null, new \DateTimeZone('UTC')));
        }
    }
    /**
     * Factory method for chainability
     *
     * Example:
     *
     *     return Response::create($body, 200)
     *         ->setSharedMaxAge(300);
     *
     * @param string  $content The response content
     * @param integer $status  The response status code
     * @param array   $headers An array of response headers
     *
     * @return Response
     */
    public static function create($content = '', $status = 200, $headers = array())
    {
        return new static($content, $status, $headers);
    }
    /**
     * Returns the Response as an HTTP string.
     *
     * The string representation of the Response is the same as the
     * one that will be sent to the client only if the prepare() method
     * has been called before.
     *
     * @return string The Response as an HTTP string
     *
     * @see prepare()
     */
    public function __toString()
    {
        return sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . '
' . $this->headers . '
' . $this->getContent();
    }
    /**
     * Clones the current Response instance.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
    /**
     * Prepares the Response before it is sent to the client.
     *
     * This method tweaks the Response to ensure that it is
     * compliant with RFC 2616. Most of the changes are based on
     * the Request that is "associated" with this Response.
     *
     * @param Request $request A Request instance
     *
     * @return Response The current response.
     */
    public function prepare(Request $request)
    {
        $headers = $this->headers;
        if ($this->isInformational() || in_array($this->statusCode, array(204, 304))) {
            $this->setContent(null);
        }
        // Content-type based on the Request
        if (!$headers->has('Content-Type')) {
            $format = $request->getRequestFormat();
            if (null !== $format && ($mimeType = $request->getMimeType($format))) {
                $headers->set('Content-Type', $mimeType);
            }
        }
        // Fix Content-Type
        $charset = $this->charset ?: 'UTF-8';
        if (!$headers->has('Content-Type')) {
            $headers->set('Content-Type', 'text/html; charset=' . $charset);
        } elseif (0 === strpos($headers->get('Content-Type'), 'text/') && false === strpos($headers->get('Content-Type'), 'charset')) {
            // add the charset
            $headers->set('Content-Type', $headers->get('Content-Type') . '; charset=' . $charset);
        }
        // Fix Content-Length
        if ($headers->has('Transfer-Encoding')) {
            $headers->remove('Content-Length');
        }
        if ($request->isMethod('HEAD')) {
            // cf. RFC2616 14.13
            $length = $headers->get('Content-Length');
            $this->setContent(null);
            if ($length) {
                $headers->set('Content-Length', $length);
            }
        }
        // Fix protocol
        if ('HTTP/1.0' != $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1');
        }
        // Check if we need to send extra expire info headers
        if ('1.0' == $this->getProtocolVersion() && 'no-cache' == $this->headers->get('Cache-Control')) {
            $this->headers->set('pragma', 'no-cache');
            $this->headers->set('expires', -1);
        }
        /**
         * Check if we need to remove Cache-Control for ssl encrypted downloads when using IE < 9
         * @link http://support.microsoft.com/kb/323308
         */
        if (false !== stripos($this->headers->get('Content-Disposition'), 'attachment') && preg_match('/MSIE (.*?);/i', $request->server->get('HTTP_USER_AGENT'), $match) == 1 && true === $request->isSecure()) {
            if (intval(preg_replace('/(MSIE )(.*?);/', '$2', $match[0])) < 9) {
                $this->headers->remove('Cache-Control');
            }
        }
        return $this;
    }
    /**
     * Sends HTTP headers.
     *
     * @return Response
     */
    public function sendHeaders()
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }
        // status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));
        // headers
        foreach ($this->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }
        // cookies
        foreach ($this->headers->getCookies() as $cookie) {
            setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }
        return $this;
    }
    /**
     * Sends content for the current web response.
     *
     * @return Response
     */
    public function sendContent()
    {
        echo $this->content;
        return $this;
    }
    /**
     * Sends HTTP headers and content.
     *
     * @return Response
     *
     * @api
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            // ob_get_level() never returns 0 on some Windows configurations, so if
            // the level is the same two times in a row, the loop should be stopped.
            $previous = null;
            $obStatus = ob_get_status(1);
            while (($level = ob_get_level()) > 0 && $level !== $previous) {
                $previous = $level;
                if ($obStatus[$level - 1] && isset($obStatus[$level - 1]['del']) && $obStatus[$level - 1]['del']) {
                    ob_end_flush();
                }
            }
            flush();
        }
        return $this;
    }
    /**
     * Sets the response content.
     *
     * Valid types are strings, numbers, and objects that implement a __toString() method.
     *
     * @param mixed $content
     *
     * @return Response
     *
     * @throws \UnexpectedValueException
     *
     * @api
     */
    public function setContent($content)
    {
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
            throw new \UnexpectedValueException('The Response content must be a string or object implementing __toString(), "' . gettype($content) . '" given.');
        }
        $this->content = (string) $content;
        return $this;
    }
    /**
     * Gets the current response content.
     *
     * @return string Content
     *
     * @api
     */
    public function getContent()
    {
        return $this->content;
    }
    /**
     * Sets the HTTP protocol version (1.0 or 1.1).
     *
     * @param string $version The HTTP protocol version
     *
     * @return Response
     *
     * @api
     */
    public function setProtocolVersion($version)
    {
        $this->version = $version;
        return $this;
    }
    /**
     * Gets the HTTP protocol version.
     *
     * @return string The HTTP protocol version
     *
     * @api
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }
    /**
     * Sets the response status code.
     *
     * @param integer $code HTTP status code
     * @param mixed   $text HTTP status text
     *
     * If the status text is null it will be automatically populated for the known
     * status codes and left empty otherwise.
     *
     * @return Response
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     *
     * @api
     */
    public function setStatusCode($code, $text = null)
    {
        $this->statusCode = $code = (int) $code;
        if ($this->isInvalid()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }
        if (null === $text) {
            $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : '';
            return $this;
        }
        if (false === $text) {
            $this->statusText = '';
            return $this;
        }
        $this->statusText = $text;
        return $this;
    }
    /**
     * Retrieves the status code for the current web response.
     *
     * @return integer Status code
     *
     * @api
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    /**
     * Sets the response charset.
     *
     * @param string $charset Character set
     *
     * @return Response
     *
     * @api
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }
    /**
     * Retrieves the response charset.
     *
     * @return string Character set
     *
     * @api
     */
    public function getCharset()
    {
        return $this->charset;
    }
    /**
     * Returns true if the response is worth caching under any circumstance.
     *
     * Responses marked "private" with an explicit Cache-Control directive are
     * considered uncacheable.
     *
     * Responses with neither a freshness lifetime (Expires, max-age) nor cache
     * validator (Last-Modified, ETag) are considered uncacheable.
     *
     * @return Boolean true if the response is worth caching, false otherwise
     *
     * @api
     */
    public function isCacheable()
    {
        if (!in_array($this->statusCode, array(200, 203, 300, 301, 302, 404, 410))) {
            return false;
        }
        if ($this->headers->hasCacheControlDirective('no-store') || $this->headers->getCacheControlDirective('private')) {
            return false;
        }
        return $this->isValidateable() || $this->isFresh();
    }
    /**
     * Returns true if the response is "fresh".
     *
     * Fresh responses may be served from cache without any interaction with the
     * origin. A response is considered fresh when it includes a Cache-Control/max-age
     * indicator or Expires header and the calculated age is less than the freshness lifetime.
     *
     * @return Boolean true if the response is fresh, false otherwise
     *
     * @api
     */
    public function isFresh()
    {
        return $this->getTtl() > 0;
    }
    /**
     * Returns true if the response includes headers that can be used to validate
     * the response with the origin server using a conditional GET request.
     *
     * @return Boolean true if the response is validateable, false otherwise
     *
     * @api
     */
    public function isValidateable()
    {
        return $this->headers->has('Last-Modified') || $this->headers->has('ETag');
    }
    /**
     * Marks the response as "private".
     *
     * It makes the response ineligible for serving other clients.
     *
     * @return Response
     *
     * @api
     */
    public function setPrivate()
    {
        $this->headers->removeCacheControlDirective('public');
        $this->headers->addCacheControlDirective('private');
        return $this;
    }
    /**
     * Marks the response as "public".
     *
     * It makes the response eligible for serving other clients.
     *
     * @return Response
     *
     * @api
     */
    public function setPublic()
    {
        $this->headers->addCacheControlDirective('public');
        $this->headers->removeCacheControlDirective('private');
        return $this;
    }
    /**
     * Returns true if the response must be revalidated by caches.
     *
     * This method indicates that the response must not be served stale by a
     * cache in any circumstance without first revalidating with the origin.
     * When present, the TTL of the response should not be overridden to be
     * greater than the value provided by the origin.
     *
     * @return Boolean true if the response must be revalidated by a cache, false otherwise
     *
     * @api
     */
    public function mustRevalidate()
    {
        return $this->headers->hasCacheControlDirective('must-revalidate') || $this->headers->has('proxy-revalidate');
    }
    /**
     * Returns the Date header as a DateTime instance.
     *
     * @return \DateTime A \DateTime instance
     *
     * @throws \RuntimeException When the header is not parseable
     *
     * @api
     */
    public function getDate()
    {
        return $this->headers->getDate('Date', new \DateTime());
    }
    /**
     * Sets the Date header.
     *
     * @param \DateTime $date A \DateTime instance
     *
     * @return Response
     *
     * @api
     */
    public function setDate(\DateTime $date)
    {
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->headers->set('Date', $date->format('D, d M Y H:i:s') . ' GMT');
        return $this;
    }
    /**
     * Returns the age of the response.
     *
     * @return integer The age of the response in seconds
     */
    public function getAge()
    {
        if (null !== ($age = $this->headers->get('Age'))) {
            return (int) $age;
        }
        return max(time() - $this->getDate()->format('U'), 0);
    }
    /**
     * Marks the response stale by setting the Age header to be equal to the maximum age of the response.
     *
     * @return Response
     *
     * @api
     */
    public function expire()
    {
        if ($this->isFresh()) {
            $this->headers->set('Age', $this->getMaxAge());
        }
        return $this;
    }
    /**
     * Returns the value of the Expires header as a DateTime instance.
     *
     * @return \DateTime|null A DateTime instance or null if the header does not exist
     *
     * @api
     */
    public function getExpires()
    {
        try {
            return $this->headers->getDate('Expires');
        } catch (\RuntimeException $e) {
            // according to RFC 2616 invalid date formats (e.g. "0" and "-1") must be treated as in the past
            return \DateTime::createFromFormat(DATE_RFC2822, 'Sat, 01 Jan 00 00:00:00 +0000');
        }
    }
    /**
     * Sets the Expires HTTP header with a DateTime instance.
     *
     * Passing null as value will remove the header.
     *
     * @param \DateTime|null $date A \DateTime instance or null to remove the header
     *
     * @return Response
     *
     * @api
     */
    public function setExpires(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Expires');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Expires', $date->format('D, d M Y H:i:s') . ' GMT');
        }
        return $this;
    }
    /**
     * Returns the number of seconds after the time specified in the response's Date
     * header when the the response should no longer be considered fresh.
     *
     * First, it checks for a s-maxage directive, then a max-age directive, and then it falls
     * back on an expires header. It returns null when no maximum age can be established.
     *
     * @return integer|null Number of seconds
     *
     * @api
     */
    public function getMaxAge()
    {
        if ($this->headers->hasCacheControlDirective('s-maxage')) {
            return (int) $this->headers->getCacheControlDirective('s-maxage');
        }
        if ($this->headers->hasCacheControlDirective('max-age')) {
            return (int) $this->headers->getCacheControlDirective('max-age');
        }
        if (null !== $this->getExpires()) {
            return $this->getExpires()->format('U') - $this->getDate()->format('U');
        }
        return null;
    }
    /**
     * Sets the number of seconds after which the response should no longer be considered fresh.
     *
     * This methods sets the Cache-Control max-age directive.
     *
     * @param integer $value Number of seconds
     *
     * @return Response
     *
     * @api
     */
    public function setMaxAge($value)
    {
        $this->headers->addCacheControlDirective('max-age', $value);
        return $this;
    }
    /**
     * Sets the number of seconds after which the response should no longer be considered fresh by shared caches.
     *
     * This methods sets the Cache-Control s-maxage directive.
     *
     * @param integer $value Number of seconds
     *
     * @return Response
     *
     * @api
     */
    public function setSharedMaxAge($value)
    {
        $this->setPublic();
        $this->headers->addCacheControlDirective('s-maxage', $value);
        return $this;
    }
    /**
     * Returns the response's time-to-live in seconds.
     *
     * It returns null when no freshness information is present in the response.
     *
     * When the responses TTL is <= 0, the response may not be served from cache without first
     * revalidating with the origin.
     *
     * @return integer|null The TTL in seconds
     *
     * @api
     */
    public function getTtl()
    {
        if (null !== ($maxAge = $this->getMaxAge())) {
            return $maxAge - $this->getAge();
        }
        return null;
    }
    /**
     * Sets the response's time-to-live for shared caches.
     *
     * This method adjusts the Cache-Control/s-maxage directive.
     *
     * @param integer $seconds Number of seconds
     *
     * @return Response
     *
     * @api
     */
    public function setTtl($seconds)
    {
        $this->setSharedMaxAge($this->getAge() + $seconds);
        return $this;
    }
    /**
     * Sets the response's time-to-live for private/client caches.
     *
     * This method adjusts the Cache-Control/max-age directive.
     *
     * @param integer $seconds Number of seconds
     *
     * @return Response
     *
     * @api
     */
    public function setClientTtl($seconds)
    {
        $this->setMaxAge($this->getAge() + $seconds);
        return $this;
    }
    /**
     * Returns the Last-Modified HTTP header as a DateTime instance.
     *
     * @return \DateTime|null A DateTime instance or null if the header does not exist
     *
     * @throws \RuntimeException When the HTTP header is not parseable
     *
     * @api
     */
    public function getLastModified()
    {
        return $this->headers->getDate('Last-Modified');
    }
    /**
     * Sets the Last-Modified HTTP header with a DateTime instance.
     *
     * Passing null as value will remove the header.
     *
     * @param \DateTime|null $date A \DateTime instance or null to remove the header
     *
     * @return Response
     *
     * @api
     */
    public function setLastModified(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Last-Modified');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
        }
        return $this;
    }
    /**
     * Returns the literal value of the ETag HTTP header.
     *
     * @return string|null The ETag HTTP header or null if it does not exist
     *
     * @api
     */
    public function getEtag()
    {
        return $this->headers->get('ETag');
    }
    /**
     * Sets the ETag value.
     *
     * @param string|null $etag The ETag unique identifier or null to remove the header
     * @param Boolean     $weak Whether you want a weak ETag or not
     *
     * @return Response
     *
     * @api
     */
    public function setEtag($etag = null, $weak = false)
    {
        if (null === $etag) {
            $this->headers->remove('Etag');
        } else {
            if (0 !== strpos($etag, '"')) {
                $etag = '"' . $etag . '"';
            }
            $this->headers->set('ETag', (true === $weak ? 'W/' : '') . $etag);
        }
        return $this;
    }
    /**
     * Sets the response's cache headers (validation and/or expiration).
     *
     * Available options are: etag, last_modified, max_age, s_maxage, private, and public.
     *
     * @param array $options An array of cache options
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function setCache(array $options)
    {
        if ($diff = array_diff(array_keys($options), array('etag', 'last_modified', 'max_age', 's_maxage', 'private', 'public'))) {
            throw new \InvalidArgumentException(sprintf('Response does not support the following options: "%s".', implode('", "', array_values($diff))));
        }
        if (isset($options['etag'])) {
            $this->setEtag($options['etag']);
        }
        if (isset($options['last_modified'])) {
            $this->setLastModified($options['last_modified']);
        }
        if (isset($options['max_age'])) {
            $this->setMaxAge($options['max_age']);
        }
        if (isset($options['s_maxage'])) {
            $this->setSharedMaxAge($options['s_maxage']);
        }
        if (isset($options['public'])) {
            if ($options['public']) {
                $this->setPublic();
            } else {
                $this->setPrivate();
            }
        }
        if (isset($options['private'])) {
            if ($options['private']) {
                $this->setPrivate();
            } else {
                $this->setPublic();
            }
        }
        return $this;
    }
    /**
     * Modifies the response so that it conforms to the rules defined for a 304 status code.
     *
     * This sets the status, removes the body, and discards any headers
     * that MUST NOT be included in 304 responses.
     *
     * @return Response
     *
     * @see http://tools.ietf.org/html/rfc2616#section-10.3.5
     *
     * @api
     */
    public function setNotModified()
    {
        $this->setStatusCode(304);
        $this->setContent(null);
        // remove headers that MUST NOT be included with 304 Not Modified responses
        foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header) {
            $this->headers->remove($header);
        }
        return $this;
    }
    /**
     * Returns true if the response includes a Vary header.
     *
     * @return Boolean true if the response includes a Vary header, false otherwise
     *
     * @api
     */
    public function hasVary()
    {
        return null !== $this->headers->get('Vary');
    }
    /**
     * Returns an array of header names given in the Vary header.
     *
     * @return array An array of Vary names
     *
     * @api
     */
    public function getVary()
    {
        if (!($vary = $this->headers->get('Vary'))) {
            return array();
        }
        return is_array($vary) ? $vary : preg_split('/[\\s,]+/', $vary);
    }
    /**
     * Sets the Vary header.
     *
     * @param string|array $headers
     * @param Boolean      $replace Whether to replace the actual value of not (true by default)
     *
     * @return Response
     *
     * @api
     */
    public function setVary($headers, $replace = true)
    {
        $this->headers->set('Vary', $headers, $replace);
        return $this;
    }
    /**
     * Determines if the Response validators (ETag, Last-Modified) match
     * a conditional value specified in the Request.
     *
     * If the Response is not modified, it sets the status code to 304 and
     * removes the actual content by calling the setNotModified() method.
     *
     * @param Request $request A Request instance
     *
     * @return Boolean true if the Response validators match the Request, false otherwise
     *
     * @api
     */
    public function isNotModified(Request $request)
    {
        if (!$request->isMethodSafe()) {
            return false;
        }
        $lastModified = $request->headers->get('If-Modified-Since');
        $notModified = false;
        if ($etags = $request->getEtags()) {
            $notModified = (in_array($this->getEtag(), $etags) || in_array('*', $etags)) && (!$lastModified || $this->headers->get('Last-Modified') == $lastModified);
        } elseif ($lastModified) {
            $notModified = $lastModified == $this->headers->get('Last-Modified');
        }
        if ($notModified) {
            $this->setNotModified();
        }
        return $notModified;
    }
    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
    /**
     * Is response invalid?
     *
     * @return Boolean
     *
     * @api
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }
    /**
     * Is response informative?
     *
     * @return Boolean
     *
     * @api
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }
    /**
     * Is response successful?
     *
     * @return Boolean
     *
     * @api
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    /**
     * Is the response a redirect?
     *
     * @return Boolean
     *
     * @api
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }
    /**
     * Is there a client error?
     *
     * @return Boolean
     *
     * @api
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }
    /**
     * Was there a server side error?
     *
     * @return Boolean
     *
     * @api
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }
    /**
     * Is the response OK?
     *
     * @return Boolean
     *
     * @api
     */
    public function isOk()
    {
        return 200 === $this->statusCode;
    }
    /**
     * Is the response forbidden?
     *
     * @return Boolean
     *
     * @api
     */
    public function isForbidden()
    {
        return 403 === $this->statusCode;
    }
    /**
     * Is the response a not found error?
     *
     * @return Boolean
     *
     * @api
     */
    public function isNotFound()
    {
        return 404 === $this->statusCode;
    }
    /**
     * Is the response a redirect of some form?
     *
     * @param string $location
     *
     * @return Boolean
     *
     * @api
     */
    public function isRedirect($location = null)
    {
        return in_array($this->statusCode, array(201, 301, 302, 303, 307, 308)) && (null === $location ?: $location == $this->headers->get('Location'));
    }
    /**
     * Is the response empty?
     *
     * @return Boolean
     *
     * @api
     */
    public function isEmpty()
    {
        return in_array($this->statusCode, array(201, 204, 304));
    }
}
namespace Illuminate\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\RenderableInterface;
class Response extends \Symfony\Component\HttpFoundation\Response
{
    /**
     * The original content of the response.
     *
     * @var mixed
     */
    public $original;
    /**
     * Add a cookie to the response.
     *
     * @param  Symfony\Component\HttpFoundation\Cookie  $cookie
     * @return Illuminate\Http\Response
     */
    public function withCookie(Cookie $cookie)
    {
        $this->headers->setCookie($cookie);
        return $this;
    }
    /**
     * Set the content on the response.
     *
     * @param  mixed  $content
     * @return void
     */
    public function setContent($content)
    {
        $this->original = $content;
        // If the content is "JSONable" we will set the appropriate header and convert
        // the content to JSON. This is useful when returning something like models
        // from routes that will be automatically transformed to their JSON form.
        if ($content instanceof JsonableInterface) {
            $this->headers->set('Content-Type', 'application/json');
            $content = $content->toJson();
        } elseif ($content instanceof RenderableInterface) {
            $content = $content->render();
        }
        return parent::setContent($content);
    }
    /**
     * Get the original response content.
     *
     * @return mixed
     */
    public function getOriginalContent()
    {
        return $this->original;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

/**
 * ResponseHeaderBag is a container for Response HTTP headers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class ResponseHeaderBag extends HeaderBag
{
    const COOKIES_FLAT = 'flat';
    const COOKIES_ARRAY = 'array';
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';
    /**
     * @var array
     */
    protected $computedCacheControl = array();
    /**
     * @var array
     */
    protected $cookies = array();
    /**
     * @var array
     */
    protected $headerNames = array();
    /**
     * Constructor.
     *
     * @param array $headers An array of HTTP headers
     *
     * @api
     */
    public function __construct(array $headers = array())
    {
        parent::__construct($headers);
        if (!isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }
    }
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $cookies = '';
        foreach ($this->getCookies() as $cookie) {
            $cookies .= 'Set-Cookie: ' . $cookie . '
';
        }
        ksort($this->headerNames);
        return parent::__toString() . $cookies;
    }
    /**
     * Returns the headers, with original capitalizations.
     *
     * @return array An array of headers
     */
    public function allPreserveCase()
    {
        return array_combine($this->headerNames, $this->headers);
    }
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function replace(array $headers = array())
    {
        $this->headerNames = array();
        parent::replace($headers);
        if (!isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }
    }
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function set($key, $values, $replace = true)
    {
        parent::set($key, $values, $replace);
        $uniqueKey = strtr(strtolower($key), '_', '-');
        $this->headerNames[$uniqueKey] = $key;
        // ensure the cache-control header has sensible defaults
        if (in_array($uniqueKey, array('cache-control', 'etag', 'last-modified', 'expires'))) {
            $computed = $this->computeCacheControlValue();
            $this->headers['cache-control'] = array($computed);
            $this->headerNames['cache-control'] = 'Cache-Control';
            $this->computedCacheControl = $this->parseCacheControl($computed);
        }
    }
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function remove($key)
    {
        parent::remove($key);
        $uniqueKey = strtr(strtolower($key), '_', '-');
        unset($this->headerNames[$uniqueKey]);
        if ('cache-control' === $uniqueKey) {
            $this->computedCacheControl = array();
        }
    }
    /**
     * {@inheritdoc}
     */
    public function hasCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl);
    }
    /**
     * {@inheritdoc}
     */
    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl) ? $this->computedCacheControl[$key] : null;
    }
    /**
     * Sets a cookie.
     *
     * @param Cookie $cookie
     *
     * @api
     */
    public function setCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;
    }
    /**
     * Removes a cookie from the array, but does not unset it in the browser
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     *
     * @api
     */
    public function removeCookie($name, $path = '/', $domain = null)
    {
        if (null === $path) {
            $path = '/';
        }
        unset($this->cookies[$domain][$path][$name]);
        if (empty($this->cookies[$domain][$path])) {
            unset($this->cookies[$domain][$path]);
            if (empty($this->cookies[$domain])) {
                unset($this->cookies[$domain]);
            }
        }
    }
    /**
     * Returns an array with all cookies
     *
     * @param string $format
     *
     * @throws \InvalidArgumentException When the $format is invalid
     *
     * @return array
     *
     * @api
     */
    public function getCookies($format = self::COOKIES_FLAT)
    {
        if (!in_array($format, array(self::COOKIES_FLAT, self::COOKIES_ARRAY))) {
            throw new \InvalidArgumentException(sprintf('Format "%s" invalid (%s).', $format, implode(', ', array(self::COOKIES_FLAT, self::COOKIES_ARRAY))));
        }
        if (self::COOKIES_ARRAY === $format) {
            return $this->cookies;
        }
        $flattenedCookies = array();
        foreach ($this->cookies as $path) {
            foreach ($path as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattenedCookies[] = $cookie;
                }
            }
        }
        return $flattenedCookies;
    }
    /**
     * Clears a cookie in the browser
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     *
     * @api
     */
    public function clearCookie($name, $path = '/', $domain = null)
    {
        $this->setCookie(new Cookie($name, null, 1, $path, $domain));
    }
    /**
     * Generates a HTTP Content-Disposition field-value.
     *
     * @param string $disposition      One of "inline" or "attachment"
     * @param string $filename         A unicode string
     * @param string $filenameFallback A string containing only ASCII characters that
     *                                 is semantically equivalent to $filename. If the filename is already ASCII,
     *                                 it can be omitted, or just copied from $filename
     *
     * @return string A string suitable for use as a Content-Disposition field-value.
     *
     * @throws \InvalidArgumentException
     * @see RFC 6266
     */
    public function makeDisposition($disposition, $filename, $filenameFallback = '')
    {
        if (!in_array($disposition, array(self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE))) {
            throw new \InvalidArgumentException(sprintf('The disposition must be either "%s" or "%s".', self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE));
        }
        if ('' == $filenameFallback) {
            $filenameFallback = $filename;
        }
        // filenameFallback is not ASCII.
        if (!preg_match('/^[\\x20-\\x7e]*$/', $filenameFallback)) {
            throw new \InvalidArgumentException('The filename fallback must only contain ASCII characters.');
        }
        // percent characters aren't safe in fallback.
        if (false !== strpos($filenameFallback, '%')) {
            throw new \InvalidArgumentException('The filename fallback cannot contain the "%" character.');
        }
        // path separators aren't allowed in either.
        if (false !== strpos($filename, '/') || false !== strpos($filename, '\\') || false !== strpos($filenameFallback, '/') || false !== strpos($filenameFallback, '\\')) {
            throw new \InvalidArgumentException('The filename and the fallback cannot contain the "/" and "\\" characters.');
        }
        $output = sprintf('%s; filename="%s"', $disposition, str_replace('"', '\\"', $filenameFallback));
        if ($filename !== $filenameFallback) {
            $output .= sprintf('; filename*=utf-8\'\'%s', rawurlencode($filename));
        }
        return $output;
    }
    /**
     * Returns the calculated value of the cache-control header.
     *
     * This considers several other headers and calculates or modifies the
     * cache-control header to a sensible, conservative value.
     *
     * @return string
     */
    protected function computeCacheControlValue()
    {
        if (!$this->cacheControl && !$this->has('ETag') && !$this->has('Last-Modified') && !$this->has('Expires')) {
            return 'no-cache';
        }
        if (!$this->cacheControl) {
            // conservative by default
            return 'private, must-revalidate';
        }
        $header = $this->getCacheControlHeader();
        if (isset($this->cacheControl['public']) || isset($this->cacheControl['private'])) {
            return $header;
        }
        // public if s-maxage is defined, private otherwise
        if (!isset($this->cacheControl['s-maxage'])) {
            return $header . ', private';
        }
        return $header;
    }
}
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\HttpFoundation;

/**
 * Represents a cookie
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * @api
 */
class Cookie
{
    protected $name;
    protected $value;
    protected $domain;
    protected $expire;
    protected $path;
    protected $secure;
    protected $httpOnly;
    /**
     * Constructor.
     *
     * @param string                   $name     The name of the cookie
     * @param string                   $value    The value of the cookie
     * @param integer|string|\DateTime $expire   The time the cookie expires
     * @param string                   $path     The path on the server in which the cookie will be available on
     * @param string                   $domain   The domain that the cookie is available to
     * @param Boolean                  $secure   Whether the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param Boolean                  $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
     *
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function __construct($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        // from PHP source code
        if (preg_match('/[=,; 	
]/', $name)) {
            throw new \InvalidArgumentException(sprintf('The cookie name "%s" contains invalid characters.', $name));
        }
        if (empty($name)) {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }
        // convert expiration time to a Unix timestamp
        if ($expire instanceof \DateTime) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);
            if (false === $expire || -1 === $expire) {
                throw new \InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }
        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->expire = $expire;
        $this->path = empty($path) ? '/' : $path;
        $this->secure = (bool) $secure;
        $this->httpOnly = (bool) $httpOnly;
    }
    /**
     * Returns the cookie as a string.
     *
     * @return string The cookie
     */
    public function __toString()
    {
        $str = urlencode($this->getName()) . '=';
        if ('' === (string) $this->getValue()) {
            $str .= 'deleted; expires=' . gmdate('D, d-M-Y H:i:s T', time() - 31536001);
        } else {
            $str .= urlencode($this->getValue());
            if ($this->getExpiresTime() !== 0) {
                $str .= '; expires=' . gmdate('D, d-M-Y H:i:s T', $this->getExpiresTime());
            }
        }
        if ('/' !== $this->path) {
            $str .= '; path=' . $this->path;
        }
        if (null !== $this->getDomain()) {
            $str .= '; domain=' . $this->getDomain();
        }
        if (true === $this->isSecure()) {
            $str .= '; secure';
        }
        if (true === $this->isHttpOnly()) {
            $str .= '; httponly';
        }
        return $str;
    }
    /**
     * Gets the name of the cookie.
     *
     * @return string
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Gets the value of the cookie.
     *
     * @return string
     *
     * @api
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Gets the domain that the cookie is available to.
     *
     * @return string
     *
     * @api
     */
    public function getDomain()
    {
        return $this->domain;
    }
    /**
     * Gets the time the cookie expires.
     *
     * @return integer
     *
     * @api
     */
    public function getExpiresTime()
    {
        return $this->expire;
    }
    /**
     * Gets the path on the server in which the cookie will be available on.
     *
     * @return string
     *
     * @api
     */
    public function getPath()
    {
        return $this->path;
    }
    /**
     * Checks whether the cookie should only be transmitted over a secure HTTPS connection from the client.
     *
     * @return Boolean
     *
     * @api
     */
    public function isSecure()
    {
        return $this->secure;
    }
    /**
     * Checks whether the cookie will be made accessible only through the HTTP protocol.
     *
     * @return Boolean
     *
     * @api
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }
    /**
     * Whether this cookie is about to be cleared
     *
     * @return Boolean
     *
     * @api
     */
    public function isCleared()
    {
        return $this->expire < time();
    }
}
