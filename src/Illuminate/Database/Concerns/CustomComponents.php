<?php

namespace Illuminate\Database\Concerns;

trait CustomComponents
{
    /**
     * An array of custom components for the current instance.
     *
     * @var array<string, mixed>
     */
    protected $customComponents = [];

    /**
     * Sets a custom component for the current instance.
     *
     * @param  string  $component
     * @param  mixed  $data
     * @return $this
     */
    public function setComponent($component, $data)
    {
        $this->customComponents[$component] = $data;

        return $this;
    }

    /**
     * Retrieves a component data, or null if it doesn't exist.
     *
     * @param  string  $component
     * @param  mixed|null  $default
     * @return mixed|null
     */
    public function getComponent($component, $default = null)
    {
        return $this->customComponents[$component] ?? value($default, $this);
    }

    /**
     * Determine a custom component has been registered in the instance.
     *
     * @param  string  $component
     * @return bool
     */
    public function hasComponent($component)
    {
        return isset($this->customComponents[$component]);
    }
}
