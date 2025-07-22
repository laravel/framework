<?php

namespace Illuminate\Tests\Integration\Database\Sqlite\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

use function Illuminate\Filesystem\join_paths;
use function Orchestra\Testbench\default_migration_path;

#[RequiresDatabase('sqlite')]
#[WithConfig('database.default', 'sqlite')]
#[WithConfig('database.connections.sqlite.journal_mode', 'wal')]
class MigrateFreshCommandWithJournalModeWalTest extends TestCase
{
    /** {@inheritDoc} */
    #[\Override]
    protected function setUp(): void
    {
        $files = new Filesystem;

        $this->afterApplicationCreated(function () use ($files) {
            $files->copy(join_paths(__DIR__, 'stubs', 'database-journal-mode-wal.sqlite'), database_path('database.sqlite'));
        });

        $this->beforeApplicationDestroyed(function () use ($files) {
            $files->delete(database_path('database.sqlite'));
        });

        parent::setUp();
    }

    public function testRunningMigrateFreshCommandWithWalJournalMode()
    {
        $this->assertTrue(Schema::hasTable('users'));

        $this->artisan('migrate:fresh', [
            '--realpath' => true,
            '--path' => default_migration_path(),
        ])->run();

        $this->assertTrue(Schema::hasTable('users'));
    }
}
