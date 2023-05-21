<?php

namespace Illuminate\Foundation\Configuration;

use Closure;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as AppEventServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as AppRouteServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

class ApplicationBuilder
{
    /**
     * Create a new application builder instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Register the standard kernel classes for the application.
     *
     * @return $this
     */
    public function withKernels()
    {
        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \Illuminate\Foundation\Http\Kernel::class,
        );

        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \Illuminate\Foundation\Console\Kernel::class,
        );

        return $this;
    }

    /**
     * Register the core event service provider for the application.
     *
     * @return $this
     */
    public function withEvents()
    {
        $this->app->booting(function () {
            $this->app->register(AppEventServiceProvider::class);
        });

        return $this;
    }

    /**
     * Register the braodcasting services for the application.
     *
     * @return $this
     */
    public function withBroadcasting()
    {
        $this->app->booted(function () {
            Broadcast::routes();

            if (file_exists($channels = $this->app->basePath('routes/channels.php'))) {
                require $channels;
            }
        });

        return $this;
    }

    /**
     * Register the routing services for the application.
     *
     * @param  \Closure|null  $callback
     * @param  string|null  $web
     * @param  string|null  $api
     * @param  string|null  $apiPrefix
     * @param  callable|null  $then
     * @return $this
     */
    public function withRouting(?Closure $callback = null,
        ?string $web = null,
        ?string $api = null,
        string $apiPrefix = 'api',
        ?callable $then = null)
    {
        if (is_null($callback) && (is_string($web) || is_string($api))) {
            $callback = function () use ($web, $api, $apiPrefix, $then) {
                if (is_string($api)) {
                    Route::middleware('api')->prefix($apiPrefix)->group($api);
                }

                if (is_string($web)) {
                    Route::middleware('web')->group($web);
                }

                if (is_callable($then)) {
                    $then();
                }
            };
        }

        AppRouteServiceProvider::loadRoutesUsing($callback);

        $this->app->booting(function () {
            $this->app->register(AppRouteServiceProvider::class);
        });

        return $this;
    }

    /**
     * Register the global middleware, middleware groups, and middleware aliases for the application.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withMiddleware(callable $callback)
    {
        $this->app->afterResolving(HttpKernel::class, function ($kernel) use ($callback) {
            $callback($middleware = new Middleware);

            $kernel->setGlobalMiddleware($middleware->getGlobalMiddleware());
            $kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());
            $kernel->setMiddlewareAliases($middleware->getMiddlewareAliases());
        });

        return $this;
    }

    /**
     * Register the standard exception handler for the application.
     *
     * @param  callable|null  $afterResolving
     * @return $this
     */
    public function withExceptionHandling($afterResolving = null)
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Illuminate\Foundation\Exceptions\Handler::class
        );

        $this->app->afterResolving(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            $afterResolving ?: fn ($handler) => $handler
        );

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booting".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booting($callback)
    {
        $this->app->booting($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booted".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booted($callback)
    {
        $this->app->booted($callback);

        return $this;
    }

    /**
     * Get the application instance.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function create()
    {
        return $this->app;
    }
}
