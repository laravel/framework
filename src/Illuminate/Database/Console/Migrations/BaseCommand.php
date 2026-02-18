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
            return (new Collection($this->option('path')))->map(function ($path) {
                return ! $this->usingRealPath()
                    ? $this->laravel->basePath().'/'.$path
                    : $path;
            })->all();
        }

        // If the active connection has a 'migrations' path configured, use it instead
        // of the default migrations directory. This allows per-connection migration
        // directories to be defined in config/database.php without passing --path.
        if ($connectionPath = $this->connectionMigrationPath()) {
            return [$this->laravel->basePath().'/'.$connectionPath];
        }

        return array_merge(
            $this->migrator->paths(), [$this->getMigrationPath()]
        );
    }

    /**
     * Get the migration path configured for the active database connection, if any.
     *
     * @return string|null
     */
    protected function connectionMigrationPath()
    {
        if (! $this->laravel->bound('config')) {
            return null;
        }

        $connection = $this->migrator->getConnection()
            ?? $this->laravel['config']->get('database.default');

        if (! $connection) {
            return null;
        }

        $path = $this->laravel['config']->get("database.connections.{$connection}.migrations");

        return is_string($path) ? $path : null;
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
