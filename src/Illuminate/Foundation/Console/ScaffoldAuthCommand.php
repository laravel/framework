<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ScaffoldAuthCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'scaffold:auth';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Add authentication scaffolding for the framework";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$files = new Filesystem;

		$files->copy(__DIR__.'/stubs/scaffold/Http/Controllers/HomeController.php', app_path('Http/Controllers/HomeController.php'));
		$files->copyDirectory(__DIR__.'/stubs/scaffold/Http/Controllers/Auth', app_path('Http/Controllers/Auth'));
		$files->copy(__DIR__.'/stubs/scaffold/Http/routes.php', app_path('Http/routes.php'));

		$files->copy(__DIR__.'/stubs/scaffold/migrations/2014_10_12_000000_create_users_table.php', base_path('database/migrations/2014_10_12_000000_create_users_table.php'));
		$files->copy(__DIR__.'/stubs/scaffold/migrations/2014_10_12_100000_create_password_resets_table.php', base_path('database/migrations/2014_10_12_100000_create_password_resets_table.php'));

		$files->copy(__DIR__.'/stubs/scaffold/Providers/AppServiceProvider.php', app_path('Providers/AppServiceProvider.php'));

		$files->copyDirectory(__DIR__.'/stubs/scaffold/public/css', base_path('public/css'));
		$files->copyDirectory(__DIR__.'/stubs/scaffold/public/fonts', base_path('public/fonts'));

		$files->copyDirectory(__DIR__.'/stubs/scaffold/resources/assets', base_path('resources/assets'));
		$files->copyDirectory(__DIR__.'/stubs/scaffold/resources/views', base_path('resources/views'));

		$this->info('Authentication scaffolding complete!');
	}

}
