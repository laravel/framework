<?php

namespace Illuminate\Foundation\Console\Presets;

class Bootstrap extends Preset
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
        static::updateSass();
        static::removeNodeModules();
    }

    /**
     * Update the given package array.
     *
     * @param  array  $packages
     * @return array
     */
    protected static function updatePackageArray(array $packages)
    {
        return [
            'bootstrap-sass' => '^3.3.7',
            'jquery' => '^3.1.1',
        ] + $packages;
    }

    /**
     * Update the Sass files for the application.
     *
     * @return void
     */
    protected static function updateSass()
    {
        copy(__DIR__.'/bootstrap-stubs/_variables.scss', resource_path('assets/sass/_variables.scss'));
        copy(__DIR__.'/bootstrap-stubs/app.scss', resource_path('assets/sass/app.scss'));
    }
}
