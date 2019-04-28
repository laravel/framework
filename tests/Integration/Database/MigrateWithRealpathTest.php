<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Events\MigrationsStarted;

class MigrateWithRealpathTest extends DatabaseTestCase
{
    protected function setUp(): void
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

    public function test_migration_events_are_fired()
    {
        Event::fake();

        Event::listen(MigrationsStarted::class, function ($event) {
            return $this->assertInstanceOf(MigrationsStarted::class, $event);
        });

        Event::listen(MigrationsEnded::class, function ($event) {
            return $this->assertInstanceOf(MigrationsEnded::class, $event);
        });

        Event::listen(MigrationStarted::class, function ($event) {
            return $this->assertInstanceOf(MigrationStarted::class, $event);
        });

        Event::listen(MigrationEnded::class, function ($event) {
            return $this->assertInstanceOf(MigrationEnded::class, $event);
        });

        $this->artisan('migrate');
    }
}
