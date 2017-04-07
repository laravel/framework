<?php

namespace Illuminate\Foundation\Console\Presets;

use Illuminate\Filesystem\Filesystem;

class None
{
    /**
     * Install the preset.
     *
     * @return void
     */
    public static function install()
    {
        tap(new Filesystem, function ($filesystem) {
            $filesystem->deleteDirectory(base_path('node_modules'));
            $filesystem->deleteDirectory(resource_path('assets'));
            $filesystem->deleteDirectory(public_path('css'));
            $filesystem->deleteDirectory(public_path('js'));

            $filesystem->delete(base_path('webpack.mix.js'));
            $filesystem->delete(base_path('package.json'));
            $filesystem->delete(base_path('yarn.lock'));
        });
    }
}
