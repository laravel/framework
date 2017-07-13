<?php

namespace Illuminate\Database\Console\Factories;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class FactoryMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Factory';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/factory.stub';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $model = $this->qualifyClass($this->argument('name'));

        if ($this->argument('model')) {
            $model = $this->qualifyClass($this->argument('model'));
        }

        return str_replace(
            'DummyModel', $model, parent::buildClass($name)
        );
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace(
            ['\\', '/'], '', $this->argument('name')
        ).'Factory';

        return $this->laravel->databasePath()."/factories/{$name}.php";
    }

    protected function getArguments()
    {
        return [
            parent::getArguments()[0],
            ['model', InputArgument::OPTIONAL, 'The name of the model to create'],
        ];
    }
}
