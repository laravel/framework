<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ProviderMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:provider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service provider class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Provider';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/provider.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Providers';
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        return tap(parent::handle(), function($result) {
            if ($result === false || !$this->hasOption('register')) {
                return;
            }

            $this->registerProvider();
        });
    }

    /**
     * Add the provider to the app.php config file.
     *
     * @return void
     */
    protected function registerProvider()
    {
        $namespace = $this->laravel->getNamespace();
        $className = $this->qualifyClass($this->argument('name'));
        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $className)) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}Providers\RouteServiceProvider::class,".$eol,
            "{$namespace}Providers\RouteServiceProvider::class,".$eol.'        '.$className.'::class,'.$eol,
            $appConfig
        ));

        $this->info($this->type.' registered successfully.');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['register', 'r', InputOption::VALUE_NONE, 'Automatically register the created provider in your application'],
        ];
    }
}
