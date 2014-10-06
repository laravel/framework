<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Stack\Builder as Stack;

class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any necessary services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$router = $this->app['router'];

		foreach ($this->middleware as $key => $class)
		{
			$router->middleware($key, $class);
		}

		if (method_exists($this, 'stack'))
		{
			$this->app->call([$this, 'stack']);
		}
		else
		{
			$this->buildStack();
		}
	}

	/**
	 * Build the application stack based on the provider properties.
	 *
	 * @return void
	 */
	public function buildStack()
	{
		$this->app->stack(function(Stack $stack, Router $router)
		{
			return $stack
				->middleware($this->stack)
				->then(function($request) use ($router)
				{
					return $router->dispatch($request);
				});
			});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}
