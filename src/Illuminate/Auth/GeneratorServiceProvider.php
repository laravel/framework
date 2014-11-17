<?php namespace Illuminate\Auth;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Console\ClearRemindersCommand;

class GeneratorServiceProvider extends ServiceProvider {

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
		'ClearReminders',
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		foreach ($this->commands as $command)
		{
			$this->{"register{$command}Command"}();
		}

		$this->commands(
			'command.auth.reminders.clear'
		);
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerClearRemindersCommand()
	{
		$this->app->singleton('command.auth.reminders.clear', function()
		{
			return new ClearRemindersCommand;
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
			'command.auth.reminders.clear'
		];
	}

}
