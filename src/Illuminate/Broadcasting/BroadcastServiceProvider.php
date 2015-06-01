<?php namespace Illuminate\Broadcasting;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class BroadcastServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BroadcastManager::class, function ($app) {
            return new BroadcastManager($app);
        });

        $this->app->singleton(Broadcaster::class, function ($app) {
            return $app->make(BroadcastManager::class)->connection();
        });

        $this->app->alias(BroadcastManager::class, Factory::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            BroadcastManager::class,
            Factory::class,
            Broadcaster::class,
        ];
    }
}
