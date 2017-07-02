<?php

namespace Illuminate\Support\Facades;

use Illuminate\Filesystem\Filesystem;

/**
 * @see \Illuminate\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Tells the fake method whether to clean the working directory.
     *
     * @var boolean
     */
    protected static $doesntWantOldFiles = true;

    /**
     * Keeps the testing files.
     *
     * @return self
     */
    public static function withOldFiles()
    {
        static::$doesntWantOldFiles = false;

        return new static;
    }

    /**
     * Replace the given disk with a local, testing disk.
     *
     * @param  string  $disk
     *
     * @return void
     */
    public static function fake($disk)
    {
        $rootPath = storage_path('framework/testing/disks/' . $disk);

        if (static::$doesntWantOldFiles) {
            (new Filesystem)->cleanDirectory($rootPath);
        }

        static::set($disk, self::createLocalDriver(['root' => $rootPath]));
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
