<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\Bootstrapper as BootstrapperContract;

class SetRequestForConsole implements BootstrapperContract {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$url = $app['config']->get('app.url', 'http://localhost');

		$app->instance('request', Request::create($url, 'GET', [], [], [], $_SERVER));
	}

}
