<?php

namespace Illuminate\View\Console;

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
     * The name of file being generated.
     *
     * @var string
     */
    protected $fileName = null;
    
    /**
     * The type of file being generated.
     *
     * @var string
     */
    protected $type = 'View';


    /**
     * The full path of file.
     *
     * @var string
     */
    protected $viewDirectoryPath = null;

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('section')) {
            return __DIR__ . '/stubs/view.section.stub';
        }

        if ($this->option('extends')) {
            return __DIR__ . '/stubs/view.extends.stub';
        }

        return __DIR__ . '/stubs/view.plain.stub';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['extends', null, InputOption::VALUE_OPTIONAL, 'Select extends file path.'],
            ['section', null, InputOption::VALUE_OPTIONAL, 'Select section name.'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->createDirectories();

        if ($this->alreadyExists($this->viewDirectoryPath.$this->fileName.'.blade.php')) {
            $this->error($this->type . ' already exists!');
            return false;
        }

        $this->makeView();

        if ($this->option('extends')) {
            file_put_contents($this->viewDirectoryPath . $this->fileName . '.blade.php', str_replace('extend_name', $this->option('extends'), file_get_contents($this->viewDirectoryPath . $this->fileName . '.blade.php')));
        }
        if ($this->option('section')) {
            file_put_contents($this->viewDirectoryPath . $this->fileName . '.blade.php', str_replace('section_name', $this->option('section'), file_get_contents($this->viewDirectoryPath . $this->fileName . '.blade.php')));
        }

        $this->info('View ' . $this->fileName . ' generated successfully!');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (strpos($this->getNameInput(), '.')) {
            $paths = explode('.', $this->getNameInput());
            // remove file name
            $this->fileName = array_pop($paths);
            // concatinate the path in loop
            $last_path = '';

            foreach ($paths as $path) {
                if (!is_dir(base_path('resources/views/' . $last_path . $path))) {
                    mkdir(base_path('resources/views/' . $last_path . $path), 0755, true);
                }
                $last_path .= $path . '/';
            }
            $this->viewDirectoryPath = base_path('resources/views/' . $last_path);
        }
    }

    /**
     * Generate the view file.
     *
     * @return void
     */
    protected function makeView()
    {
        $path = $this->viewDirectoryPath;
        file_put_contents($path . $this->fileName . '.blade.php', file_get_contents($this->getStub()));
    }

     /**
     * Determine if the view already exists.
     *
     * @param  string  $path
     * @return bool
     */
    protected function alreadyExists($path)
    {
        return $this->files->exists($path);
    }
}
