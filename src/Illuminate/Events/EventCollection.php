<?php

namespace Illuminate\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

class EventCollection extends Collection
{
    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $dispatcher;

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return static
     */
    public function setDispatcher(Dispatcher $dispatcher): static
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Dispatch all captured events.
     *
     * @return void
     */
    public function dispatch(): void
    {
        foreach ($this->items as $args) {
            $this->dispatcher->dispatch(...$args);
        }
    }
}
