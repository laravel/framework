<?php namespace Illuminate\Exception;

use Monolog\Handler\SocketHandler;
use Illuminate\Support\ServiceProvider;

class LiveServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		if ($this->registerMonologHandler())
		{
			$this->registerEvents();
		}
	}

	protected function registerEvents()
	{
		$monolog = $this->app['log']->getMonolog();

		$this->app['events']->listen('illuminate.query', function($sql, $bindings, $time) use ($monolog)
		{
			$monolog->addInfo($sql);
		});

		$this->app->before(function($request) use ($monolog)
		{
			$monolog->addInfo('Incoming request: '.$request->method().' '.$request->path());
		});

		$this->app['events']->listen('*', function() use ($monolog)
		{
			$event = last(func_get_args());
			if ($event != 'illuminate.query')
			{
				$monolog->addInfo('Event fired: '.last(func_get_args()));
			}
		});
	}

	/**
	 * Register Monolog handler and establish the connection.
	 *
	 * @return bool
	 */
	protected function registerMonologHandler()
	{
		return $this->establishConnection($this->app['log']->getMonolog());
	}

	/**
	 * Add the socket handler onto the Monolog stack.
	 *
	 * @return void
	 */
	protected function addSocketHandler()
	{
		$monolog = $this->app['log']->getMonolog();

		$monolog->pushHandler(new SocketHandler('tcp://127.0.0.1:8337'));		
	}

	/**
	 * Attempt to establish the socket handler connection.
	 *
	 * @param  \Monolog\Logger  $monolog
	 * @return bool
	 */
	protected function establishConnection($monolog)
	{
		try
		{
			$monolog->addInfo('Live debugger connecting...');
		}
		catch (\Exception $e)
		{
			$monolog->popHandler();

			return false;
		}

		return true;
	}

}