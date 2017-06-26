<?php

namespace Illuminate\Validation;

use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
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
        $this->registerPresenceVerifier();

        $this->registerFailureFormatter();

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

            $validator->setFailureFormatter($app['validation.failure_formatter']);

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
     * Register failure formatter.
     *
     * @return void
     */
    protected function registerFailureFormatter()
    {
        $this->app->singleton('validation.failure_formatter', function ($app) {
            $config = $app['config'];

            $defaultFormatter = $config->get('validation.default_failure_formatter');

            $formatter = $config->get('validation.failure_formatters'.$defaultFormatter);

            return new $formatter;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'validator', 'validation.presence', 'validation.failure_formatter',
        ];
    }
}
