<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin ServiceProvider
 *
 * @property string|null $namespace - The controller namespace for the application.
 * @property \Closure|null $loadRoutesUsing - The callback that should be used to load the application's routes.
 */
trait WithRoutes
{
    use ForwardsCalls;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function registerWithRoutes()
    {
        $this->booted(function () {
            $this->setRootControllerNamespace();

            if ($this->routesAreCached()) {
                $this->loadCachedRoutes();
            } else {
                $this->loadRoutes();

                $this->app->booted(function () {
                    $this->app['router']->getRoutes()->refreshNameLookups();
                    $this->app['router']->getRoutes()->refreshActionLookups();
                });
            }
        });
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param  \Closure  $routesCallback
     * @return $this
     */
    protected function routes(\Closure $routesCallback)
    {
        $this->loadRoutesUsing = $routesCallback;

        return $this;
    }

    /**
     * Get the route namespace.
     *
     * @return string|null
     */
    protected function getNamespace()
    {
        return $this->namespace ?? null;
    }

    /**
     * Set the root controller namespace for the application.
     *
     * @return void
     */
    protected function setRootControllerNamespace()
    {
        if (!is_null($this->getNamespace())) {
            $this->app[UrlGenerator::class]->setRootControllerNamespace($this->getNamespace());
        }
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    protected function routesAreCached()
    {
        return $this->app->routesAreCached();
    }

    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * Load the application routes.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        if (!is_null($this->loadRoutesUsing ?? null)) {
            $this->app->call($this->loadRoutesUsing);
        } elseif (method_exists($this, 'map')) {
            $this->app->call([$this, 'map']);
        }
    }

    /**
     * Pass dynamic methods onto the router instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->app->make(Router::class), $method, $parameters
        );
    }
}