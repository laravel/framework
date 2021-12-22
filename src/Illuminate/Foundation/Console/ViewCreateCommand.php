<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class ViewCreateCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:view {name}
                {--f|force : Overwrite existing view if any}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a view file in the resources/views directory';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Here, we look at whether there is a view file to be created, then the relevant
        // answers are returned to those who use the command, and at the end, the view file is
        // created.

        $force = $this->option('force');

        // If it specifies directory and file as dots, it converts dots to slash(/)
        $viewName = $this->getView();

        $viewPath = resource_path('views/'.$viewName.'.blade.php');

        // If the file exists, and the user wants to overwrite it, it will be overwritten.
        if (file_exists($viewPath) && ! $force) {
            $this->error('View already exists!');

            return GeneratorCommand::FAILURE;
        } elseif (file_exists($viewPath) && $force) {
            unlink($viewPath);
        }

        // If the directory exists, and the user wants to overwrite it, it will be overwritten.
        if (! is_dir(dirname($viewPath))) {
            mkdir(dirname($viewPath), 0755, true);
        }

        $this->info('Creating view:'.$viewName.'.blade.php');

        copy($this->getStub(), $viewPath);

        return GeneratorCommand::SUCCESS;
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/view-make.stub';
    }

    protected function getView()
    {
        return Str::of($this->argument('name'))->replace('.', DIRECTORY_SEPARATOR)->replace('//', '/');
    }
}
