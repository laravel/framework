<?php namespace Illuminate\Foundation;

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

class Application extends Container implements HttpKernelInterface {

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
	 * Get the application bootstrap file.
	 *
	 * @return string
	 */
	public static function getBootstrapFile()
	{
		return __DIR__.'/start.php';
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
		$provider = array_first($this->serviceProviders, function($key, $provider)
		{
			return $provider instanceof ExceptionServiceProvider;
		});

		$provider->startHandling($this);
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

		// First we will check to see if we have any command-line arguments and if
		// if we do we will set this environment based on those arguments as we
		// need to set it before each configurations are actually loaded out.
		$arguments = $this['request']->server->get('argv');

		if ($this->runningInConsole())
		{
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
		if ($environments instanceof Closure)
		{
			return $this['env'] = call_user_func($environments);
		}

		foreach ($environments as $environment => $hosts)
		{
			// To determine the current environment, we'll simply iterate through the
			// possible environments and look for a host that matches this host in
			// the request's context, then return back that environment's names.
			foreach ($hosts as $host)
			{
				if (str_is($host, $base) or $this->isMachine($host))
				{
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
		foreach ($arguments as $key => $value)
		{
			// For the console environmnet, we'll just look for an argument that starts
			// with "--env" then assume that it is setting the environment for every
			// operation being performed, and we'll use that environment's config.
			if (starts_with($value, '--env='))
			{
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
		foreach ($options as $key => $value)
		{
			$this[$key] = $value;
		}

		$this->serviceProviders[] = $provider;

		$this->loadedProviders[get_class($provider)] = true;
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
			$this->register($instance = new $provider($this));

			unset($this->deferredServices[$service]);

			if ($this->booted) $instance->boot();
		}
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
		// Before we handle the requests we need to make sure the application has been
		// booted up. The boot process will call the "boot" method on each service
		// provider giving them all a chance to register any application events.
		if ( ! $this->booted) $this->boot();

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
		foreach ($this->serviceProviders as $provider)
		{
			$provider->boot($this);
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
		foreach ($this->bootingCallbacks as $callback)
		{
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
	 * @param  Illuminate\Foundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function prepareResponse($value, Request $request)
	{
		if ( ! $value instanceof SymfonyResponse) $value = new Response($value);

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

		$this['events']->fire('locale.changed', compact('locale'));
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
		$this->error(function(FatalErrorException $e) use ($callback)
		{
			return call_user_func($callback, $e);
		});
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