<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

trait WithCachedRoutes
{
    /**
     * After creating the routes once, we can cache them for the remaining tests.
     *
     * @return void
     */
    protected function setUpWithCachedRoutes(): void
    {
        // If we haven't stored the cached routes yet, then let's store them
        // once so we can use them in the remaining tests.
        if ((CachedState::$cachedRoutes ?? null) === null) {
            $routes = $this->app['router']->getRoutes();
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
            CachedState::$cachedRoutes = $routes->compile();
        }

        $this->markRoutesCached($this->app);
    }

    protected function tearDownWithCachedRoutes(): void
    {
        RouteServiceProvider::loadCachedRoutesUsing(null);
    }

    protected function markRoutesCached(Application $app): void
    {
        $app->instance('routes.cached', true);

        RouteServiceProvider::loadCachedRoutesUsing(
            static fn () => app('router')->setCompiledRoutes(CachedState::$cachedRoutes)
        );
    }

}
