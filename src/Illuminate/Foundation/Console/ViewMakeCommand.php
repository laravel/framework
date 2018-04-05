<?php

namespace Illuminate\Foundation\Console;

use RuntimeException;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class ViewMakeCommand extends Command
{

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new blade template in view directory with given name.';

    /**
     * Create a new controller.
     *
     * @param  \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $paths = app('config')->get('view.paths');
        $path = null;
        if (is_string($paths)) {
            $path = $paths;
        } elseif (is_array($paths) && isset($paths[0]) && count($paths) == 1) {
            $path = $paths[0];
        } elseif (is_array($paths) && count($paths) > 1) {

            //build question + answers
            $answers = [];
            foreach ($paths as $path) {
                $answers[] = $path;
            }
            $path = $this->choice('Application has more than 1 directory with views. Please select directory where to create new blade template', $answers);

        }
        if (is_null($path)) {
            throw new RuntimeException('Unable to get view path');
        }
        $path .= DIRECTORY_SEPARATOR.str_replace('.', '/', $this->argument('name')).'.blade.php';
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if ($this->files->exists($path) && ! $this->confirm('Blade template with name "'.$this->argument('name').'" already exists. Overwrite?')) {
            return;
        }

        $this->files->put($path, '');
        $this->info('View created. '.PHP_EOL.'Name: '.$this->argument('name').PHP_EOL.'Path: '.$path);
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'Enter view name'],
        ];
    }

}
