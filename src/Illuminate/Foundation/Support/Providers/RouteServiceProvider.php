<?php

namespace Illuminate\Foundation\Support\Providers;

use Closure;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Illuminate\Routing\Router
 */
class RouteServiceProvider extends ServiceProvider
{
    use ForwardsCalls;

    /**
     * The controller namespace for the application.
     *
     * @var string|null
     */
    protected $namespace;

    /**
     * The callback that should be used to load the application's routes.
     *
     * @var \Closure|null
     */
    protected $loadRoutesUsing;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
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
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param  \Closure|null  $routesCallback
     * @param  string|null  $web
     * @param  string|null  $api
     * @return $this
     */
    protected function routes(?Closure $routesCallback = null, ?string $web = null, ?string $api = null)
    {
        if (is_null($web) && is_null($api)) {
            $this->loadRoutesUsing = $routesCallback;
        } else {
            $this->loadRoutesUsing = function () use ($web, $api) {
                if ($api) {
                    $this->app[Router::class]
                        ->middleware('api')
                        ->prefix('api')
                        ->group($api);
                }

                if ($web) {
                    $this->app[Router::class]
                        ->middleware('web')
                        ->group($web);
                }
            };
        }

        return $this;
    }

    /**
     * Set the root controller namespace for the application.
     *
     * @return void
     */
    protected function setRootControllerNamespace()
    {
        if (! is_null($this->namespace)) {
            $this->app[UrlGenerator::class]->setRootControllerNamespace($this->namespace);
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
        if (! is_null($this->loadRoutesUsing)) {
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
