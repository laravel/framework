<?php

namespace Illuminate\Foundation\Console\Presets;

class Lodash extends Preset
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        static::updatePackages();
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
        return ['lodash' => '^4.17.4'] + $packages;
    }
}
