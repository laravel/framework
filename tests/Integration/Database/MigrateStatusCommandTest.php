<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class MigrateStatusCommandTest extends TestCase
{
    protected function migrateOptions()
    {
        return [
            '--path' => realpath(__DIR__ . '/stubs/'),
            '--realpath' => true,
        ];
    }

    public function testMigrateStatusShowsPendingMigrations()
    {
        $this->artisan('migrate:install');

        $this->artisan('migrate:status', $this->migrateOptions())
            ->expectsOutputToContain('Pending')
            ->assertSuccessful();
    }

    public function testMigrateStatusShowsRanMigrations()
    {
        $this->artisan('migrate', $this->migrateOptions());

        $this->artisan('migrate:status', $this->migrateOptions())
            ->expectsOutputToContain('Ran')
            ->assertSuccessful();

        $this->artisan('migrate:rollback', $this->migrateOptions());
    }

    public function testMigrateStatusShowsBatchNumber()
    {
        $this->artisan('migrate', $this->migrateOptions());

        $this->artisan('migrate:status', $this->migrateOptions())
            ->expectsOutputToContain('[1]')
            ->assertSuccessful();

        $this->artisan('migrate:rollback', $this->migrateOptions());
    }

    public function testMigrateStatusShowsSkippedMigrations()
    {
        $this->artisan('migrate:install');

        $this->artisan('migrate:status', $this->migrateOptions())
            ->expectsOutputToContain('Skipped')
            ->assertSuccessful();
    }

    public function testMigrateStatusWithSkippedOptionReturnsExitCodeWhenSkipped()
    {
        $this->artisan('migrate:install');

        $this->artisan('migrate:status', $this->migrateOptions() + ['--skipped' => true])
            ->expectsOutputToContain('Skipped')
            ->assertExitCode(1);
    }

    public function testMigrateStatusWithSkippedOptionWhenNoSkippedMigrations()
    {
        $this->artisan('migrate:install');
        
        // We need a path with NO skipped migrations.
        // We can limit path to just the normal migration.
        $this->artisan('migrate:status', [
            '--path' => realpath(__DIR__ . '/stubs/2014_10_12_000000_create_members_table.php'),
            '--realpath' => true,
            '--skipped' => true,
        ])
        ->expectsOutputToContain('No skipped migrations')
        ->assertSuccessful();
    }

    public function testMigrateStatusWithPendingOptionReturnsExitCodeWhenPending()
    {
        $this->artisan('migrate:install');

        $this->artisan('migrate:status', $this->migrateOptions() + ['--pending' => 1])
            ->assertExitCode(1);
    }

    public function testMigrateStatusWithPendingOptionWhenNoPendingMigrations()
    {
        $this->artisan('migrate', $this->migrateOptions());

        $this->artisan('migrate:status', $this->migrateOptions() + ['--pending' => true])
            ->expectsOutputToContain('No pending migrations')
            ->assertSuccessful();

        $this->artisan('migrate:rollback', $this->migrateOptions());
    }

    public function testMigrateStatusWithNoMigrationFiles()
    {
        $this->artisan('migrate:install');

        $this->artisan('migrate:status')
            ->expectsOutputToContain('No migrations found')
            ->assertSuccessful();
    }

    public function testMigrateStatusReturnsErrorWhenMigrationTableDoesNotExist()
    {
        Schema::dropIfExists('migrations');

        $this->artisan('migrate:status', $this->migrateOptions())
            ->expectsOutputToContain('Migration table not found')
            ->assertExitCode(1);
    }
}
