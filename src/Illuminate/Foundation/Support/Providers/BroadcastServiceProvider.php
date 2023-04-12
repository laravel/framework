<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (file_exists(base_path('routes/channels.php'))) {
            Broadcast::routes();

            require base_path('routes/channels.php');
        }
    }
}
