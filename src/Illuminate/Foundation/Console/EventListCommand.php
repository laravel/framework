<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:list';

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
        $this->table(['Event', 'Listeners'], $this->getEvents());
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

        return collect($events)->map(function ($value, $key) {
            return ['Event' => $key, 'Listeners' => implode("\n", $value)];
        })->values()->toArray();
    }
}
