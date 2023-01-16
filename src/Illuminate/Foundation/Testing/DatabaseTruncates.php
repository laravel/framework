<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait DatabaseTruncates
{
    use CanConfigureMigrationCommands;

    protected static array $allTables;

    protected function truncateTables()
    {
        // Always remove any test data before the application is destroyed.
        $this->beforeApplicationDestroyed(function () {
            Schema::disableForeignKeyConstraints();
            collect(static::$allTables ??= DB::connection()->getDoctrineSchemaManager()->listTableNames())
                ->diff($this->excludeTables())
                ->filter(fn ($table) => DB::table($table)->exists())
                ->each(fn ($table) => DB::table($table)->truncate());
        });

        // Migrate and seed the database on first run.
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());
            RefreshDatabaseState::$migrated = true;

            return;
        }

        // Seed the database on subsequent runs.
        if ($seeder = $this->seeder()) {
            // Use a specific seeder class.
            $this->artisan('db:seed', ['--class' => $seeder]);

            return;
        }

        if ($this->shouldSeed()) {
            // Use the default seeder class.
            $this->artisan('db:seed');
        }
    }

    protected function excludeTables()
    {
        return [
            'migrations',
        ];
    }
}
