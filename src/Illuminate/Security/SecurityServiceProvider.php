<?php

namespace Illuminate\Security;

use Illuminate\Cache\RateLimiter;
use Illuminate\Security\Sensors\RateLimitingSensor;
use Illuminate\Security\Sensors\SqlInjectionSensor;
use Illuminate\Security\Sensors\XssSensor;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(IdsManager::class, function ($app) {
            $manager = new IdsManager($app);

            // Register default sensors
            $manager->addSensor(new SqlInjectionSensor());
            $manager->addSensor(new XssSensor());
            $manager->addSensor(new RateLimitingSensor($app[RateLimiter::class]));

            return $manager;
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/security.php' => config_path('security.php'),
        ], 'laravel-security');
    }
} 