<?php

namespace Illuminate\Auth\VerifyEmails;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\VerifyEmails\DatabaseTokenRepository as DbRepository;

class VerifyEmailServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerVerifyEmailBroker();

        $this->registerTokenRepository();
    }

    /**
     * Register the verify email broker instance.
     *
     * @return void
     */
    protected function registerVerifyEmailBroker()
    {
        $this->app->singleton('auth.verify_email', function ($app) {
            // The token repository is responsible for storing the email addresses and
            // email verification tokens. It will be used to verify the tokens are valid
            // for the given e-mail addresses. We will resolve an implementation here.
            $tokens = $app['auth.verify_email.tokens'];

            $users = $app['auth']->driver()->getProvider();

            $view = $app['config']['auth.verify_email.email'];

            // The verify email broker uses a token repository to validate tokens, as well
            // as validating that email verification process as an aggregate service of
            // sorts providing a convenient interface for verification.
            return new VerifyEmailBroker($tokens, $users, $app['mailer'], $view);
        });
    }

    /**
     * Register the token repository implementation.
     *
     * @return void
     */
    protected function registerTokenRepository()
    {
        $this->app->singleton('auth.verify_email.tokens', function ($app) {
            $connection = $app['db']->connection();

            // The database token repository is an implementation of the token repository
            // interface, and is responsible for the actual storing of auth tokens and
            // their e-mail addresses. We will inject this table and hash key to it.
            $table = $app['config']['auth.verify_email.table'];

            $key = $app['config']['app.key'];

            $expire = $app['config']->get('auth.verify_email.expire', 60);

            return new DbRepository($connection, $table, $key, $expire);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['auth.verify_email', 'auth.verify_email.tokens'];
    }
}
