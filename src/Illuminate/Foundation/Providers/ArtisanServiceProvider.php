<?php namespace Illuminate\Foundation\Providers;

use ReflectionClass;
use ReflectionMethod;
use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;

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
		'command.app.name' => 'Illuminate\Foundation\Console\AppNameCommand',
		'command.clear-compiled' => 'Illuminate\Foundation\Console\ClearCompiledCommand',
		'command.command.make' => 'Illuminate\Foundation\Console\CommandMakeCommand',
		'command.config.cache' => 'Illuminate\Foundation\Console\ConfigCacheCommand',
		'command.config.clear' => 'Illuminate\Foundation\Console\ConfigClearCommand',
		'command.console.make' => 'Illuminate\Foundation\Console\ConsoleMakeCommand',
		'command.event.make' => 'Illuminate\Foundation\Console\EventMakeCommand',
		'command.down' => 'Illuminate\Foundation\Console\DownCommand',
		'command.environment' => 'Illuminate\Foundation\Console\EnvironmentCommand',
		'command.event.scan' => 'Illuminate\Foundation\Console\EventScanCommand',
		'command.handler.command' => 'Illuminate\Foundation\Console\HandlerCommandCommand',
		'command.handler.event' => 'Illuminate\Foundation\Console\HandlerEventCommand',
		'command.key.generate' => 'Illuminate\Foundation\Console\KeyGenerateCommand',
		'command.optimize' => 'Illuminate\Foundation\Console\OptimizeCommand',
		'command.provider.make' => 'Illuminate\Foundation\Console\ProviderMakeCommand',
		'command.request.make' => 'Illuminate\Foundation\Console\RequestMakeCommand',
		'command.route.cache' => 'Illuminate\Foundation\Console\RouteCacheCommand',
		'command.route.clear' => 'Illuminate\Foundation\Console\RouteClearCommand',
		'command.route.list' => 'Illuminate\Foundation\Console\RouteListCommand',
		'command.route.scan' => 'Illuminate\Foundation\Console\RouteScanCommand',
		'command.tinker' => 'Illuminate\Foundation\Console\TinkerCommand',
		'command.up' => 'Illuminate\Foundation\Console\UpCommand',
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		foreach ($this->commands as $commandName => $commandClass)
		{
			$this->registerCommand($commandName, $commandClass);
		}

		$this->commands(array_keys($this->commands));
	}

	/**
	 * Register command in container
	 *
	 * @param  string $commandName
	 * @param  string $commandClass
	 * @return void
	 */
	protected function registerCommand($commandName, $commandClass)
	{
		$this->app->singleton($commandName, function($app) use ($commandClass)
		{
			$class  = new ReflectionClass($commandClass);

			return $class->newInstanceArgs($this->resolveCommandParameters($class->getConstructor()));
		});
	}

	/**
	 * Resolve the parameters in the constructor
	 *
	 * @param  ReflectionMethod $constructor
	 * @return array
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function resolveCommandParameters(ReflectionMethod $constructor)
	{
		$params      = [];
		foreach ($constructor->getParameters() as $parameter) {
			$paramClass = $parameter->getClass();
			if (!is_null($paramClass)) {
				$paramClass   = $paramClass->name;
				$value        = $this->app->make($paramClass);
				$pos          = $parameter->getPosition();
				$params[$pos] = $value;
			} else {
				throw new InvalidArgumentException("The parameter: '" . $parameter->name . "' there is not valid class");
			}
		}

		return $params;
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array_keys($this->commands);
	}

}
