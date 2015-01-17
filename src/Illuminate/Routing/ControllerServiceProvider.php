<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;

class ControllerServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('illuminate.route.dispatcher', function()
		{
			return new ControllerDispatcher($this->app['router'], $this->app);
		});
	}

}
