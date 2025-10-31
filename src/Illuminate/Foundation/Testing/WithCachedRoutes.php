<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

trait WithCachedRoutes
{
    protected static array $cachedRoutes = [];

    protected function setUpWithCachedRoutes(): void
    {
        $this->app->instance('routes.cached', true);

        if ((self::$cachedRoutes ?? null) === null) {
            $routes = $this->app['router']->getRoutes();
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
            self::$cachedRoutes = $routes;
        }

        RouteServiceProvider::loadCachedRoutesUsing(
            static fn () => app('router')->setCompiledRoutes(self::$cachedRoutes)
        );
    }

    protected function tearDownWithCachedRoutes(): void
    {
        RouteServiceProvider::loadCachedRoutesUsing(null);
    }
}
