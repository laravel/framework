<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait MigratesDatabase
{
    /**
     * Refresh the database using a fresh migration.
     *
     * @return void
     */
    protected function migrateTestDatabase()
    {
        $this->artisan('migrate:fresh', [
            '--drop-views' => $this->shouldDropViews(),
            '--drop-types' => $this->shouldDropTypes(),
        ]);

        if ($this->shouldSeed()) {
            $this->seedTestDatabase();
        }

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Determine if views should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropViews()
    {
        return $this->dropViews ?? false;
    }

    /**
     * Determine if types should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropTypes()
    {
        return $this->dropTypes ?? false;
    }

    /**
     * Determine if database should be seeded when refreshing the database.
     *
     * @return bool
     */
    protected function shouldSeed()
    {
        return property_exists($this, 'seed')
            ? $this->seed : property_exists($this, 'seeder');
    }

    /**
     * Seed the database for testing.
     *
     * @return void
     */
    protected function seedTestDatabase()
    {
        $this->seed($this->seeder ?? null);
    }
}
