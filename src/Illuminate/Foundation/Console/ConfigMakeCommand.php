<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class ConfigMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new config file';

    public function handle ()
    {
        $path = config_path() . '/' .  $this->getNameInput() . '.php';
        $stub = $this->files->get($this->getStub());

        $this->files->put($path, $stub);
        $this->info($this->getNameInput() .' config created successfully.');
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/config.stub';
    }
}