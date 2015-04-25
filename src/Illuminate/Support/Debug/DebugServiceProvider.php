<?php namespace Illuminate\Support\Debug;

use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('dumper', function()
		{
			return new Dumper;
		});
	}

}
