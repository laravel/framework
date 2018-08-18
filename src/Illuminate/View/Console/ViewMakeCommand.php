<?php

namespace Illuminate\View\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ViewMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view file';

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/view.stub';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return $this->getViewPath().'/'.$this->getFileName();
    }

    /**
     * Get the views folder path.
     *
     * @return string
     */
    protected function getViewPath()
    {
        if ($path = $this->option('path')) {
            return $path;
        }

        return $this->laravel['config']['view.paths'][0];
    }

    /**
     * Get the view file path.
     *
     * @return string
     */
    protected function getFileName()
    {
        return $this->getNameInput().'.blade.php';
    }

    /**
     * Replace the view dots syntax with slashes.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return str_replace('.', '/', parent::getNameInput());
    }

    /**
     * Build the view file with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $replace = $this->buildViewReplacements([]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->files->get($this->getStub())
        );
    }

    /**
     * Build the view replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildViewReplacements(array $replace)
    {
        return array_merge($replace, [
            'ParentView' => $this->option('extends'),
            'ContentSection' => $this->option('section'),
        ]);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'Where to store the view file.'],
            ['extends', 'e', InputOption::VALUE_OPTIONAL, 'The parent view.'],
            ['section', 's', InputOption::VALUE_OPTIONAL, 'The section of the content.'],
        ];
    }
}
