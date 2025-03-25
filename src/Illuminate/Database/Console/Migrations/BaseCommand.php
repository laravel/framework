<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BaseCommand extends Command
{
    /**
     * Get all of the migration paths.
     *
     * @return string[]
     */
    protected function getMigrationPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        if ($this->input->hasOption('path') && $this->option('path')) {

            $paths = (new Collection($this->option('path')))->map(function ($path) {
                return ! $this->usingRealPath()
                    ? $this->laravel->basePath().'/'.$path
                    : $path;
            })->all();
        }

        // default migration paths
        else {
            $paths = array_merge(
                $this->migrator->paths(), [$this->getMigrationPath()]
            );
        }


        // Should we recursively search for migration files in the given path(s) ?
        // Then get all sub directories of the given path(s)
        if ($this->input->hasOption('recursive') && $this->option('recursive')) {

            $fs = $this->migrator->getFilesystem();

            $paths = (new Collection($paths))->map(function( $path ) use( $fs ) {
                return [ $path, $fs->directories( $path, true ) ];

            })->flatten()->unique()->all();
        }

        return $paths;
    }

    /**
     * Determine if the given path(s) are pre-resolved "real" paths.
     *
     * @return bool
     */
    protected function usingRealPath()
    {
        return $this->input->hasOption('realpath') && $this->option('realpath');
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        return $this->laravel->databasePath().DIRECTORY_SEPARATOR.'migrations';
    }
}
