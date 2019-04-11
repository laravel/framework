<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:list {--event= : The event name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "List the application's events and listeners";

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Event', 'Listeners'];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = $this->getEvents();

        if (empty($events)) {
            if ($this->isSearching()) {
                return $this->error('Your application doesn\'t have any events matching the given criteria.');
            }

            return $this->error('Your application doesn\'t has any events, listeners.');
        }

        $this->displayEvents($events);
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
            $providerEvents = array_merge($provider->discoverEvents(), $provider->listens());

            $events = array_merge_recursive($events, $providerEvents);
        }

        if ($this->isSearching()) {
            $events = $this->filterEvents($events);
        }

        return collect($events)->map(function ($listeners, $event) {
            return ['Event' => $event, 'Listeners' => implode(PHP_EOL, $listeners)];
        })->sortBy('Event')->values()->toArray();
    }

    /**
     * Determine whether the user is searching event.
     *
     * @return bool
     */
    protected function isSearching()
    {
        return $this->input->hasParameterOption('--event');
    }

    /**
     * Filter the given events.
     *
     * @param  array  $events
     * @return array
     */
    protected function filterEvents(array $events)
    {
        return collect($events)->filter(function ($listeners, $event) {
            if ($this->option('event')) {
                return Str::contains($event, $this->option('event'));
            }

            return true;
        })->toArray();
    }

    /**
     * Display the event listeners information on the console.
     *
     * @param  array  $events
     * @return void
     */
    protected function displayEvents(array $events)
    {
        $this->table($this->headers, $events);
    }
}
