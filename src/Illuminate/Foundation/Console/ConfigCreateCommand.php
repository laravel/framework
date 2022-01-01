<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Command\Command;

class ConfigCreateCommand extends GeneratorCommand
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

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/config-make.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__ . $relativePath;
    }
}
