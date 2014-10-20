<?php namespace Illuminate\Foundation\Http;

use Illuminate\Routing\Stack;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Foundation\Application;
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
	 * @param \Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * Indicates if the bootstrap process has run.
	 *
	 * @var bool
	 */
	protected $bootstrapped = false;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @return void
	 */
	protected $bootstrappers = [
		'Illuminate\Foundation\Bootstrap\LoadEnvironment',
		'Illuminate\Foundation\Bootstrap\HandleExceptions',
		'Illuminate\Foundation\Bootstrap\LoadConfiguration',
		'Illuminate\Foundation\Bootstrap\RegisterProviders',
		'Illuminate\Foundation\Bootstrap\BootProviders',
	];

	/**
	 * The application's middleware stack.
	 *
	 * @var array
	 */
	protected $stack = [];

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
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function handle($request)
	{
		$this->app->instance('request', $request);

		$this->bootstrap();

		return (new Stack($this->app))
		            ->send($request)
		            ->through($this->stack)
		            ->then($this->dispatchToRouter());
	}

	/**
	 * Bootstrap the application for HTTP requests.
	 *
	 * @return void
	 */
	public function bootstrap()
	{
		if ( ! $this->bootstrapped)
		{
			$this->app->bootstrapWith($this->bootstrappers);
		}

		$this->bootstrapped = true;
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
			return $this->router->dispatch($request);
		};
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
