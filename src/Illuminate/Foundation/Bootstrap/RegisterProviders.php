<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\Bootstrapper as BootstrapperContract;

class RegisterProviders implements BootstrapperContract {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$app->registerConfiguredProviders();
	}

}
