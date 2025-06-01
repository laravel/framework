<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

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
        $this->registerValidationFactory();
    }

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCustomValidationRules();
        $this->registerRouteMacros();
    }

    /**
     * Register custom validation rules.
     *
     * @return void
     */
    protected function registerCustomValidationRules()
    {
        $this->app['validator']->extend('nested', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters)) {
                echo "DEBUG: Empty parameters\n";
                return false;
            }

            // The parameter contains the Base64 encoded JSON schema
            $encodedSchema = $parameters[0];
            echo "DEBUG: Encoded Schema: " . $encodedSchema . "\n";

            // Decode the Base64 encoded schema
            $schemaJson = base64_decode($encodedSchema);
            echo "DEBUG: Decoded Schema JSON: " . $schemaJson . "\n";

            // Parse the JSON to validate it
            $schema = json_decode($schemaJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "DEBUG: Invalid JSON schema: " . json_last_error_msg() . "\n";
                return false;
            }

            echo "DEBUG: Parsed schema: " . json_encode($schema) . "\n";
            echo "DEBUG: Value: " . json_encode($value) . "\n";

            // Instead of calling validateNested with JSON, pass the array directly
            return $validator->validateNestedStructureInternal($attribute, $value, $schema, []);
        });
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
     * Register route macros for validation.
     *
     * @return void
     */
    protected function registerRouteMacros()
    {
        if (! class_exists('Illuminate\Routing\Route')) {
            return;
        }

        // Register alias middleware names for easier use
        if ($this->app->bound('router')) {
            $router = $this->app['router'];

            $router->aliasMiddleware('validate.request', 'Illuminate\Validation\Middleware\ValidateRequestBySchema');
            $router->aliasMiddleware('validate.route.params', 'Illuminate\Validation\Middleware\ValidateRouteParams');
            $router->aliasMiddleware('validate.query', 'Illuminate\Validation\Middleware\ValidateQueryOnly');
        }

        \Illuminate\Routing\Route::macro('validateRequestBy', function ($jsonFilePath, $validateAll = false) {
            /** @var \Illuminate\Routing\Route $this */
            $middlewareClass = 'Illuminate\Validation\Middleware\ValidateRequestBySchema';

            // Only accept file paths (strings) pointing to JSON files
            if (!is_string($jsonFilePath)) {
                throw new \InvalidArgumentException('validateRequestBy() only accepts file paths to JSON files. Use validateQuery() for array rules.');
            }

            // For file paths, use as-is
            $parameters = $validateAll ? "{$jsonFilePath},true" : $jsonFilePath;

            return $this->middleware("{$middlewareClass}:{$parameters}");
        });

        \Illuminate\Routing\Route::macro('validateRouteParams', function ($schema) {
            /** @var \Illuminate\Routing\Route $this */
            $middlewareClass = 'Illuminate\Validation\Middleware\ValidateRouteParams';

            if (is_array($schema)) {
                // For inline rules, encode as base64 JSON and prefix with 'inline:'
                $encoded = 'inline:' . base64_encode(json_encode($schema));
            } else {
                // For file paths, use as-is
                $encoded = $schema;
            }

            return $this->middleware("{$middlewareClass}:{$encoded}");
        });

        \Illuminate\Routing\Route::macro('validateQuery', function ($rulesArray) {
            /** @var \Illuminate\Routing\Route $this */
            $middlewareClass = 'Illuminate\Validation\Middleware\ValidateQueryOnly';

            // Only accept arrays of validation rules
            if (!is_array($rulesArray)) {
                throw new \InvalidArgumentException('validateQuery() only accepts arrays of validation rules. Use validateRequestBy() for JSON file paths.');
            }

            // For inline rules, encode as base64 JSON and prefix with 'inline:'
            $encoded = 'inline:' . base64_encode(json_encode($rulesArray));

            return $this->middleware("{$middlewareClass}:{$encoded}");
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['validator', 'validation.presence', UncompromisedVerifier::class];
    }
}
