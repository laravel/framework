<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Contracts\Validation\StrictMimeTypeGuesser;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Magika\MagikaCliGuesser;

class ValidationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPresenceVerifier();
        $this->registerUncompromisedVerifier();
        $this->registerStrictMimeTypeGuesser();
        $this->registerValidationFactory();
    }

    /**
     * Register the validation factory.
     *
     * @return void
     */
    protected function registerValidationFactory()
    {
        $this->app->singleton('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence of
            // values in a given data collection which is typically a relational database or
            // other persistent data stores. It is used to check for "uniqueness" as well.
            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }

    /**
     * Register the database presence verifier.
     *
     * @return void
     */
    protected function registerPresenceVerifier()
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db']);
        });
    }

    /**
     * Register the uncompromised password verifier.
     *
     * @return void
     */
    protected function registerUncompromisedVerifier()
    {
        $this->app->singleton(UncompromisedVerifier::class, function ($app) {
            return new NotPwnedVerifier($app[HttpFactory::class]);
        });
    }

    /**
     * Register the default strict MIME type guesser.
     *
     * @return void
     */
    protected function registerStrictMimeTypeGuesser(): void
    {
        $this->app->bind(StrictMimeTypeGuesser::class, MagikaCliGuesser::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['validator', 'validation.presence', UncompromisedVerifier::class, StrictMimeTypeGuesser::class];
    }
}
