<?php

namespace Illuminate\Database\Eloquent\Factories;

use Illuminate\Support\Collection;

trait DisablesEvents
{
    protected bool $disableEvents;

    /**
     * Disables events during creation.
     *
     * @return self
     */
    public function withoutEvents()
    {
        $this->disableEvents = true;

        return $this;
    }

    /**
     * Enables events during creation
     *
     * @return self
     */
    public function withEvents()
    {
        $this->disableEvents = false;

        return $this;
    }

    /**
     * Checks if events should be disabled for creation.
     */
    protected function shouldDisableEvents(): bool
    {
        if (isset($this->disableEvents) && ! is_null($this->disableEvents)) {
            return $this->disableEvents;
        }

        return false;
    }

    /**
     * Set the connection name on the results and store them.
     *
     * @param  \Illuminate\Support\Collection  $results
     *
     * @return void
     */
    protected function store(Collection $results)
    {
        $this->shouldDisableEvents()
            ? $this->model::withoutEvents(function () use ($results) {
                return parent::store($results);
            })
            : parent::store($results);
    }

    /**
     * Create a new instance of the factory builder with the given mutated properties.
     * This propagates the event disabler across states.
     *
     * @param  array  $arguments
     *
     * @return static
     */
    protected function newInstance(array $arguments = [])
    {
        $newInstance = parent::newInstance($arguments);

        return $this->shouldDisableEvents()
            ? $newInstance->withoutEvents()
            : $newInstance->withEvents();
    }
}
