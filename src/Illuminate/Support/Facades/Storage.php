<?php

namespace Illuminate\Support\Facades;

use Illuminate\Filesystem\Filesystem;

/**
 * @see \Illuminate\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Replace the given disk with a local testing disk.
     *
     * @param  string|null  $disk
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public static function fake($disk = null, array $config = [])
    {
        $disk = $disk ?: static::$app['config']->get('filesystems.default');

        $root = storage_path('framework/testing/disks/'.$disk);

        if ($token = ParallelTesting::token()) {
            $root = "{$root}_test_{$token}";
        }

        (new Filesystem)->cleanDirectory($root);

        static::set($disk, $fake = static::createLocalDriver(array_merge($config, [
            'root' => $root,
        ])));

        return tap($fake)->buildTemporaryUrlsUsing(function ($path, $expiration) {
            return URL::to($path.'?expiration='.$expiration->getTimestamp());
        });
    }

    /**
     * Replace the given disk with a persistent local testing disk.
     *
     * @param  string|null  $disk
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public static function persistentFake($disk = null, array $config = [])
    {
        $disk = $disk ?: static::$app['config']->get('filesystems.default');

        static::set($disk, $fake = static::createLocalDriver(array_merge($config, [
            'root' => storage_path('framework/testing/disks/'.$disk),
        ])));

        return $fake;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filesystem';
    }
}
