<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use ClassPreloader\Command\PreCompileCommand;
use Symfony\Component\Console\Input\InputOption;

class OptimizeCommand extends Command
{
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
    protected $description = 'Optimize the framework for better performance';

    /**
     * The composer instance.
     *
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

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
        $this->info('Generating optimized class loader');

        if ($this->option('psr')) {
            $this->composer->dumpAutoloads();
        } else {
            $this->composer->dumpOptimized();
        }

        if ($this->option('force') || !$this->laravel['config']['app.debug']) {
            $this->info('Compiling common classes');

            $this->compileClasses();
        } else {
            $this->call('clear-compiled');
        }
    }

    /**
     * Generate the compiled class file.
     *
     * @return void
     */
    protected function compileClasses()
    {
        $this->registerClassPreloaderCommand();

        $this->callSilent('compile', [
            '--config' => implode(',', $this->getClassFiles()),
            '--output' => $this->laravel->getCachedCompilePath(),
            '--strip_comments' => 1,
            '--skip_dir_file' => (bool) $this->option('portable'),
        ]);
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

        $files = array_merge($core, $this->laravel['config']->get('compile.files', []));

        foreach ($this->laravel['config']->get('compile.providers', []) as $provider) {
            $files = array_merge($files, forward_static_call([$provider, 'compiles']));
        }

        return $files;
    }

    /**
     * Register the pre-compiler command instance with Artisan.
     *
     * @return void
     */
    protected function registerClassPreloaderCommand()
    {
        $this->getApplication()->add(new PreCompileCommand);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the compiled class file to be written.'],

            ['psr', null, InputOption::VALUE_NONE, 'Do not optimize Composer dump-autoload.'],

            ['portable', null, InputOption::VALUE_NONE, 'Skip files with __DIR__ or __FILE__ to make the cache portable.'],
        ];
    }
}
