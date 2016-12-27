<?php

namespace Illuminate\View\Concerns;

use Illuminate\Support\HtmlString;

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
     * @param  string  $name
     * @param  array  $data
     * @return void
     */
    public function startComponent($name, array $data = [])
    {
        if (ob_start()) {
            $this->componentStack[] = $name;

            $this->componentData[$name] = $data;

            $this->slots[$name] = [];
        }
    }

    /**
     * Render the current component.
     *
     * @return string
     */
    public function renderComponent()
    {
        $name = array_pop($this->componentStack);

        return tap($this->make($name, $this->componentData($name))->render(), function () use ($name) {
            $this->resetComponent($name);
        });
    }

    /**
     * Get the data for the given component.
     *
     * @param  string  $name
     * @return array
     */
    protected function componentData($name)
    {
        $slot = ['slot' => new HtmlString(trim(ob_get_clean()))];

        return array_merge($this->componentData[$name], $slot, $this->slots[$name]);
    }

    /**
     * Start the slot rendering process.
     *
     * @param  string  $name
     * @return void
     */
    public function slot($name)
    {
        if (ob_start()) {
            $this->slots[last($this->componentStack)][$name] = '';

            $this->slotStack[last($this->componentStack)][] = $name;
        }
    }

    /**
     * Save the slot content for rendering.
     *
     * @return void
     */
    public function endSlot()
    {
        $current = last($this->componentStack);

        $currentSlot = array_pop($this->slotStack[$current]);

        $this->slots[$current][$currentSlot] = new HtmlString(trim(ob_get_clean()));
    }

    /**
     * Reset the state for the given component.
     *
     * @param  string  $name
     * @return void
     */
    protected function resetComponent($name)
    {
        unset($this->slots[$name]);
        unset($this->slotStack[$name]);
        unset($this->componentData[$name]);
    }
}
