<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class ObserverCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'observer:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Discover and cache the application's observers";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('observer:clear');

        file_put_contents(
            $this->laravel->getCachedObserversPath(),
            '<?php return '.var_export($this->getObservers(), true).';'
        );

        $this->info('Observers cached successfully!');
    }

    /**
     * Get all of the observers configured for the application.
     *
     * @return array
     */
    protected function getObservers()
    {
        $observers = [];

        foreach ($this->laravel->getProviders(EventServiceProvider::class) as $provider) {
            $providerObservers = array_merge_recursive($provider->shouldDiscoverObservers() ? $provider->discoverObservers() : [], $provider->observes());

            $observers[get_class($provider)] = $providerObservers;
        }

        return $observers;
    }
}
