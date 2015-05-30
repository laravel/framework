<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class FreshCommand extends Command {

	use ConfirmableTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'fresh';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Remove the scaffolding included with the framework";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if ( ! $this->confirmToProceed()) return;

		$files = new Filesystem;

		$files->deleteDirectory(app_path('Services'));
		$files->delete(base_path('resources/views/app.blade.php'));
		$files->delete(base_path('resources/views/home.blade.php'));
		$files->deleteDirectory(app_path('Http/Controllers/Auth'));
		$files->deleteDirectory(base_path('resources/views/auth'));
		$files->deleteDirectory(base_path('resources/views/emails'));
		$files->delete(app_path('Http/Controllers/HomeController.php'));

		$files->deleteDirectory(base_path('public/css'));
		$files->deleteDirectory(base_path('public/fonts'));
		$files->put(base_path('resources/assets/less/app.less'), ''.PHP_EOL);
		$files->deleteDirectory(base_path('resources/assets/less/bootstrap'));

		$files->delete(base_path('database/migrations/2014_10_12_000000_create_users_table.php'));
		$files->delete(base_path('database/migrations/2014_10_12_100000_create_password_resets_table.php'));

		$files->put(app_path('Http/routes.php'), $files->get(__DIR__.'/stubs/fresh-routes.stub'));
		$files->put(app_path('Providers/AppServiceProvider.php'), $files->get(__DIR__.'/stubs/fresh-app-provider.stub'));

		$this->info('Scaffolding removed! Enjoy your fresh start.');
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
		);
	}

}
