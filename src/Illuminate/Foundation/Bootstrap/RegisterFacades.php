<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\Bootstrapper as BootstrapperContract;

class RegisterFacades implements BootstrapperContract {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		Facade::clearResolvedInstances();

		Facade::setFacadeApplication($app);

		AliasLoader::getInstance($app['config']['app.aliases'])->register();
	}

}
