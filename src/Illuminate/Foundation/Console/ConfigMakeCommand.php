<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;

class ConfigMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:config {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a config file in the config directory';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $viewName = $this->argument('name') . '.php';
        $configPath = config_path($viewName);

        copy($this->getStub(), $configPath);

        $this->info("Created config file $configPath");

        return Command::SUCCESS;
    }

     /** The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Config';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('stubs/config-make.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}
