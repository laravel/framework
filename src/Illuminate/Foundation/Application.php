<?php namespace Illuminate\Foundation;

use Closure;
use Stack\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Config\FileLoader;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Contracts\Support\ResponsePreparer;
use Illuminate\Exception\ExceptionServiceProvider;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class Application extends Container implements HttpKernelInterface,
                                               TerminableInterface,
                                               ApplicationContract,
                                               ResponsePreparer {

	/**
	 * The Laravel framework version.
	 *
	 * @var string
	 */
	const VERSION = '5.0-dev';

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * The array of booting callbacks.
	 *
	 * @var array
	 */
	protected $bootingCallbacks = array();

	/**
	 * The array of booted callbacks.
	 *
	 * @var array
	 */
	protected $bootedCallbacks = array();

	/**
	 * The array of finish callbacks.
	 *
	 * @var array
	 */
	protected $finishCallbacks = array();

	/**
	 * The array of shutdown callbacks.
	 *
	 * @var array
	 */
	protected $shutdownCallbacks = array();

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
	 * The stack callback for the application.
	 *
	 * @var \Closure
	 */
	protected $stack;

	/**
	 * The request class used by the application.
	 *
	 * @var string
	 */
	protected static $requestClass = 'Illuminate\Http\Request';

	/**
	 * Create a new Illuminate application instance.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function __construct(Request $request = null)
	{
		$this->registerBaseBindings($request ?: $this->createNewRequest());

		$this->registerBaseServiceProviders();
	}

	/**
	 * Create a new request instance from the request class.
	 *
	 * @return \Illuminate\Http\Request
	 */
	protected function createNewRequest()
	{
		return forward_static_call(array(static::$requestClass, 'createFromGlobals'));
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	protected function registerBaseBindings($request)
	{
		$this->instance('request', $request);

		$this->instance('Illuminate\Container\Container', $this);
	}

	/**
	 * Register all of the base service providers.
	 *
	 * @return void
	 */
	protected function registerBaseServiceProviders()
	{
		foreach (array('Event', 'Exception', 'Routing') as $name)
		{
			$this->{"register{$name}Provider"}();
		}
	}

	/**
	 * Register the exception service provider.
	 *
	 * @return void
	 */
	protected function registerExceptionProvider()
	{
		$this->register(new ExceptionServiceProvider($this));
	}

	/**
	 * Register the routing service provider.
	 *
	 * @return void
	 */
	protected function registerRoutingProvider()
	{
		$this->register(new RoutingServiceProvider($this));
	}

	/**
	 * Register the event service provider.
	 *
	 * @return void
	 */
	protected function registerEventProvider()
	{
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
		$this->instance('path', realpath($paths['app']));

		// Here we will bind the install paths into the container as strings that can be
		// accessed from any point in the system. Each path key is prefixed with path
		// so that they have the consistent naming convention inside the container.
		foreach (array_except($paths, array('app')) as $key => $value)
		{
			$this->instance("path.{$key}", realpath($value));
		}
	}

	/**
	 * Get the application bootstrap file.
	 *
	 * @return string
	 */
	public static function getBootstrapFile()
	{
		return __DIR__.'/start.php';
	}

	/**
	 * Start the exception handling for the request.
	 *
	 * @return void
	 */
	public function startExceptionHandling()
	{
		$this['exception']->register($this->environment());

		$this['exception']->setDebug($this['config']['app.debug']);
	}

	/**
	 * Get or check the current application environment.
	 *
	 * @param  mixed
	 * @return string
	 */
	public function environment()
	{
		if (count(func_get_args()) > 0)
		{
			return in_array($this['env'], func_get_args());
		}

		return $this['env'];
	}

	/**
	 * Determine if application is in local environment.
	 *
	 * @return bool
	 */
	public function isLocal()
	{
		return $this['env'] == 'local';
	}

	/**
	 * Detect the application's current environment.
	 *
	 * @param  array|string  $envs
	 * @return string
	 */
	public function detectEnvironment($envs)
	{
		$args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;

		return $this['env'] = (new EnvironmentDetector())->detect($envs, $args);
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
	 * Force register a service provider with the application.
	 *
	 * @param  \Illuminate\Support\ServiceProvider|string  $provider
	 * @param  array  $options
	 * @return \Illuminate\Support\ServiceProvider
	 */
	public function forceRegister($provider, $options = array())
	{
		return $this->register($provider, $options, true);
	}

	/**
	 * Register a service provider with the application.
	 *
	 * @param  \Illuminate\Support\ServiceProvider|string  $provider
	 * @param  array  $options
	 * @param  bool   $force
	 * @return \Illuminate\Support\ServiceProvider
	 */
	public function register($provider, $options = array(), $force = false)
	{
		if ($registered = $this->getRegistered($provider) && ! $force)
                                     return $registered;

		// If the given "provider" is a string, we will resolve it, passing in the
		// application instance automatically for the developer. This is simply
		// a more convenient way of specifying your service provider classes.
		if (is_string($provider))
		{
			$provider = $this->resolveProviderClass($provider);
		}

		$provider->register();

		// Once we have registered the service we will iterate through the options
		// and set each of them on the application so they will be available on
		// the actual loading of the service objects and for developer usage.
		foreach ($options as $key => $value)
		{
			$this[$key] = $value;
		}

		$this->markAsRegistered($provider);

		// If the application has already booted, we will call this boot method on
		// the provider class so it has an opportunity to do its boot logic and
		// will be ready for any usage by the developer's application logics.
		if ($this->booted)
		{
			$this->bootProvider($provider);
		}

		return $provider;
	}

	/**
	 * Get the registered service provider instance if it exists.
	 *
	 * @param  \Illuminate\Support\ServiceProvider|string  $provider
	 * @return \Illuminate\Support\ServiceProvider|null
	 */
	public function getRegistered($provider)
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return array_first($this->serviceProviders, function($key, $value) use ($name)
		{
			return $value instanceof $name;
		});
	}

	/**
	 * Resolve a service provider instance from the class name.
	 *
	 * @param  string  $provider
	 * @return \Illuminate\Support\ServiceProvider
	 */
	public function resolveProviderClass($provider)
	{
		return new $provider($this);
	}

	/**
	 * Mark the given provider as registered.
	 *
	 * @param  \Illuminate\Support\ServiceProvider
	 * @return void
	 */
	protected function markAsRegistered($provider)
	{
		$this['events']->fire($class = get_class($provider), array($provider));

		$this->serviceProviders[] = $provider;

		$this->loadedProviders[$class] = true;
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
		foreach ($this->deferredServices as $service => $provider)
		{
			$this->loadDeferredProvider($service);
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
		if ( ! isset($this->loadedProviders[$provider]))
		{
			$this->registerDeferredProvider($provider, $service);
		}
	}

	/**
	 * Register a deferred provider and service.
	 *
	 * @param  string  $provider
	 * @param  string  $service
	 * @return void
	 */
	public function registerDeferredProvider($provider, $service = null)
	{
		// Once the provider that provides the deferred service has been registered we
		// will remove it from our local list of the deferred services with related
		// providers so that this container does not try to resolve it out again.
		if ($service) unset($this->deferredServices[$service]);

		$this->register($instance = new $provider($this));

		if ( ! $this->booted)
		{
			$this->booting(function() use ($instance)
			{
				$this->bootProvider($instance);
			});
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
		$abstract = $this->getAlias($abstract);

		if (isset($this->deferredServices[$abstract]))
		{
			$this->loadDeferredProvider($abstract);
		}

		return parent::make($abstract, $parameters);
	}

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * (Overriding Container::bound)
	 *
	 * @param  string  $abstract
	 * @return bool
	 */
	public function bound($abstract)
	{
		return isset($this->deferredServices[$abstract]) || parent::bound($abstract);
	}

	/**
	 * "Extend" an abstract type in the container.
	 *
	 * (Overriding Container::extend)
	 *
	 * @param  string   $abstract
	 * @param  \Closure  $closure
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	public function extend($abstract, Closure $closure)
	{
		$abstract = $this->getAlias($abstract);

		if (isset($this->deferredServices[$abstract]))
		{
			$this->loadDeferredProvider($abstract);
		}

		return parent::extend($abstract, $closure);
	}

	/**
	 * Register a "finish" application filter.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public function finish($callback)
	{
		$this->finishCallbacks[] = $callback;
	}

	/**
	 * Register a "shutdown" callback.
	 *
	 * @param  callable  $callback
	 * @return void
	 */
	public function shutdown(callable $callback = null)
	{
		if (is_null($callback))
		{
			$this->fireAppCallbacks($this->shutdownCallbacks);
		}
		else
		{
			$this->shutdownCallbacks[] = $callback;
		}
	}

	/**
	 * Register a function for determining when to use array sessions.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function useArraySessions(Closure $callback)
	{
		$this->bind('session.reject', function() use ($callback)
		{
			return $callback;
		});
	}

	/**
	 * Determine if the application has booted.
	 *
	 * @return bool
	 */
	public function isBooted()
	{
		return $this->booted;
	}

	/**
	 * Boot the application's service providers.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->booted) return;

		array_walk($this->serviceProviders, function($p) { $this->bootProvider($p); });

		$this->bootApplication();
	}

	/**
	 * Boot the application and fire app callbacks.
	 *
	 * @return void
	 */
	protected function bootApplication()
	{
		// Once the application has booted we will also fire some "booted" callbacks
		// for any listeners that need to do work after this initial booting gets
		// finished. This is useful when ordering the boot-up processes we run.
		$this->fireAppCallbacks($this->bootingCallbacks);

		$this->booted = true;

		$this->fireAppCallbacks($this->bootedCallbacks);
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
	 * Register a new "booted" listener.
	 *
	 * @param  mixed  $callback
	 * @return void
	 */
	public function booted($callback)
	{
		$this->bootedCallbacks[] = $callback;

		if ($this->isBooted()) $this->fireAppCallbacks(array($callback));
	}

	/**
	 * Boot the given service provider.
	 *
	 * @param  \Illuminate\Support\ServiceProvider  $provider
	 * @return void
	 */
	protected function bootProvider(ServiceProvider $provider)
	{
		if (method_exists($provider, 'boot'))
		{
			return $this->call([$provider, 'boot']);
		}
	}

	/**
	 * Determine if the application routes are cached.
	 *
	 * @return bool
	 */
	public function routesAreCached()
	{
		return $this['files']->exists($this->getCachedRoutesPath());
	}

	/**
	 * Get the path to the routes cache file.
	 *
	 * @return string
	 */
	public function getCachedRoutesPath()
	{
		return $this['path.storage'].'/framework/routes.php';
	}

	/**
	 * Determine if the application routes have been scanned.
	 *
	 * @return bool
	 */
	public function routesAreScanned()
	{
		return $this['files']->exists($this->getScannedRoutesPath());
	}

	/**
	 * Get the path to the scanned routes file.
	 *
	 * @return string
	 */
	public function getScannedRoutesPath()
	{
		return $this['path.storage'].'/framework/routes.scanned.php';
	}

	/**
	 * Determine if the application events have been scanned.
	 *
	 * @return bool
	 */
	public function eventsAreScanned()
	{
		return $this['files']->exists($this->getScannedEventsPath());
	}

	/**
	 * Get the path to the scanned events file.
	 *
	 * @return string
	 */
	public function getScannedEventsPath()
	{
		return $this['path.storage'].'/framework/events.scanned.php';
	}

	/**
	 * Register the application stack.
	 *
	 * @param  \Closure  $stack
	 * @return $this
	 */
	public function stack(Closure $stack)
	{
		$this->stack = $stack;

		return $this;
	}

	/**
	 * Run the application and send the response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function run(SymfonyRequest $request = null)
	{
		$request = $request ?: $this['request'];

		with($response = $this->handleRequest($request))->send();

		$this->terminate($request, $response);
	}

	/**
	 * Handle the given request and get the response.
	 *
	 * Provides compatibility with BrowserKit functional testing.
	 *
	 * @implements HttpKernelInterface::handle
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  int   $type
	 * @param  bool  $catch
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @throws \Exception
	 */
	public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
	{
		try
		{
			return $this->handleRequest($request);
		}
		catch (\Exception $e)
		{
			if ($this->runningUnitTests()) throw $e;

			return $this['exception']->handleException($e);
		}
	}

	/**
	 * Handle the given request and get the response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function handleRequest(SymfonyRequest $request = null)
	{
		$request = $request ?: $this['request'];

		$this->refreshRequest($request = Request::createFromBase($request));

		$this->boot();

		return with($stack = $this->call($this->stack))->setContainer($this)->run($request);
	}

	/**
	 * Terminate the request and send the response to the browser.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function terminate(SymfonyRequest $request, SymfonyResponse $response)
	{
		$this->callFinishCallbacks($request, $response);

		$this->shutdown();
	}

	/**
	 * Refresh the bound request instance in the container.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	protected function refreshRequest(Request $request)
	{
		$this->instance('request', $request);

		Facade::clearResolvedInstance('request');
	}

	/**
	 * Call the "finish" callbacks assigned to the application.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function callFinishCallbacks(SymfonyRequest $request, SymfonyResponse $response)
	{
		foreach ($this->finishCallbacks as $callback)
		{
			call_user_func($callback, $request, $response);
		}
	}

	/**
	 * Call the booting callbacks for the application.
	 *
	 * @param  array  $callbacks
	 * @return void
	 */
	protected function fireAppCallbacks(array $callbacks)
	{
		foreach ($callbacks as $callback)
		{
			call_user_func($callback, $this);
		}
	}

	/**
	 * Prepare the given value as a Response object.
	 *
	 * @param  mixed  $value
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function prepareResponse($value)
	{
		if ( ! $value instanceof SymfonyResponse) $value = new Response($value);

		return $value->prepare($this['request']);
	}

	/**
	 * Determine if the application is ready for responses.
	 *
	 * @return bool
	 */
	public function readyForResponses()
	{
		return $this->booted;
	}

	/**
	 * Determine if the application is currently down for maintenance.
	 *
	 * @return bool
	 */
	public function isDownForMaintenance()
	{
		return file_exists($this['config']['app.manifest'].'/down');
	}

	/**
	 * Register a maintenance mode event listener.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function down(Closure $callback)
	{
		$this['events']->listen('illuminate.app.down', $callback);
	}

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
	public function abort($code, $message = '', array $headers = array())
	{
		if ($code == 404)
		{
			throw new NotFoundHttpException($message);
		}

		throw new HttpException($code, $message, null, $headers);
	}

	/**
	 * Get the configuration loader instance.
	 *
	 * @return \Illuminate\Config\LoaderInterface
	 */
	public function getConfigLoader()
	{
		return new FileLoader(new Filesystem, $this['path.config']);
	}

	/**
	 * Get the service provider repository instance.
	 *
	 * @return \Illuminate\Foundation\ProviderRepository
	 */
	public function getProviderRepository()
	{
		$manifest = $this['config']['app.manifest'];

		return new ProviderRepository(new Filesystem, $manifest);
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
	 * Determine if the given service is a deferred service.
	 *
	 * @param  string  $service
	 * @return bool
	 */
	public function isDeferredService($service)
	{
		return isset($this->deferredServices[$service]);
	}

	/**
	 * Get or set the request class for the application.
	 *
	 * @param  string  $class
	 * @return string
	 */
	public static function requestClass($class = null)
	{
		if ( ! is_null($class)) static::$requestClass = $class;

		return static::$requestClass;
	}

	/**
	 * Set the application request for the console environment.
	 *
	 * @return void
	 */
	public function setRequestForConsoleEnvironment()
	{
		$url = $this['config']->get('app.url', 'http://localhost');

		$parameters = array($url, 'GET', array(), array(), array(), $_SERVER);

		$this->refreshRequest(static::onRequest('create', $parameters));
	}

	/**
	 * Call a method on the default request class.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public static function onRequest($method, $parameters = array())
	{
		return forward_static_call_array(array(static::requestClass(), $method), $parameters);
	}

	/**
	 * Get the current application locale.
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return $this['config']->get('app.locale');
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
	 * Register the core class aliases in the container.
	 *
	 * @return void
	 */
	public function registerCoreContainerAliases()
	{
		$aliases = array(
			'app'            => ['Illuminate\Foundation\Application', 'Illuminate\Contracts\Container\Container', 'Illuminate\Contracts\Foundation\Application'],
			'artisan'        => ['Illuminate\Console\Application', 'Illuminate\Contracts\Console\Application'],
			'auth'           => 'Illuminate\Auth\AuthManager',
			'auth.driver'    => ['Illuminate\Auth\Guard', 'Illuminate\Contracts\Auth\Guard'],
			'auth.password.tokens' => 'Illuminate\Auth\Passwords\TokenRepositoryInterface',
			'blade.compiler' => 'Illuminate\View\Compilers\BladeCompiler',
			'cache'          => ['Illuminate\Cache\CacheManager', 'Illuminate\Contracts\Cache\Factory'],
			'cache.store'    => ['Illuminate\Cache\Repository', 'Illuminate\Contracts\Cache\Repository'],
			'config'         => ['Illuminate\Config\Repository', 'Illuminate\Contracts\Config\Repository'],
			'cookie'         => ['Illuminate\Cookie\CookieJar', 'Illuminate\Contracts\Cookie\Factory', 'Illuminate\Contracts\Cookie\QueueingFactory'],
			'exception'      => 'Illuminate\Contracts\Exception\Handler',
			'encrypter'      => ['Illuminate\Encryption\Encrypter', 'Illuminate\Contracts\Encryption\Encrypter'],
			'db'             => 'Illuminate\Database\DatabaseManager',
			'events'         => ['Illuminate\Events\Dispatcher', 'Illuminate\Contracts\Events\Dispatcher'],
			'files'          => 'Illuminate\Filesystem\Filesystem',
			'filesystem'     => 'Illuminate\Contracts\Filesystem\Factory',
			'filesystem.disk' => 'Illuminate\Contracts\Filesystem\Filesystem',
			'filesystem.cloud' => 'Illuminate\Contracts\Filesystem\Cloud',
			'hash'           => 'Illuminate\Contracts\Hashing\Hasher',
			'translator'     => ['Illuminate\Translation\Translator', 'Symfony\Component\Translation\TranslatorInterface'],
			'log'            => ['Illuminate\Log\Writer', 'Illuminate\Contracts\Logging\Log', 'Psr\Log\LoggerInterface'],
			'mailer'         => ['Illuminate\Mail\Mailer', 'Illuminate\Contracts\Mail\Mailer', 'Illuminate\Contracts\Mail\MailQueue'],
			'paginator'      => 'Illuminate\Pagination\Factory',
			'auth.password'  => ['Illuminate\Auth\Passwords\PasswordBroker', 'Illuminate\Contracts\Auth\PasswordBroker'],
			'queue'          => ['Illuminate\Queue\QueueManager', 'Illuminate\Contracts\Queue\Factory', 'Illuminate\Contracts\Queue\Monitor'],
			'queue.connection' => 'Illuminate\Contracts\Queue\Queue',
			'redirect'       => 'Illuminate\Routing\Redirector',
			'redis'          => ['Illuminate\Redis\Database', 'Illuminate\Contracts\Redis\Database'],
			'request'        => 'Illuminate\Http\Request',
			'router'         => ['Illuminate\Routing\Router', 'Illuminate\Contracts\Routing\Registrar'],
			'session'        => 'Illuminate\Session\SessionManager',
			'session.store'  => ['Illuminate\Session\Store', 'Symfony\Component\HttpFoundation\Session\SessionInterface'],
			'url'            => ['Illuminate\Routing\UrlGenerator', 'Illuminate\Contracts\Routing\UrlGenerator'],
			'validator'      => ['Illuminate\Validation\Factory', 'Illuminate\Contracts\Validation\Factory'],
			'view'           => ['Illuminate\View\Factory', 'Illuminate\Contracts\View\Factory'],
		);

		foreach ($aliases as $key => $aliases)
		{
			foreach ((array) $aliases as $alias)
			{
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush()
	{
		parent::flush();

		$this->loadedProviders = [];
	}

}
