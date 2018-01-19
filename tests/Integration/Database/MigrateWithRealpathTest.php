<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

class MigrateWithRealpathTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(realpath(__DIR__.'/stubs/'));
    }

    public function test_realpath_migration_has_properly_executed()
    {
        $this->assertTrue(Schema::hasTable('members'));
    }

    public function test_migrations_has_the_migrated_table()
    {
        $this->assertDatabaseHas('migrations', [
            'id' => 1,
            'migration' => '2014_10_12_000000_create_members_table',
            'batch' => 1,
        ]);
    }

    /**
     * Swap Orchestra\Testbench\TestCase::loadMigrationsFrom() operation until we swap the implementation.
     */
    protected function loadMigrationsFrom($paths): void
    {
        $options = is_array($paths) ? $path : ['--path' => $paths];
        $options['--realpath'] = true;

        $this->artisan('migrate', $options);

        $this->app[ConsoleKernel::class]->setArtisan(null);

        $this->beforeApplicationDestroyed(function () use ($options) {
            $this->artisan('migrate:rollback', $options);
        });
    }
}
