<?php

namespace Illuminate\Foundation\Console\Presets;

use Laravel;
use Illuminate\Filesystem\Filesystem;

class None extends Preset
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
        static::updateBootstrapping();

        tap(new Filesystem, function ($filesystem) {
            $filesystem->deleteDirectory(Laravel::resourcePath('js/components'));
            $filesystem->delete(Laravel::resourcePath('sass/_variables.scss'));
            $filesystem->deleteDirectory(Laravel::basePath('node_modules'));
            $filesystem->deleteDirectory(Laravel::publicPath('css'));
            $filesystem->deleteDirectory(Laravel::publicPath('js'));
        });
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        unset(
            $packages['bootstrap'],
            $packages['jquery'],
            $packages['popper.js'],
            $packages['vue'],
            $packages['@babel/preset-react'],
            $packages['react'],
            $packages['react-dom']
        );

        return $packages;
    }

    /**
     * Write the stubs for the Sass and JavaScript files.
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        file_put_contents(Laravel::resourcePath('sass/app.scss'), ''.PHP_EOL);
        copy(__DIR__.'/none-stubs/app.js', Laravel::resourcePath('js/app.js'));
        copy(__DIR__.'/none-stubs/bootstrap.js', Laravel::resourcePath('js/bootstrap.js'));
    }
}
