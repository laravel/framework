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

    public function testRealpathMigrationHasProperlyExecuted()
    {
        $this->assertTrue(Schema::hasTable('members'));
    }

    public function testMigrationsHasTheMigratedTable()
    {
        $this->assertDatabaseHas('migrations', [
            'id' => 1,
            'migration' => '2014_10_12_000000_create_members_table',
            'batch' => 1,
        ]);
    }

    public function testMigrationEventsAreFired()
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
