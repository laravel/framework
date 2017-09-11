<?php

namespace Illuminate\Foundation\Testing;

use File;
use RuntimeException;

trait TemporaryStorage
{
    /**
     * Create and return the name of a temporary directory.
     *
     * @param  string  $prefix  The prefix of the directoy name
     * @throws \RuntimeExpcetion
     * @return string
     */
    protected function createTemporaryDirectory(string $prefix)
    {
        $attemps    = 5;
        $path       = sprintf(
            "%s%s%s.%d",
            sys_get_temp_dir(),
            DIRECTORY_SEPARATOR,
            $prefix,
            random_int(10000000, 99999999)
        );

        do {
            if (@mkdir($path)) {
                return $path;
            }
        } while (0 < --$attemps);

        throw new RuntimeException(
            sprintf('Failed to create temporary directory [%s]', $path)
        );
    }

    /**
     * Set the storage of the application to a temporary directory.
     *
     * @throws \RuntimeExpcetion
     * @return void
     */
    public function setTemporaryStorage()
    {
        $prefix = (
            'laravel-' .
            app('env') . '-'
            . class_basename(get_class($this)) . '-'
            . $this->getName()
        );

        $storagePath = $this->createTemporaryDirectory($prefix);

        $this->app->useStoragePath($storagePath);
        mkdir(storage_path('/app/public'), 0775, true);
        mkdir(storage_path('/framework/cache'), 0775, true);
        mkdir(storage_path('/framework/sessions'), 0775, true);
        mkdir(storage_path('/framework/views'), 0775, true);
        mkdir(storage_path('/logs'), 0775, true);

        $this->beforeApplicationDestroyed([$this, 'removeTemporaryStorage']);
    }

    /**
     * Remove temporary storage.
     *
     * @return void
     */
    public function removeTemporaryStorage()
    {
        if (env('TESTING_KEEP_TEMP_STORAGE', false) === true) {
            printf('Keeping temporary storage[%s]%s', storage_path(), PHP_EOL);
        } elseif (0 === strpos(storage_path(), sys_get_temp_dir() . '/')) {
            File::deleteDirectory(storage_path());
        } else {
            printf(
                'Ignoring to remove invalid storage dir[%s]%s',
                storage_path(),
                PHP_EOL
            );
        }
    }
}
