<?php

namespace Illuminate\Foundation\Console\Presets;

use Illuminate\Filesystem\Filesystem;

class React
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
        static::updateComponent();
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

        unset($packages['devDependencies']['vue']);

        $packages['devDependencies'] += [
            'babel-preset-react' => '^6.23.0',
            'react' => '^15.4.2',
            'react-dom' => '^15.4.2',
        ];

        ksort($packages['devDependencies']);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Update the Webpack configuration.
     *
     * @return void
     */
    protected static function updateWebpackConfiguration()
    {
        copy(__DIR__.'/react-stubs/webpack.mix.js', base_path('webpack.mix.js'));
    }

    /**
     * Update the example component.
     *
     * @return void
     */
    protected static function updateComponent()
    {
        (new Filesystem)->delete(
            resource_path('assets/js/components/Example.vue')
        );

        copy(
            __DIR__.'/react-stubs/Example.js',
            resource_path('assets/js/components/Example.js')
        );
    }

    /**
     * Update the bootstrapping files.
     *
     * @return void
     */
    protected static function updateBootstrapping()
    {
        copy(__DIR__.'/react-stubs/app.js', resource_path('assets/js/app.js'));

        copy(__DIR__.'/react-stubs/bootstrap.js', resource_path('assets/js/bootstrap.js'));
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
