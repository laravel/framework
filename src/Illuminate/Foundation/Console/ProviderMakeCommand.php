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
        return tap(parent::handle(), function ($result) {
            if ($result !== false && $this->option('install')) {
                $this->registerProvider();
            }
        });
    }

    /**
     * Add the provider to the app.php config file.
     *
     * @return void
     */
    protected function registerProvider()
    {
        $appConfig = file_get_contents(config_path('app.php'));

        if (! Str::contains($appConfig, 'App\\Providers\\'.$this->getNameInput().'::class')) {
            file_put_contents(config_path('app.php'), str_replace(
                'App\\Providers\\RouteServiceProvider::class,',
                'App\\Providers\\RouteServiceProvider::class,'.PHP_EOL.'        App\\Providers\\'.$this->getNameInput().'::class,',
                $appConfig
            ));
        }

        $this->info($this->type.' installed successfully.');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['install', 'i', InputOption::VALUE_NONE, 'Automatically install the created provider in your application'],
        ];
    }
}
