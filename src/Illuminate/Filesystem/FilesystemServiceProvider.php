<?php

namespace Illuminate\Filesystem;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\PathTraversalDetected;

class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerNativeFilesystem();
        $this->registerFlysystem();
        $this->registerFileServing();
    }

    /**
     * Register the native filesystem implementation.
     *
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        $this->app->singleton('files', function () {
            return new Filesystem;
        });
    }

    /**
     * Register the driver based filesystem.
     *
     * @return void
     */
    protected function registerFlysystem()
    {
        $this->registerManager();

        $this->app->singleton('filesystem.disk', function ($app) {
            return $app['filesystem']->disk($this->getDefaultDriver());
        });

        $this->app->singleton('filesystem.cloud', function ($app) {
            return $app['filesystem']->disk($this->getCloudDriver());
        });
    }

    /**
     * Register the filesystem manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('filesystem', function ($app) {
            return new FilesystemManager($app);
        });
    }

    /**
     * Register protected file serving.
     *
     * @return void
     */
    protected function registerFileServing()
    {
        foreach ($this->app['config']['filesystems.disks'] ?? [] as $disk => $config) {
            if ($config['driver'] !== 'local' || ! ($config['serve'] ?? false)) {
                continue;
            }

            $this->app->booted(function () use ($disk, $config) {
                $path = isset($config['url'])
                    ? rtrim(parse_url($config['url'])['path'], '/')
                    : '/storage';

                Route::get($path.'/{file}', function (Request $request, $file) use ($disk, $config) {
                    if (($config['visibility'] ?? 'private') !== 'public' &&
                        ! $request->hasValidRelativeSignature()) {
                        abort(403);
                    }

                    try {
                        return Storage::disk($disk)->exists($file)
                            ? Storage::disk($disk)->download($file)
                            : abort(404);
                    } catch (PathTraversalDetected $e) {
                        abort(404);
                    }
                })->where('file', '.*')->name('storage.'.$disk);
            });
        }
    }

    /**
     * Get the default file driver.
     *
     * @return string
     */
    protected function getDefaultDriver()
    {
        return $this->app['config']['filesystems.default'];
    }

    /**
     * Get the default cloud based file driver.
     *
     * @return string
     */
    protected function getCloudDriver()
    {
        return $this->app['config']['filesystems.cloud'];
    }
}
