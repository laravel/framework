<?php namespace Illuminate\Foundation\Bootstrap;

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;

class HandleExceptions {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		error_reporting(-1);

		ErrorHandler::register();

		ExceptionHandler::register(strtolower(getenv('APP_DEBUG')) === 'yes');

		if ( ! $app->environment('testing'))
		{
			ini_set('display_errors', 'Off');
		}
	}

}
