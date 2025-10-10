<?php

namespace Illuminate\Tests\Integration\Console;

use Orchestra\Testbench\TestCase;

class DryRunCommandTest extends TestCase
{
    public function testMakeModelWithDryRunDoesNotCreateFiles(): void
    {
        $this->artisan('make:model', ['name' => 'DryRunTestPost', '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would create Model')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist(app_path('Models/DryRunTestPost.php'));
    }

    public function testMakeModelWithMigrationFlagPropagatesDryRun(): void
    {
        $this->artisan('make:model', ['name' => 'DryRunCategory', '-m' => true, '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would create')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist(app_path('Models/DryRunCategory.php'));
    }

    public function testMakeMigrationWithDryRunDoesNotCreateFiles(): void
    {
        $this->artisan('make:migration', ['name' => 'create_dry_run_test_table', '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would create migration file')
            ->assertExitCode(0);
    }

    public function testDryRunShowsOperationDetails(): void
    {
        $this->artisan('make:model', ['name' => 'DryRunTest', '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would create Model')
            ->expectsOutputToContain('operation(s) would be performed')
            ->assertExitCode(0);
    }
}
