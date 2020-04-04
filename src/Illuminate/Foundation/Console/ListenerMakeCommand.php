<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ListenerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event listener class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Listener';

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $event = $this->option('event');

        if (! Str::startsWith($event, [
            $this->laravel->getNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $event = $this->laravel->getNamespace().'Events\\'.$event;
        }

        $eventName = class_basename($event);
        $fullEvent = trim($event, '\\');

        $replace = [
            'DummyEvent' => $eventName,
            '{{ event }}' => $eventName,
            '{{event}}' => $eventName,
            'DummyFullEvent' => $fullEvent,
            '{{ fullEvent }}' => $fullEvent,
            '{{fullEvent}}' => $fullEvent,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('queued')) {
            return $this->option('event')
                        ? $this->resolveStubPath('/stubs/listener-queued.stub')
                        : $this->resolveStubPath('/stubs/listener-queued-duck.stub');
        }

        return $this->option('event')
                    ? $this->resolveStubPath('/stubs/listener.stub')
                    : $this->resolveStubPath('/stubs/listener-duck.stub');
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

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($rawName);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Listeners';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for'],

            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued'],
        ];
    }
}
