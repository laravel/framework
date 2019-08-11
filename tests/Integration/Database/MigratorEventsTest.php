<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Event;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Events\MigrationsStarted;

class MigratorEventsTest extends DatabaseTestCase
{
    protected function migrateOptions()
    {
        return [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];
    }

    public function test_migration_events_are_fired()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions());
        $this->artisan('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, 2);
        Event::assertDispatched(MigrationsEnded::class, 2);
        Event::assertDispatched(MigrationStarted::class, 2);
        Event::assertDispatched(MigrationEnded::class, 2);
    }

    public function test_migration_events_contain_the_migration_and_method()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions());
        $this->artisan('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationStarted::class, function ($event) {
            return $event->method == 'up' && $event->migration instanceof Migration;
        });
        Event::assertDispatched(MigrationStarted::class, function ($event) {
            return $event->method == 'down' && $event->migration instanceof Migration;
        });
        Event::assertDispatched(MigrationEnded::class, function ($event) {
            return $event->method == 'up' && $event->migration instanceof Migration;
        });
        Event::assertDispatched(MigrationEnded::class, function ($event) {
            return $event->method == 'down' && $event->migration instanceof Migration;
        });
    }

    public function test_migrations_events_contain_the_migration_and_method()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions());
        $this->artisan('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method == 'up' && count($event->migrations) === 1 && $event->migrations[0] instanceof Migration;
        });
        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method == 'down' && count($event->migrations) === 1 && $event->migrations[0] instanceof Migration;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method == 'up' && count($event->migrations) === 1 && $event->migrations[0] instanceof Migration;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method == 'down' && count($event->migrations) === 1 && $event->migrations[0] instanceof Migration;
        });
    }

    public function test_migration_events_are_fired_when_no_migrations_are_to_be_ran()
    {
        Event::fake();

        $this->artisan('migrate');
        $this->artisan('migrate:rollback');

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method == 'up' && count($event->migrations) === 0;
        });
        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method == 'down' && count($event->migrations) === 0;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method == 'up' && count($event->migrations) === 0;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method == 'down' && count($event->migrations) === 0;
        });
        Event::assertNotDispatched(MigrationStarted::class);
        Event::assertNotDispatched(MigrationEnded::class);
    }
}
