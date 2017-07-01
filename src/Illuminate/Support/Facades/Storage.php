<?php

namespace Illuminate\Support\Facades;

use Illuminate\Filesystem\Filesystem;

/**
 * @see \Illuminate\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Replace the given disk with a local, testing disk.
     *
     * @param  string  $disk
     * @param  bool $doesntWantsOldFiles
     * @return void
     */
    public static function fake($disk, $doesntWantsOldFiles = true)
    {
        $rootPath = storage_path('framework/testing/disks/' . $disk);

        if ($doesntWantsOldFiles) {
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
