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
        if (file_exists(base_path('package.json'))) {
            $packages = json_decode(file_get_contents(base_path('package.json')), true);

            unset($packages['devDependencies']['vue']);

            $packages['devDependencies']['babel-preset-react'] = '^6.23.0';
            $packages['devDependencies']['react'] = '^15.4.2';
            $packages['devDependencies']['react-dom'] = '^15.4.2';

            ksort($packages['devDependencies']);

            file_put_contents(base_path('package.json'), json_encode($packages, JSON_PRETTY_PRINT));
        }

        copy(__DIR__.'/react-stubs/app.js', resource_path('assets/js/app.js'));
        copy(__DIR__.'/react-stubs/bootstrap.js', resource_path('assets/js/bootstrap.js'));

        $filesystem = new Filesystem;
        $filesystem->delete(resource_path('assets/js/components/Example.vue'));

        copy(__DIR__.'/react-stubs/Example.js', resource_path('assets/js/components/Example.js'));

        copy(__DIR__.'/react-stubs/webpack.mix.js', base_path('webpack.mix.js'));

        $filesystem->deleteDirectory(base_path('node_modules'));
        $filesystem->delete(base_path('yarn.lock'));
    }
}
