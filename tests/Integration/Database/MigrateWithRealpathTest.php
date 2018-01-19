<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Schema;

class MigrateWithRealpathTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $options = [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];

        $this->artisan('migrate', $options);

        $this->beforeApplicationDestroyed(function () use ($options) {
            $this->artisan('migrate:rollback', $options);
        });
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
}
