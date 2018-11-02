<?php

namespace Illuminate\Translation;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class TranslationServiceProvider extends ServiceProvider
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
        $this->registerLoader();

        $this->app->singleton('translator', function (Application $app) {
            $loader = $app->make('translation.loader');

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app->make('config')->get('app.locale');

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app->make('config')->get('app.fallback_locale'));

            return $trans;
        });
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function (Application $app) {
            return new FileLoader($app->make('files'), $app->make('path.lang'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['translator', 'translation.loader'];
    }
}
