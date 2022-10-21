<?php

namespace Illuminate\Foundation\Testing\Traits;

use Illuminate\Support\Collection;

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
        $dump = $this->dump();

        return collect([
            '--drop-views' => $this->shouldDropViews(),
            '--drop-types' => $this->shouldDropTypes(),
        ])->when($seeder,
            fn (Collection $collection) => $collection->put('--seeder', $seeder),
            fn (Collection $collection) => $collection->put('--seed', $this->shouldSeed())
        )->when($dump,
            fn (Collection $collection) => $collection->put('--schema-path', $dump)
        )->toArray();
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
     * Determine the specific schema dump path that should be used when refreshing the database.
     * Otherwise laravel will use the default dump as per your connection name.
     *
     * @return mixed
     */
    protected function dump()
    {
        return property_exists($this, 'dump') ? $this->dump : false;
    }
}
