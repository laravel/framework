<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Events\DiscoverEvents;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->listens() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);
        }
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return array_merge($this->discoverEvents(), $this->listen);
    }

    /**
     * Discover the events and listeners for the application.
     *
     * @return array
     */
    protected function discoverEvents()
    {
        return collect($this->discoverEventsWithin())
                    ->reduce(function ($discovered, $directory) {
                        return array_merge(
                            $discovered,
                            DiscoverEvents::within($directory, base_path())
                        );
                    }, []);
    }

    /**
     * Get the listener directories that should be used to discover events.
     *
     * @return array
     */
    protected function discoverEventsWithin()
    {
        return [
            app_path('Listeners'),
        ];
    }
}
