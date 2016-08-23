<?php
namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
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
    protected $description = 'Create a new view';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'View';

    /**
     * Determine if the class already exists.
     *
     * @param  string $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return file_exists($this->getPath($this->parseName($rawName)));
    }

    /**
     * Parse the name and format according to the root namespace.
     *
     * @param  string $name
     * @return string
     */
    protected function parseName($name)
    {
        if (Str::contains($name, '\\')) {
            $name = str_replace('\\', '/', $name);
        }
        if (Str::contains($name, '.')) {
            $name = str_replace('.', '/', $name);
        }

        return $name;
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
     * @param  string $name
     * @return string
     */
    protected function getPath($name)
    {
        return resource_path('/views/'.$name.'.blade.php');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        $parent = $this->option('parent');
        $section = $this->option('section');
        $class = $this->option('class');
        $stacks = $this->option('stacks');
        $this->replaceParentView($stub, $parent)->replaceSection($stub, $section, $class)->insertStacks($stub, $stacks);

        return $stub;
    }

    /**
     * Replace the Parent View Name for the given stub.
     *
     * @param  string $stub
     * @param  string $parentViewName
     * @return $this
     */
    protected function replaceParentView(&$stub, $parentViewName)
    {
        $stub = str_replace(
            'DummyParentView', $parentViewName, $stub
        );

        return $this;
    }

    /**
     * Replace the Section Name for the given stub.
     *
     * @param  string $stub
     * @param  string $sectionName
     * @param  string $class
     * @return $this
     */
    protected function replaceSection(&$stub, $sectionName, $class)
    {
        $stub = str_replace(
            'DummySection', $sectionName, $stub
        );
        if ($class != 'false') {
            $divStart = "<div class='{$class}'>".PHP_EOL;
            $divEnd = PHP_EOL.'</div>';
        } else {
            $divStart = null;
            $divEnd = null;
        }
        $stub = str_replace(
            '<DumyDiv>', $divStart, $stub
        );
        $stub = str_replace(
            '</DumyDiv>', $divEnd, $stub
        );

        return $this;
    }

    /**
     * Inserts the Stacks at the end of the stub.
     *
     * @param  string $stub
     * @param  string $sectionName
     * @return $this
     */
    protected function insertStacks(&$stub, array $stacks = [])
    {
        if (! empty($stacks)) {
            $stub_stack = PHP_EOL.'@stack(\'DummyStack\')'.PHP_EOL.PHP_EOL.'@endstack';

            foreach ($stacks as $stack) {
                $stub = str_replace('DummyStack', $stack, ($stub.$stub_stack));
            }
        }

        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the view'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['parent', null, InputOption::VALUE_REQUIRED, 'The parent view that would be extended.', 'layouts.app'],

            ['section', null, InputOption::VALUE_REQUIRED, 'The section where your content is placed', 'content'],

            ['class', null, InputOption::VALUE_OPTIONAL, 'Defines the default bootstrap class that wrapps your content. [false for disable]', 'container'],

            ['stacks', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Creates stackes'],
        ];
    }
}
