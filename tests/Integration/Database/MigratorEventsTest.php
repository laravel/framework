<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class MigratorEventsTest extends TestCase
{
    protected function migrateOptions()
    {
        return [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];
    }

    public function testMigrationEventsAreFired()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions());
        $this->artisan('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, 2);
        Event::assertDispatched(MigrationsEnded::class, 2);
        Event::assertDispatched(MigrationStarted::class, 2);
        Event::assertDispatched(MigrationEnded::class, 2);
    }

    public function testMigrationEventsContainTheOptionsAndPretendFalse()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions());
        $this->artisan('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'down'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'down'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === false;
        });
    }

    public function testMigrationEventsContainTheOptionsAndPretendTrue()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions() + ['--pretend' => true]);
        $this->artisan('migrate:rollback', $this->migrateOptions()); // doesn't support pretend

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === true;
        });

        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'up'
                && is_array($event->options)
                && isset($event->options['pretend'])
                && $event->options['pretend'] === true;
        });
    }

    public function testMigrationEventsContainTheMigrationAndMethod()
    {
        Event::fake();

        $this->artisan('migrate', $this->migrateOptions());
        $this->artisan('migrate:rollback', $this->migrateOptions());

        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'up';
        });
        Event::assertDispatched(MigrationsStarted::class, function ($event) {
            return $event->method === 'down';
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'up';
        });
        Event::assertDispatched(MigrationsEnded::class, function ($event) {
            return $event->method === 'down';
        });

        Event::assertDispatched(MigrationStarted::class, function ($event) {
            return $event->method === 'up' && $event->migration instanceof Migration;
        });
        Event::assertDispatched(MigrationStarted::class, function ($event) {
            return $event->method === 'down' && $event->migration instanceof Migration;
        });
        Event::assertDispatched(MigrationEnded::class, function ($event) {
            return $event->method === 'up' && $event->migration instanceof Migration;
        });
        Event::assertDispatched(MigrationEnded::class, function ($event) {
            return $event->method === 'down' && $event->migration instanceof Migration;
        });
    }

    public function testTheNoMigrationEventIsFiredWhenNothingToMigrate()
    {
        Event::fake();

        $this->artisan('migrate');
        $this->artisan('migrate:rollback');

        Event::assertDispatched(NoPendingMigrations::class, function ($event) {
            return $event->method === 'up';
        });
        Event::assertDispatched(NoPendingMigrations::class, function ($event) {
            return $event->method === 'down';
        });
    }
}
