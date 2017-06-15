<?php

namespace Illuminate\Foundation\Console\Presets;

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
            $filesystem->deleteDirectory(resource_path('assets/js/components'));
            $filesystem->delete(resource_path('assets/sass/_variables.scss'));
            $filesystem->deleteDirectory(base_path('node_modules'));
            $filesystem->deleteDirectory(public_path('css'));
            $filesystem->deleteDirectory(public_path('js'));
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
            $packages['bootstrap-sass'],
            $packages['jquery'],
            $packages['vue'],
            $packages['babel-preset-react'],
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
        file_put_contents(resource_path('assets/sass/app.scss'), ''.PHP_EOL);
        copy(__DIR__.'/none-stubs/app.js', resource_path('assets/js/app.js'));
    }
}
