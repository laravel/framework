<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ViewCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:view {name} 
                {--p|path : If the specified directory does not exist, it creates a directory.}
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
    public function __construct()
    {
        parent::__construct();
    }

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
        $path = $this->option('path');

        // If it specifies directory and file as dots, it converts dots to slash(/)
        $viewName = Str::of($this->argument('name'))->replace('.', DIRECTORY_SEPARATOR)->replace('//', '/');

        $viewPath = resource_path('views/'.$viewName.'.blade.php');

        // If the file exists, and the user wants to overwrite it, it will be overwritten.
        if (file_exists($viewPath) && ! $force) {

            $this->error('View already exists!');
            return Command::FAILURE;
        } elseif (file_exists($viewPath) && $force) {
            unlink($viewPath);
        }

        // If the directory exists, and the user wants to overwrite it, it will be overwritten.
        if (! is_dir(dirname($viewPath)) && $path) {
            mkdir(dirname($viewPath), 0755, true);
        } elseif (! is_dir(dirname($viewPath)) && ! $path) {
            $this->error('Directory does not exist!');
            $this->info('If you want to create the directory as well add the -p flag');

            return Command::FAILURE;
        }

        $this->info('Creating view:'.$viewName.'.blade.php');
        file_put_contents($viewPath, '@extends(\'layouts.app\')'."\n\n".'@section(\'content\')'."\n\n".'@endsection');

        return Command::SUCCESS;
    }
}
