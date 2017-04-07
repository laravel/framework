<?php

namespace Illuminate\Foundation\Console\Presets;

use Illuminate\Support\Arr;
use Illuminate\Filesystem\Filesystem;

class Bootstrap
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
        static::removeComponent();
        static::removeNodeModules();
    }

    /**
     * Update the "package.json" file.
     *
     * @return void
     */
    protected static function updatePackages()
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages['devDependencies'] = static::updatePackageArray(
            $packages['devDependencies']
        );

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        return Arr::except($packages, ['vue']);
    }

    /**
     * Update the example component.
     *
     * @return void
     */
    protected static function removeComponent()
    {
        (new Filesystem)->deleteDirectory(
            resource_path('assets/js/components')
        );
    }

    /**
     * Update the bootstrapping files.
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        copy(__DIR__.'/bootstrap-stubs/app.js', resource_path('assets/js/app.js'));

        copy(__DIR__.'/bootstrap-stubs/bootstrap.js', resource_path('assets/js/bootstrap.js'));
    }

    /**
     * Remove the installed Node modules.
     *
     * @return void
     */
    protected static function removeNodeModules()
    {
        tap(new Filesystem, function ($files) {
            $files->deleteDirectory(base_path('node_modules'));

            $files->delete(base_path('yarn.lock'));
        });
    }
}
