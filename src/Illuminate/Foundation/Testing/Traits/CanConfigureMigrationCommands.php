<?php

namespace Illuminate\Foundation\Testing\Traits;

trait CanConfigureMigrationCommands
{
    /**
     * The parameters that should be used when running "migrate:fresh".
     *
     * @return array
     */
    protected function migrateFreshUsing()
    {
        $seeder = $this->seeder();
        $schemaPath = $this->useSchemaPath();

        return array_merge(
            [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ],
            $seeder ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()],
            $schemaPath ? ['--schema-path' => $schemaPath] : [],
        );
    }

    /**
     * Determine if views should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropViews()
    {
        return property_exists($this, 'dropViews') ? $this->dropViews : false;
    }

    /**
     * Determine if types should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropTypes()
    {
        return property_exists($this, 'dropTypes') ? $this->dropTypes : false;
    }

    /**
     * Determine if the seed task should be run when refreshing the database.
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return property_exists($this, 'seed') ? $this->seed : false;
    }

    /**
     * Determine the specific seeder class that should be used when refreshing the database.
     *
     * @return mixed
     */
    protected function seeder()
    {
        return property_exists($this, 'seeder') ? $this->seeder : false;
    }

    /**
     * Determine the schema path, checking if the path is absolute, relative to
     * the database directory, or within the database schema/ directory.
     *
     * @return bool
     */
    protected function useSchemaPath(): bool
    {
        $schemaPath = $this->schemaPath ?? false;

        if (! $schemaPath) {
            return false;
        }

        if (file_exists($schemaPath)) {
            return $schemaPath;
        }

        // check if the path is simply the filename of the schema
        if (file_exists($schemaPathWithinDatabaseMigrationsPath = database_path('schema/'.$schemaPath))) {
            return $schemaPathWithinDatabaseMigrationsPath;
        }

        // check if the path provided is relative to the root database path
        if (file_exists($schemaPathWithinDatabasePath = database_path($schemaPath))) {
            return $schemaPathWithinDatabasePath;
        }

        return false;
    }
}
