<?php

namespace Illuminate\View\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentSlot;

trait ManagesComponents
{
    /**
     * The components being rendered.
     *
     * @var array
     */
    protected $componentStack = [];

    /**
     * The original data passed to the component.
     *
     * @var array
     */
    protected $componentData = [];

    /**
     * The slot contents for the component.
     *
     * @var array
     */
    protected $slots = [];

    /**
     * The names of the slots being rendered.
     *
     * @var array
     */
    protected $slotStack = [];

    /**
     * Start a component rendering process.
     *
     * @param  \Illuminate\Contracts\View\View|\Illuminate\Contracts\Support\Htmlable|\Closure|string  $view
     * @param  array  $data
     * @return void
     */
    public function startComponent($view, array $data = [])
    {
        if (ob_start()) {
            $this->componentStack[] = $view;

            $this->componentData[$this->currentComponent()] = $data;

            $this->slots[$this->currentComponent()] = [];
        }
    }

    /**
     * Get the first view that actually exists from the given list, and start a component.
     *
     * @param  array  $names
     * @param  array  $data
     * @return void
     */
    public function startComponentFirst(array $names, array $data = [])
    {
        $name = Arr::first($names, function ($item) {
            return $this->exists($item);
        });

        $this->startComponent($name, $data);
    }

    /**
     * Render the current component.
     *
     * @return string
     */
    public function renderComponent()
    {
        $view = array_pop($this->componentStack);

        $data = $this->componentData();

        $view = value($view, $data);

        if ($view instanceof View) {
            return $view->with($data)->render();
        } elseif ($view instanceof Htmlable) {
            return $view->toHtml();
        } else {
            return $this->make($view, $data)->render();
        }
    }

    /**
     * Get the data for the given component.
     *
     * @return array
     */
    protected function componentData()
    {
        $defaultSlot = new HtmlString(trim(ob_get_clean()));

        $slots = array_merge([
            '__default' => $defaultSlot,
        ], $this->slots[count($this->componentStack)]);

        return array_merge(
            $this->componentData[count($this->componentStack)],
            ['slot' => $defaultSlot],
            $this->slots[count($this->componentStack)],
            ['__laravel_slots' => $slots]
        );
    }

    /**
     * Start the slot rendering process.
     *
     * @param  string  $name
     * @param  string|null  $content
     * @param  array  $attributes
     * @return void
     */
    public function slot($name, $content = null, $attributes = [])
    {
        if (func_num_args() === 2 || $content !== null) {
            $this->slots[$this->currentComponent()][$name] = $content;
        } elseif (ob_start()) {
            $this->slots[$this->currentComponent()][$name] = '';

            $this->slotStack[$this->currentComponent()][] = [$name, $attributes];
        }
    }

    /**
     * Save the slot content for rendering.
     *
     * @return void
     */
    public function endSlot()
    {
        last($this->componentStack);

        $currentSlot = array_pop(
            $this->slotStack[$this->currentComponent()]
        );

        [$currentName, $currentAttributes] = $currentSlot;

        $this->slots[$this->currentComponent()][$currentName] = new ComponentSlot(
            trim(ob_get_clean()), $currentAttributes
        );
    }

    /**
     * Get the index for the current component.
     *
     * @return int
     */
    protected function currentComponent()
    {
        return count($this->componentStack) - 1;
    }
}
