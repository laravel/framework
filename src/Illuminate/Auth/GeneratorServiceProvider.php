<?php namespace Illuminate\Auth;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Console\AuthMakeCommand;
use Illuminate\Auth\Console\LoginRequestCommand;
use Illuminate\Auth\Console\AuthControllerCommand;
use Illuminate\Auth\Console\ClearRemindersCommand;
use Illuminate\Auth\Console\RemindersTableCommand;
use Illuminate\Auth\Console\RegisterRequestCommand;
use Illuminate\Auth\Console\RemindersControllerCommand;

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
		'AuthController',
		'AuthMake',
		'ClearReminders',
		'LoginRequest',
		'RegisterRequest',
		'RemindersController',
		'RemindersTable',
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
			'command.auth.reminders.clear', 'command.auth.reminders', 'command.auth.reminders.controller',
			'command.auth.make', 'command.auth.controller', 'command.auth.login.request',
			'command.auth.register.request'
		);
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerAuthControllerCommand()
	{
		$this->app->bindShared('command.auth.controller', function($app)
		{
			return new AuthControllerCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerAuthMakeCommand()
	{
		$this->app->bindShared('command.auth.make', function()
		{
			return new AuthMakeCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerClearRemindersCommand()
	{
		$this->app->bindShared('command.auth.reminders.clear', function()
		{
			return new ClearRemindersCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerLoginRequestCommand()
	{
		$this->app->bindShared('command.auth.login.request', function($app)
		{
			return new LoginRequestCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRegisterRequestCommand()
	{
		$this->app->bindShared('command.auth.register.request', function($app)
		{
			return new RegisterRequestCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRemindersControllerCommand()
	{
		$this->app->bindShared('command.auth.reminders.controller', function($app)
		{
			return new RemindersControllerCommand($app['files']);
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerRemindersTableCommand()
	{
		$this->app->bindShared('command.auth.reminders', function($app)
		{
			return new RemindersTableCommand($app['files']);
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
			'command.auth.reminders.clear', 'command.auth.reminders',
			'command.auth.reminders.controller', 'command.auth.make',
			'command.auth.controller', 'command.auth.login.request',
			'command.auth.register.request',
		];
	}

}
