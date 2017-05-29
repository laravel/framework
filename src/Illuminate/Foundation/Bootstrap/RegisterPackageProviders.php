<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageAssetLoader;
use Illuminate\Contracts\Foundation\Application;

class RegisterPackageProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if (! $app->make('config')->get('app.autoload_package_providers', true)) {
            return;
        }

        $assetLoader = new PackageAssetLoader(new Filesystem, base_path('vendor'), $app->getCachedPackagesPath());

        foreach ($assetLoader->get('providers') as $provider) {
            $app->register($provider);
        }
    }
}
