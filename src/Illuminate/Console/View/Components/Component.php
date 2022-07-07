<?php

namespace Illuminate\Console\View\Components;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\QuestionHelper;
use Illuminate\Support\Facades\Blade;
use ReflectionClass;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use function Termwind\render;
use function Termwind\renderUsing;

abstract class Component
{
    /**
     * The output style implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * The list of mutators to apply on the view data.
     *
     * @var array<int, callable(string): string>
     */
    protected $mutators;

    /**
     * Creates a new component instance.
     *
     * @param  \Illuminate\Console\OutputStyle  $output
     * @return void
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * Renders the given view.
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  int  $verbosity
     * @return void
     */
    protected function renderView($view, $data, $verbosity)
    {
        $view = file_get_contents(
            __DIR__."/../../resources/views/components/$view.blade.php"
        );

        renderUsing($this->output);
        render((string) Blade::render($view, $data), $verbosity);
    }

    /**
     * Mutates the given data with the given set of mutators.
     *
     * @param  array<int, string>|string  $data
     * @param  array<int, callable(string): string>  $mutators
     * @return array<int, string>|string
     */
    protected function mutate($data, $mutators)
    {
        foreach ($mutators as $mutator) {
            if (is_iterable($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = resolve($mutator)->__invoke($value);
                }
            } else {
                $data = resolve($mutator)->__invoke($data);
            }
        }

        return $data;
    }

    /**
     * Eventually performs a question using the component's question helper.
     *
     * @param  callable  $callable
     * @return mixed
     */
    protected function usingQuestionHelper($callable)
    {
        $property = with(new ReflectionClass(OutputStyle::class))
            ->getParentClass()
            ->getProperty('questionHelper');

        $currentHelper = $property->isInitialized($this->output)
            ? $property->getValue($this->output)
            : new SymfonyQuestionHelper();

        $property->setValue($this->output, new QuestionHelper);

        try {
            return $callable();
        } finally {
            $property->setValue($this->output, $currentHelper);
        }
    }
}
