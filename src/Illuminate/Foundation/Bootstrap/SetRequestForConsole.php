<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;

class SetRequestForConsole {

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
