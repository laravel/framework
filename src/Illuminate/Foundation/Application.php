<?php namespace Illuminate\Foundation;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Config\FileLoader;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Exception\ExceptionServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Contracts\ResponsePreparerInterface;
use Symfony\Component\HttpKernel\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class Application extends Container implements HttpKernelInterface, ResponsePreparerInterface {

	/**
	 * The Laravel framework version.
	 *
	 * @var string
	 */
	const VERSION = '4.0.6';

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
	 * The array of close callbacks.
	 *
	 * @var array
	 */
	protected $closeCallbacks = array();

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
		$this['request'] = $this->createRequest($request);

		// The exception handler class takes care of determining which of the bound
		// exception handler Closures should be called for a given exception and
		// gets the response from them. We'll bind it here to allow overrides.
		$this->register(new ExceptionServiceProvider($this));

		$this->register(new RoutingServiceProvider($this));

		$this->register(new EventServiceProvider($this));
	}

	/**
	 * Create the request for the application.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Request
	 */
	protected function createRequest(Request $request = null)
	{
		return $request ?: static::onRequest('createFromGlobals');
	}

	/**
	 * Redirect the request if it has a trailing slash.
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
	 */
	public function redirectIfTrailingSlash()
	{
		if ($this->runningInConsole()) return;

		// Here we will check if the request path ends in a single trailing slash and
		// redirect it using a 301 response code if it does which avoids duplicate
		// content in this application while still providing a solid experience.
		$path = $this['request']->getPathInfo();

		if ($this->hasTrailingSlash($path))
		{
			$this->redirectWithoutSlash();
		}
	}

	/**
	 * Determine if the given path has a trailing slash.
	 *
	 * @param  string  $path
	 * @return string
	 */
	protected function hasTrailingSlash($path)
	{
		return ($path != '/' and ends_with($path, '/') and ! ends_with($path, '//'));
	}

	/**
	 * Send a redirect response without the trailing slash.
	 *
	 * @return void
	 */
	protected function redirectWithoutSlash()
	{
		with(new SymfonyRedirect($this['request']->fullUrl(), 301))->send();

		exit;
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
		$this['env'] = with(new EnvironmentDetector($this['request']))->detect(

			$environments, $this->runningInConsole()

		);

		return $this['env'];
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
	 * @param  \Illuminate\Support\ServiceProvider|string  $provider
	 * @param  array  $options
	 * @return void
	 */
	public function register($provider, $options = array())
	{
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
	}

	/**
	 * Resolve a service provider instance from the class name.
	 *
	 * @param  string  $provider
	 * @return \Illuminate\Support\ServiceProvider
	 */
	protected function resolveProviderClass($provider)
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
		$me = $this;

		// We will simply spin through each of the deferred providers and register each
		// one and boot them if the application has booted. This should make each of
		// the remaining services available to this application for immediate use.
		foreach ($this->deferredServices as $provider)
		{
			$this->registerDeferredProvider($provider);
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
	 * Register a deffered provider and service.
	 *
	 * @param  string  $provider
	 * @param  string  $service
	 * @return void
	 */
	protected function registerDeferredProvider($provider, $service = null)
	{
		$this->register($instance = new $provider($this));

		// Once the provider that provides the deferred service has been registered we
		// will remove it from our local list of the deferred services with related
		// providers so that this container does not try to resolve it out again.
		if ($service) unset($this->deferredServices[$service]);

		$this->setupDeferredBoot($instance);
	}

	/**
	 * Handle the booting of a deferred service provider.
	 *
	 * @param  \Illuminate\Support\ServiceProvider  $instance
	 * @return void
	 */
	protected function setupDeferredBoot($instance)
	{
		if ($this->booted) return $instance->boot();

		$this->booting(function() use ($instance) { $instance->boot(); });
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
		if (isset($this->deferredServices[$abstract]))
		{
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
		$this->closeCallbacks[] = $callback;
	}

	/**
	 * Register a "finish" application filter.
	 *
	 * @param  Closure|string  $callback
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
	public function shutdown($callback = null)
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
	 * Boot the application's service providers.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->booted) return;

		array_walk($this->serviceProviders, function($p) { $p->boot(); });

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
	}

	/**
	 * Handles the given request and delivers the response.
	 *
	 * @return void
	 */
	public function run()
	{
		$response = $this->dispatch($this['request']);

		$this->terminate($this['request'], $response);
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
	 */
	public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
	{
		$this->refreshRequest($request = Request::createFromBase($request));

		return $this->dispatch($request);
	}

	/**
	 * Handle the given request and get the response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function dispatch(Request $request)
	{
		if ($this->isDownForMaintenance())
		{
			$response = $this['events']->until('illuminate.app.down');

			if ( ! is_null($response)) return $this->prepareResponse($response, $request);
		}

		return $this['router']->dispatch($this->prepareRequest($request));
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
		$this->callCloseCallbacks($request, $response);

		$response->send();

		$this->callFinishCallbacks($request, $response);
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
	 * Call the "close" callbacks assigned to the application.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Http\Response  $response
	 * @return void
	 */
	public function callCloseCallbacks(Request $request, Response $response)
	{
		foreach ($this->closeCallbacks as $callback)
		{
			call_user_func($callback, $request, $response);
		}
	}

	/**
	 * Call the "finish" callbacks assigned to the application.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Http\Response  $response
	 * @return void
	 */
	public function callFinishCallbacks(Request $request, Response $response)
	{
		foreach ($this->finishCallbacks as $callback)
		{
			call_user_func($callback, $request, $response);
		}
	}

	/**
	 * Call the booting callbacks for the application.
	 *
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
	 * Prepare the request by injecting any services.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Request
	 */
	public function prepareRequest(Request $request)
	{
		if (isset($this['session']))
		{
			$request->setSessionStore($this['session']);
		}

		return $request;
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
	 * Determine if the application is currently down for maintenance.
	 *
	 * @return bool
	 */
	public function isDownForMaintenance()
	{
		return file_exists($this['path.storage'].'/meta/down');
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
	 */
	public function abort($code, $message = '', array $headers = array())
	{
		if ($code == 404)
		{
			throw new NotFoundHttpException($message);
		}
		else
		{
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
		$this->error(function(NotFoundHttpException $e) use ($callback)
		{
			return call_user_func($callback, $e);
		});
	}

	/**
	 * Register an application error handler.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function error(Closure $callback)
	{
		$this['exception']->error($callback);
	}

	/**
	 * Register an error handler at the bottom of the stack.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function pushError(Closure $callback)
	{
		$this['exception']->pushError($callback);
	}

	/**
	 * Register an error handler for fatal errors.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function fatal(Closure $callback)
	{
		$this->error(function(FatalErrorException $e) use ($callback)
		{
			return call_user_func($callback, $e);
		});
	}

	/**
	 * Get the configuration loader instance.
	 *
	 * @return \Illuminate\Config\LoaderInterface
	 */
	public function getConfigLoader()
	{
		return new FileLoader(new Filesystem, $this['path'].'/config');
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

		$this->instance('request', static::onRequest('create', $parameters));
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
