<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Str;
use ReflectionFunction;

class EventListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:list {--event= : Filter the events by name}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'event:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "List the application's events and listeners";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = $this->getEvents();

        if (empty($events)) {
            return $this->error("Your application doesn't have any events matching the given criteria.");
        }

        $this->table(['Event', 'Listeners'], $events);
    }

    /**
     * Get all of the events and listeners configured for the application.
     *
     * @return array
     */
    protected function getEvents()
    {
        $events = [];

        foreach ($this->laravel->getProviders(EventServiceProvider::class) as $provider) {
            $providerEvents = array_merge_recursive($provider->shouldDiscoverEvents() ? $provider->discoverEvents() : [], $provider->listens());

            $events = array_merge_recursive($events, $providerEvents);
        }

        $events = $this->addListenersOnDispatcher($events);

        if ($this->filteringByEvent()) {
            $events = $this->filterEvents($events);
        }

        return collect($events)->map(function ($listeners, $event) {
            return ['Event' => $event, 'Listeners' => implode(PHP_EOL, $listeners)];
        })->sortBy('Event')->values()->toArray();
    }

    /**
     * Filter the given events using the provided event name filter.
     *
     * @param  array  $events
     * @return array
     */
    protected function filterEvents(array $events)
    {
        if (! $eventName = $this->option('event')) {
            return $events;
        }

        return collect($events)->filter(function ($listeners, $event) use ($eventName) {
            return Str::contains($event, $eventName);
        })->toArray();
    }

    /**
     * Determine whether the user is filtering by an event name.
     *
     * @return bool
     */
    protected function filteringByEvent()
    {
        return ! empty($this->option('event'));
    }

    /**
     * Adds the event/listeners on the dispatcher object to the given list.
     *
     * @param  array  $events
     *
     * @return array
     */
    protected function addListenersOnDispatcher(array $events)
    {
        foreach ($this->getRawListeners() as $event => $rawListeners) {
            foreach ($rawListeners as $rawListener) {
                if (is_string($rawListener)) {
                    $events[$event][] = $rawListener;
                } elseif ($rawListener instanceof Closure) {
                    $events[$event][] = $this->stringifyClosure($rawListener);
                }
            }
        }

        return $events;
    }

    /**
     * Creates a printable string version of a closure.
     *
     * @param  Closure  $rawListener
     *
     * @return string
     */
    protected function stringifyClosure(Closure $rawListener)
    {
        $reflection = new ReflectionFunction($rawListener);
        $path = str_replace(base_path(), '', $reflection->getFileName() ?: '');

        return 'Closure at: '.$path.':'.$reflection->getStartLine();
    }

    /**
     * Gets the raw version of event listeners from dispatcher object.
     *
     * @return array
     */
    protected function getRawListeners()
    {
        try {
            return $this->getLaravel()->make('events')->getRawListeners();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
