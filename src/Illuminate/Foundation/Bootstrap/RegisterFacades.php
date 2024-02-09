<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Facade;

class RegisterFacades
{
    /**
     * The facades that should be merged before registration.
     *
     * @var array
     */
    protected static $merge = [];

    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if (! $app->bound('config_loaded_from_cache') ||
            $app->make('config_loaded_from_cache') === false) {
            $this->mergeAdditionalFacades($app);
        }

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($app);

        AliasLoader::getInstance(array_merge(
            $app->make('config')->get('app.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }

    /**
     * Merge the additional configured facades into the configuration.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function mergeAdditionalFacades(Application $app)
    {
        $app->make('config')->set(
            'app.aliases',
            array_merge(
                $app->make('config')->get('app.aliases'),
                static::$merge,
            ),
        );
    }

    /**
     * Merge the given facades into the facade configuration before registration.
     *
     * @param  array $facades
     * @return void
     */
    public static function merge(array $facades)
    {
        static::$merge = array_filter(array_unique(
            $facades
        ));
    }
}
