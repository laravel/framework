<?php namespace Illuminate\Exception;

use Monolog\Handler\SocketHandler;
use Illuminate\Support\ServiceProvider;

class LiveServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerCommand();
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->registerMonologHandler()) $this->registerEvents();
	}

	/**
	 * Register the live debugger events.
	 *
	 * @return void
	 */
	protected function registerEvents()
	{
		$monolog = $this->app['log']->getMonolog();

		foreach (array('Request', 'Database') as $event)
		{
			$this->{"register{$event}Logger"}($monolog);
		}
	}

	/**
	 * Register the live debugger console command.
	 *
	 * @return void
	 */
	protected function registerCommand()
	{
		$this->app['command.debug'] = $this->app->share(function($app)
		{
			return new Console\DebugCommand;
		});

		$this->commands('command.debug');
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
			$monolog->addInfo('<info>'.strtolower($request->getMethod()).' '.$request->path().'</info>');
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
			$sql = str_replace_array('\?', $bindings, $sql);

			$monolog->addInfo('<comment>'.$sql.' ['.$time.'ms]</comment>');
		});
	}

	/**
	 * Register Monolog handler and establish the connection.
	 *
	 * @return bool
	 */
	protected function registerMonologHandler()
	{
		$this->addSocketHandler();

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
			$monolog->addInfo('Debug client connecting...');
		}
		catch (\Exception $e)
		{
			$monolog->popHandler();

			return false;
		}

		return true;
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('command.debug');
	}

}