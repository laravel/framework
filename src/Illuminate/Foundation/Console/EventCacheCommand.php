<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Discover and cache the application's events and listeners";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('event:clear');

        file_put_contents(
            $this->laravel->getCachedEventsPath(),
            '<?php return '.var_export($this->getEvents(), true).';'
        );

        $this->info('Events cached successfully!');
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

        return $events;
    }
}
