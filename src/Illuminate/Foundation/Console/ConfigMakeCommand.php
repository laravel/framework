<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Filesystem\join_paths;

#[AsCommand(name: 'make:config', aliases: ['config:make'])]
class ConfigMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:config
                    {name : The name of the config}
                    {--f|force : Create the configuration file even if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new configuration file';

    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'Config';

    /**
     * The console command name aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['config:make'];

    /**
     * Get the destination file path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        return config_path(Str::finish($this->argument('name'), '.php'));
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        $relativePath = join_paths('stubs', 'config.stub');

        return file_exists($customPath = $this->laravel->basePath($relativePath))
            ? $customPath
            : join_paths(__DIR__, $relativePath);
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => 'What should the configuration file be named?',
        ];
    }
}
