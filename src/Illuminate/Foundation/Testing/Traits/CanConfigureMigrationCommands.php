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
        $database = $this->database();
        $path = $this->path();

        return array_merge(
            [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ],
            $seeder ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()],
            $database ? ['--database' => $database] : [],
            $path ? ['--path' => $path] : [],
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
     * Determine the specific database connection that should be used when refreshing the database
     *
     * @return mixed
     */
    protected function database()
    {
        return property_exists($this, 'database') ? $this->database : false;
    }

    /**
     * Determine the specific migration path that should be used when refreshing the database
     *
     * @return mixed
     */
    protected function path()
    {
        return property_exists($this, 'path') ? $this->path : false;
    }
}
