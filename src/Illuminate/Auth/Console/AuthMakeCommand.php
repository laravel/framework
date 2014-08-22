<?php namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;

class AuthMakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:auth';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create auth classes for the application';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->call('auth:controller');
		$this->call('auth:reminders-controller');
		$this->callSilent('auth:register-request');
		$this->callSilent('auth:login-request');
		$this->info('Authentication requests created successfully.');
		$this->call('auth:reminders-table');
	}

}
