<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Broadcasting\Factory as BroadcasterContract;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * The channel auth handler mappings for the application.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Register the application's channel authenticators.
     *
     * @param  \Illuminate\Contracts\Broadcasting\Factory  $broadcaster
     * @return void
     */
    public function boot(BroadcasterContract $broadcaster)
    {
        foreach ($this->channels() as $channel => $authenticators) {
            foreach ($authenticators as $authenticator) {
                $broadcaster->auth($channel, $authenticator);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }

    /**
     * Get the channels and handlers.
     *
     * @return array
     */
    public function channels()
    {
        return $this->channels;
    }
}
