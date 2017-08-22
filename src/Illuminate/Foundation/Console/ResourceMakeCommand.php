<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ResourceMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if ($this->option('collection')) {
            $this->type = 'Resource collection';
        }

        parent::handle();

        if (! $this->option('collection')) {
            $this->call('make:resource', [
                'name' => $this->argument('name').'Collection',
                '--collection' => true,
            ]);
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('collection')
                    ? __DIR__.'/stubs/resource-collection.stub'
                    : __DIR__.'/stubs/resource.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Resources';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['collection', 'c', InputOption::VALUE_NONE, 'Create a resource collection.'],
        ];
    }
}
