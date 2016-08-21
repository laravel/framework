<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseMigrations
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        // dropping all tables before migrating
        foreach(\DB::select('SHOW TABLES') as $table) {
            $table_array = get_object_vars($table);
            \Schema::drop($table_array[key($table_array)]);
        }
        
        $this->artisan('migrate');

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }
}
