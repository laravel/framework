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

	/**
	 * Register the live debugger events.
	 *
	 * @return void
	 */
	protected function registerEvents()
	{
		$monolog = $this->app['log']->getMonolog();

		foreach (array('Request', 'Events', 'Database') as $event)
		{
			$this->{"register{$event}Logger"}($monolog);
		}
	}

	/**
	 * Register the request logger event.
	 *
	 * @param  \Monolog\Logger  $monolog
	 * @return void
	 */
	protected function registerRequestLogger($monolog)
	{
		$this->app->before(function($request) use ($monolog)
		{
			$monolog->addInfo(strtoupper($request->getMethod()).' '.$request->path());
		});
	}

	/**
	 * Register the wildcard event listener.
	 *
	 * @param  \Monolog\Logger  $monolog
	 * @return void
	 */
	protected function registerEventsLogger($monolog)
	{
		$this->app['events']->listen('*', function() use ($monolog)
		{
			if (($event = last(func_get_args())) != 'illuminate.query')
			{
				$monolog->addInfo('Event fired: '.$event);
			}
		});
	}

	/**
	 * Register the database query listener.
	 *
	 * @param  \Monolog\Logger  $monolog
	 * @return void
	 */
	protected function registerDatabaseLogger($monolog)
	{
		$this->app['events']->listen('illuminate.query', function($sql, $bindings, $time) use ($monolog)
		{
			$monolog->addInfo($sql);
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