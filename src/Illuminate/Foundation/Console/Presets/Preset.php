<?php

namespace Illuminate\Foundation\Console\Presets;

use Laravel;
use Illuminate\Filesystem\Filesystem;

class Preset
{
    /**
     * Ensure the component directories we need exist.
     *
     * @return void
     */
    protected static function ensureComponentDirectoryExists()
    {
        $filesystem = new Filesystem;

        if (! $filesystem->isDirectory($directory = Laravel::resourcePath('js/components'))) {
            $filesystem->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Update the "package.json" file.
     *
     * @param  bool  $dev
     * @return void
     */
    protected static function updatePackages($dev = true)
    {
        if (! file_exists(Laravel::basePath('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(Laravel::basePath('package.json')), true);

        $packages[$configurationKey] = static::updatePackageArray(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            Laravel::basePath('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * Remove the installed Node modules.
     *
     * @return void
     */
    protected static function removeNodeModules()
    {
        tap(new Filesystem, function ($files) {
            $files->deleteDirectory(Laravel::basePath('node_modules'));

            $files->delete(Laravel::basePath('yarn.lock'));
        });
    }
}
