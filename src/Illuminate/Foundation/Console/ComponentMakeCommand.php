<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:component')]
class ComponentMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view component class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Component';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if ($this->option('view')) {
            $this->writeView();

            return null;
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if (! $this->option('inline')) {
            $this->writeView();
        }
    }

    /**
     * Write the view for the component.
     *
     * @return void
     */
    protected function writeView()
    {
        $path = $this->viewPath(
            str_replace('.', '/', $this->getViewPath()) . '.blade.php'
        );

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error('View already exists.');

            return;
        }

        file_put_contents(
            $path,
            '<div>
    <!-- ' . Inspiring::quotes()->random() . ' -->
</div>'
        );

        $this->components->info(sprintf('%s [%s] created successfully.', 'View', $path));
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        if ($this->option('inline')) {
            return str_replace(
                ['DummyView', '{{ view }}'],
                "<<<'blade'\n<div>\n    <!-- " . Inspiring::quotes()->random() . " -->\n</div>\nblade",
                parent::buildClass($name)
            );
        }

        return str_replace(
            ['DummyView', '{{ view }}'],
            "view('{$this->getViewName()}')",
            parent::buildClass($name)
        );
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        $name = trim($this->argument('name'));

        $normalized = str_replace(['.', '\\'], '/', $name);

        return (new Collection(explode('/', $normalized)))
            ->map(fn($segment) => Str::studly($segment))
            ->implode('/');
    }

    /**
     * Get the full view path including the base directory.
     *
     * @return string
     */
    protected function getViewPath()
    {
        $segments = $this->getViewSegments();

        if ($customPath = $this->option('path')) {
            $basePath = trim($customPath, '/');
            return $basePath . '.' . $segments->implode('.');
        }

        return 'components.' . $segments->implode('.');
    }

    /**
     * Get the view name for referencing in the component class.
     *
     * @return string
     */
    protected function getViewName()
    {
        $segments = $this->getViewSegments();

        if ($customPath = $this->option('path')) {
            $basePath = str_replace('/', '.', trim($customPath, '/'));
            return $basePath . '.' . $segments->implode('.');
        }

        return 'components.' . $segments->implode('.');
    }

    /**
     * Get the view name segments in kebab-case.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getViewSegments()
    {
        $name = trim($this->argument('name'));

        $normalized = str_replace(['.', '\\'], '/', $name);

        return (new Collection(explode('/', $normalized)))
            ->map(fn($segment) => Str::kebab($segment));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/view-component.stub');
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
            : __DIR__ . $stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\View\Components';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['inline', null, InputOption::VALUE_NONE, 'Create a component that renders an inline view'],
            ['view', null, InputOption::VALUE_NONE, 'Create an anonymous component with only a view'],
            ['path', null, InputOption::VALUE_REQUIRED, 'The location where the component view should be created'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the component already exists'],
        ];
    }
}
