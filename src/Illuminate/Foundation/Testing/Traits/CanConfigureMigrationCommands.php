<?php

namespace Illuminate\Foundation\Testing\Traits;

use Illuminate\Foundation\Testing\Attributes\Seed;
use Illuminate\Foundation\Testing\Attributes\Seeder;
use ReflectionClass;

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

        return array_merge(
            [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ],
            $seeder ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()]
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
        $class = new ReflectionClass($this);

        do {
            if (count($class->getAttributes(Seed::class)) > 0) {
                return true;
            }
        } while ($class = $class->getParentClass());

        return property_exists($this, 'seed') ? $this->seed : false;
    }

    /**
     * Determine the specific seeder class that should be used when refreshing the database.
     *
     * @return mixed
     */
    protected function seeder()
    {
        $class = new ReflectionClass($this);

        do {
            $seeder = $class->getAttributes(Seeder::class);

            if (count($seeder) > 0) {
                return $seeder[0]->newInstance()->class;
            }
        } while ($class = $class->getParentClass());

        return property_exists($this, 'seeder') ? $this->seeder : false;
    }
}
