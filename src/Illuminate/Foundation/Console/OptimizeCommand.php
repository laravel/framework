<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Illuminate\Foundation\AssetPublisher;
use ClassPreloader\Command\PreCompileCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class OptimizeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'optimize';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Optimize the framework for better performance";

	/**
	 * The composer instance.
	 *
	 * @var \Illuminate\Foundation\Composer
	 */
	protected $composer;

	/**
	 * The output path for the compiled class.
	 *
	 * @var string
	 */
	protected $outputPath;

	/**
	 * Create a new optimize command instance.
	 *
	 * @param  \Illuminate\Foundation\Composer  $composer
	 * @return void
	 */
	public function __construct(Composer $composer)
	{
		parent::__construct();

		$this->composer = $composer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->info('Generating optimized class loader...');

		$this->composer->dumpOptimized();

		$this->outputPath = $this->laravel['path.base'].'/bootstrap/compiled.php';

		if ($this->laravel['config']['compile']['run'] === false)
		{
			@unlink($this->outputPath);
			return;
		}

		$this->info('Compiling common classes...');

		$this->compileClasses();
	}

	/**
	 * Generate the compiled class file.
	 *
	 * @return void
	 */
	protected function compileClasses()
	{
		$this->registerClassPreloaderCommand();

		$this->callSilent('compile', array('--output' => $this->outputPath, '--config' => implode(',', $this->getClassFiles())));
	}

	/**
	 * Get the classes that should be combined and compiled.
	 *
	 * @return array
	 */
	protected function getClassFiles()
	{
		$app = $this->laravel;

		$core = require __DIR__.'/Optimize/config.php';

		return array_merge($core, $this->laravel['config']['compile']['classes']);
	}

	/**
	 * Register the pre-compiler command instance with Artisan.
	 *
	 * @return void
	 */
	protected function registerClassPreloaderCommand()
	{
		$this->laravel['artisan']->add(new PreCompileCommand);
	}

}
