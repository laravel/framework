<?php

namespace Illuminate\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthenticator();

        $this->registerUserResolver();

        $this->registerAccessGate();

        $this->registerRequestRebindHandler();
    }

    /**
     * Register the authenticator services.
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', function (Application $app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app->bind('auth.loaded', function () {
                return true;
            });

            return new AuthManager($app);
        });

        $this->app->singleton('auth.driver', function (Application $app) {
            return $app->make('auth')->guard();
        });
    }

    /**
     * Register a resolver for the authenticated user.
     *
     * @return void
     */
    protected function registerUserResolver()
    {
        $this->app->bind(
            AuthenticatableContract::class, function (Application $app) {
                return call_user_func($app->make('auth')->userResolver());
            }
        );
    }

    /**
     * Register the access gate service.
     *
     * @return void
     */
    protected function registerAccessGate()
    {
        $this->app->singleton(GateContract::class, function (Application $app) {
            return new Gate($app, function () use ($app) {
                return call_user_func($app->make('auth')->userResolver());
            });
        });
    }

    /**
     * Register a resolver for the authenticated user.
     *
     * @return void
     */
    protected function registerRequestRebindHandler()
    {
        $this->app->rebinding('request', function (Application $app, Request $request) {
            $request->setUserResolver(function ($guard = null) use ($app) {
                return call_user_func($app->make('auth')->userResolver(), $guard);
            });
        });
    }
}
