<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Database\Seeder;
use Illuminate\Contracts\Console\Kernel;

trait SeedDatabase
{
    /**
     * Seeds the database.
     *
     * @return void
     */
    public function seedDatabase()
    {
        if (! SeedDatabaseState::$seeded) {
            $this->runSeeders(SeedDatabaseState::$seeders);

            $this->syncTransactionTraits();

            if (SeedDatabaseState::$seedOnce) {
                SeedDatabaseState::$seeded = true;
            }
        }
    }

    /**
     * Calls specific seeders if possible.
     *
     * @param array $seeders
     */
    public function runSeeders(array $seeders)
    {
        if (empty($seeders)) {
            $this->artisan('db:seed');

            $this->app[Kernel::class]->setArtisan(null);

            return;
        }

        $this->getSeederInstance()->call($seeders);
    }

    /**
     * Persists the seed and begins a new transaction
     * where the rollback has been already registered in Transaction traits.
     *
     * @return void
     */
    public function syncTransactionTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[RefreshDatabase::class]) || isset($uses[DatabaseTransactions::class])) {
            $database = $this->app->make('db');

            foreach ($this->connectionsToTransact() as $name) {
                $database->connection($name)->commit();
                $database->connection($name)->beginTransaction();
            }
        }
    }

    /**
     * Builds a quick seeder instance.
     *
     * @return Seeder
     */
    private function getSeederInstance()
    {
        return
        new class() extends Seeder
        {
            public function run()
            {
            }
        };
    }
}
