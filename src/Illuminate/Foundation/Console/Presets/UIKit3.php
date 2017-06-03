<?php

namespace Illuminate\Foundation\Console\Presets;

use Illuminate\Support\Arr;
use Illuminate\Filesystem\Filesystem;

class UIKit3 extends Preset
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
        static::updateWebpackConfiguration();
        static::updateBootstrapping();
        //static::removeNodeModules();
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        return ['uikit' => '*', 'jquery' => '^3.1.1' ] + Arr::except($packages, [
            'bootstrap-sass'
        ]);
    }

    /**
     * Update the Webpack configuration.
     *
     * @return void
     */
    protected static function updateWebpackConfiguration()
    {
        copy(__DIR__.'/vue-stubs/webpack.mix.js', base_path('webpack.mix.js'));
    }

    /**
     * Update the bootstrapping files.
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        copy(__DIR__.'/uikit3-stubs/app.scss', resource_path('assets/sass/app.scss'));

        tap(new Filesystem, function ($filesystem) {
            $filesystem->delete(resource_path('assets/sass/_variables.scss'));

            $bootstrapJs = str_replace(
                "require('bootstrap-sass');",
                "window.UIkit = require('uikit');",
                $filesystem->get(resource_path('assets/js/bootstrap.js'))
            );

            $filesystem->put(resource_path('assets/js/bootstrap.js'), $bootstrapJs);
        });
    }
}
