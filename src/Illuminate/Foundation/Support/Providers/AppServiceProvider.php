<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Notifications channels list.
     *
     * @var array
     */
    protected $notificationsChannels = [];

    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerNotificationsChannels();
    }

    /**
     * Register Notifications channels.
     *
     * @return void
     */
    public function registerNotificationsChannels()
    {
        if(empty($this->notificationsChannels))
            return;

        $manager = $this->app->make(ChannelManager::class);

        foreach ($this->notificationsChannels as $channel) {
            if (class_exists($channel)) {
                $manager->register($channel);
            }
        }
    }
}