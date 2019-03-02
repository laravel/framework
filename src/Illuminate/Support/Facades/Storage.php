<?php

namespace Illuminate\Support\Facades;

use Illuminate\Filesystem\Filesystem;

/**
 * @method static \Illuminate\Contracts\Filesystem\Filesystem disk(string $name = null)
 *
 * @see \Illuminate\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Replace the given disk with a local testing disk.
     *
     * @param  string|null  $disk
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public static function fake($disk = null)
    {
        $disk = $disk ?: self::$app['config']->get('filesystems.default');

        (new Filesystem)->cleanDirectory(
            $root = storage_path('framework/testing/disks/'.$disk)
        );

        static::set($disk, $fake = self::createLocalDriver(['root' => $root]));

        return $fake;
    }

    /**
     * Replace the given disk with a persistent local testing disk.
     *
     * @param  string|null  $disk
     * @return \Illuminate\Filesystem\Filesystem
     */
    public static function persistentFake($disk = null)
    {
        $disk = $disk ?: self::$app['config']->get('filesystems.default');

        static::set($disk, $fake = self::createLocalDriver([
            'root' => storage_path('framework/testing/disks/'.$disk),
        ]));

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
