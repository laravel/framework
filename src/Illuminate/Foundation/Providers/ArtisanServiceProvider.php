<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Foundation\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\TailCommand;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Foundation\Console\TinkerCommand;
use Illuminate\Foundation\Console\AppNameCommand;
use Illuminate\Foundation\Console\ChangesCommand;
use Illuminate\Foundation\Console\OptimizeCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Console\EventCacheCommand;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteClearCommand;
use Illuminate\Foundation\Console\CommandMakeCommand;
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
		'AppName',
		'Changes',
		'ClearCompiled',
		'CommandMake',
		'Down',
		'Environment',
		'EventCache',
		'KeyGenerate',
		'Optimize',
		'ProviderMake',
		'RequestMake',
		'RouteCache',
		'RouteClear',
		'RouteList',
		'Serve',
		'Tail',
		'Tinker',
		'Up',
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// This Artisan class is a lightweight wrapper for calling into the Artisan
		// command line. If a call to this class is executed we will boot up the
		// entire Artisan command line then pass the method into the main app.
		$this->app->bindShared('artisan', function($app)
		{
			return new Artisan($app);
		});

		foreach ($this->commands as $command)
		{
			$this->{"register{$command}Command"}();
		}

		$this->commands(
			'command.tail', 'command.changes', 'command.environment', 'command.event.cache',
			'command.route.cache', 'command.route.clear', 'command.route.list',
			'command.request.make', 'command.tinker', 'command.command.make',
			'command.key.generate', 'command.down', 'command.up', 'command.clear-compiled',
			'command.optimize', 'command.serve', 'command.app.name', 'command.provider.make'
		);
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerAppNameCommand()
	{
		$this->app->bindShared('command.app.name', function($app)
		{
			return new AppNameCommand($app['composer'], $app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerChangesCommand()
	{
		$this->app->bindShared('command.changes', function($app)
		{
			return new ChangesCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerClearCompiledCommand()
	{
		$this->app->bindShared('command.clear-compiled', function()
		{
			return new ClearCompiledCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerCommandMakeCommand()
	{
		$this->app->bindShared('command.command.make', function($app)
		{
			return new CommandMakeCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerDownCommand()
	{
		$this->app->bindShared('command.down', function($app)
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
		$this->app->bindShared('command.environment', function($app)
		{
			return new EnvironmentCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerEventCacheCommand()
	{
		$this->app->bindShared('command.event.cache', function($app)
		{
			return new EventCacheCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerKeyGenerateCommand()
	{
		$this->app->bindShared('command.key.generate', function($app)
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
		$this->app->bindShared('command.optimize', function($app)
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
		$this->app->bindShared('command.provider.make', function($app)
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
		$this->app->bindShared('command.request.make', function($app)
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
		$this->app->bindShared('command.route.cache', function($app)
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
		$this->app->bindShared('command.route.clear', function($app)
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
		$this->app->bindShared('command.route.list', function($app)
		{
			return new RouteListCommand($app['router']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerServeCommand()
	{
		$this->app->bindShared('command.serve', function()
		{
			return new ServeCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerTailCommand()
	{
		$this->app->bindShared('command.tail', function($app)
		{
			return new TailCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerTinkerCommand()
	{
		$this->app->bindShared('command.tinker', function()
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
		$this->app->bindShared('command.up', function($app)
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
		return [
			'artisan', 'command.changes', 'command.tail', 'command.environment',
			'command.event.cache', 'command.route.cache', 'command.route.clear',
			'command.route.list', 'command.request.make', 'command.tinker',
			'command.command.make', 'command.key.generate', 'command.down',
			'command.up', 'command.clear-compiled', 'command.optimize',
			'command.serve', 'command.app.name', 'command.provider.make',
		];
	}

}
