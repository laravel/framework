<?php

namespace Illuminate\Console\View\Components;

use Illuminate\Support\Facades\View;
use function Termwind\render;
use function Termwind\renderUsing;

abstract class Component
{
    /**
     * The output instance.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
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
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * Creates a new component instance from the given output.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return static
     */
    public static function fromOutput($output)
    {
        return new static($output);
    }

    /**
     * Renders the given view.
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  int  $verbosity
     * @return void
     */
    public function renderView($view, $data, $verbosity)
    {
        $this->ensureViewRequirements();

        renderUsing($this->output);

        render(view('illuminate.console::'.$view, $data), $verbosity);
    }

    /**
     * Ensure with view requirements, such as the namespace, are loaded.
     *
     * @return void
     */
    protected function ensureViewRequirements()
    {
        View::replaceNamespace('illuminate.console', __DIR__.'/../../resources/views');
    }

    /**
     * Mutates the given data with the given set of mutators.
     *
     * @param  array<int, string>|string  $data
     * @param  array<int, callable(string): string>  $mutators
     * @return array<int, string>|string
     */
    public function mutate($data, $mutators)
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
}
