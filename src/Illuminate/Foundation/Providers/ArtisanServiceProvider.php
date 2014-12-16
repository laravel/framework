<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\TinkerCommand;
use Illuminate\Foundation\Console\AppNameCommand;
use Illuminate\Foundation\Console\OptimizeCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Console\RouteScanCommand;
use Illuminate\Foundation\Console\EventScanCommand;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteClearCommand;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Foundation\Console\RequestMakeCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Foundation\Console\ClearCompiledCommand;

class ArtisanServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * The commands to be registered.
	 *
	 * @var array
	 */
	protected $commands = [
		'AppName' => 'command.app.name',
		'ClearCompiled' => 'command.clear-compiled',
		'ConsoleMake' => 'command.console.make',
		'Down' => 'command.down',
		'Environment' => 'command.environment',
		'EventScan' => 'command.event.scan',
		'KeyGenerate' => 'command.key.generate',
		'Optimize' => 'command.optimize',
		'ProviderMake' => 'command.provider.make',
		'RequestMake' => 'command.request.make',
		'RouteCache' => 'command.route.cache',
		'RouteClear' => 'command.route.clear',
		'RouteList' => 'command.route.list',
		'RouteScan' => 'command.route.scan',
		'Tinker' => 'command.tinker',
		'Up' => 'command.up',
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		foreach (array_keys($this->commands) as $command)
		{
			$method = "register{$command}Command";

			call_user_func_array([$this, $method], []);
		}

		$this->commands(array_values($this->commands));
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerAppNameCommand()
	{
		$this->app->singleton('command.app.name', function($app)
		{
			return new AppNameCommand($app['composer'], $app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerClearCompiledCommand()
	{
		$this->app->singleton('command.clear-compiled', function()
		{
			return new ClearCompiledCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerConsoleMakeCommand()
	{
		$this->app->singleton('command.console.make', function($app)
		{
			return new ConsoleMakeCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerDownCommand()
	{
		$this->app->singleton('command.down', function()
		{
			return new DownCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerEnvironmentCommand()
	{
		$this->app->singleton('command.environment', function()
		{
			return new EnvironmentCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerEventScanCommand()
	{
		$this->app->singleton('command.event.scan', function()
		{
			return new EventScanCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerKeyGenerateCommand()
	{
		$this->app->singleton('command.key.generate', function($app)
		{
			return new KeyGenerateCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerOptimizeCommand()
	{
		$this->app->singleton('command.optimize', function($app)
		{
			return new OptimizeCommand($app['composer']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerProviderMakeCommand()
	{
		$this->app->singleton('command.provider.make', function($app)
		{
			return new ProviderMakeCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRequestMakeCommand()
	{
		$this->app->singleton('command.request.make', function($app)
		{
			return new RequestMakeCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRouteCacheCommand()
	{
		$this->app->singleton('command.route.cache', function($app)
		{
			return new RouteCacheCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRouteClearCommand()
	{
		$this->app->singleton('command.route.clear', function($app)
		{
			return new RouteClearCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRouteListCommand()
	{
		$this->app->singleton('command.route.list', function($app)
		{
			return new RouteListCommand($app['router']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRouteScanCommand()
	{
		$this->app->singleton('command.route.scan', function()
		{
			return new RouteScanCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerTinkerCommand()
	{
		$this->app->singleton('command.tinker', function()
		{
			return new TinkerCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerUpCommand()
	{
		$this->app->singleton('command.up', function()
		{
			return new UpCommand;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array_values($this->commands);
	}

}
