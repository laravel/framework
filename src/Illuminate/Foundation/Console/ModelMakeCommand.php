<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ModelMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (parent::fire() !== false) {
            if ($this->option('migration')) {
                $table = Str::plural(Str::snake(class_basename($this->argument('name'))));

                $this->call('make:migration', [
                    'name' => "create_{$table}_table",
                    '--create' => $table,
                    '--no-timestamps' => $this->option('no-timestamps'),
                    '--soft-deletes' => $this->option('soft-deletes'),
                ]);
            }
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/model.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name)
            ->replaceTimestamps($stub)
            ->replaceSoftDeletes($stub)
            ->replaceDateMutators($stub);
    }

    /**
     * Remove the timestamps attribute if no-timestamps option is not set.
     *
     * @param  string  $stub
     * @return $this
     */
    protected function replaceTimestamps(&$stub)
    {
        if (! $this->option('no-timestamps')) {
            $stub = str_replace("public $timestamps = false;\n", '', $stub);
        }

        return $this;
    }

    /**
     * Remove the SoftDeletes trait if soft-deletes option is not set.
     *
     * @param  string  $stub
     * @return $this
     */
    protected function replaceSoftDeletes(&$stub)
    {
        if (! $this->option('soft-deletes')) {
            $stub = str_replace("use Illuminate\Database\Eloquent\SoftDeletes;\n", '', $stub);
        }

        return $this;
    }

    /**
     * Add date mutators if soft-deletes option is set.
     *
     * @param  string  $stub
     * @return $this
     */
    protected function replaceDateMutators(&$stub)
    {
        if (($this->option('no-timestamps') && ! $this->option('soft-deletes')) ||
            (! $this->option('no-timestamps') && ! $this->option('soft-deletes'))) {
            $stub = str_replace("protected $dates = ['created_at', 'updated_at', 'deleted_at'];\n", '', $stub);
        } elseif ($this->option('no-timestamps') && $this->option('soft-deletes')) {
            $stub = str_replace(
                "protected $dates = ['created_at', 'updated_at', 'deleted_at'];",
                "protected $dates = ['soft-deletes'];",
                $stub
            );
        }

        return $this;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model.'],
            ['no-timestamps', 'nts', InputOption::VALUE_NONE, 'Set timestamps attribute to false.'],
            ['soft-deletes', 'sd', InputOption::VALUE_NONE, 'Add SoftDeletes trait to the model.'],
        ];
    }
}
