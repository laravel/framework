<?php namespace Illuminate\Foundation\Http;

use Exception;
use Illuminate\Routing\Stack;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\TerminableMiddleware;
use Illuminate\Contracts\Http\Kernel as KernelContract;

class Kernel implements KernelContract {

	/**
	 * The application implementation.
	 *
	 * @var \Illuminate\Contracts\Foundation\Application
	 */
	protected $app;

	/**
	 * The router instance.
	 *
	 * @param  \Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @return void
	 */
	protected $bootstrappers = [
		'Illuminate\Foundation\Bootstrap\DetectEnvironment',
		'Illuminate\Foundation\Bootstrap\LoadConfiguration',
		'Illuminate\Foundation\Bootstrap\ConfigureLogging',
		'Illuminate\Foundation\Bootstrap\HandleExceptions',
		'Illuminate\Foundation\Bootstrap\RegisterFacades',
		'Illuminate\Foundation\Bootstrap\RegisterProviders',
		'Illuminate\Foundation\Bootstrap\BootProviders',
	];

	/**
	 * The application's middleware stack.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * The application's route middleware.
	 *
	 * @var array
	 */
	protected $routeMiddleware = [];

	/**
	 * Create a new HTTP kernel instance.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function __construct(Application $app, Router $router)
	{
		$this->app = $app;
		$this->router = $router;

		foreach ($this->routeMiddleware as $key => $middleware)
		{
			$router->middleware($key, $middleware);
		}
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function handle($request)
	{
		try
		{
			return $this->sendRequestThroughRouter($request);
		}
		catch (Exception $e)
		{
			$this->reportException($e);

			return $this->renderException($request, $e);
		}
	}

	/**
	 * Send the given request through the middleware / router.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	protected function sendRequestThroughRouter($request)
	{
		$this->app->instance('request', $request);

		$this->bootstrap();

		return (new Stack($this->app))
		            ->send($request)
		            ->through($this->middleware)
		            ->then($this->dispatchToRouter());
	}

	/**
	 * Call the terminate method on any terminable middleware.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Http\Response  $response
	 * @return void
	 */
	public function terminate($request, $response)
	{
		foreach ($this->middleware as $middleware)
		{
			$instance = $this->app->make($middleware);

			if ($instance instanceof TerminableMiddleware)
			{
				$instance->terminate($request, $response);
			}
		}
	}

	/**
	 * Bootstrap the application for HTTP requests.
	 *
	 * @return void
	 */
	public function bootstrap()
	{
		if ( ! $this->app->hasBeenBootstrapped())
		{
			$this->app->bootstrapWith($this->bootstrappers());
		}
	}

	/**
	 * Get the route dispatcher callback.
	 *
	 * @return \Closure
	 */
	protected function dispatchToRouter()
	{
		return function($request)
		{
			$this->app->instance('request', $request);

			return $this->router->dispatch($request);
		};
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers()
	{
		return $this->bootstrappers;
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	protected function reportException(Exception $e)
	{
		$this->app['Illuminate\Contracts\Debug\ExceptionHandler']->report($e);
	}

	/**
	 * Render the exception to a response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function renderException($request, Exception $e)
	{
		return $this->app['Illuminate\Contracts\Debug\ExceptionHandler']->render($request, $e);
	}

	/**
	 * Get the Laravel application instance.
	 *
	 * @return \Illuminate\Contracts\Foundation\Application
	 */
	public function getApplication()
	{
		return $this->app;
	}

}
