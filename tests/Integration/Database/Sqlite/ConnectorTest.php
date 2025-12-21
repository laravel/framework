<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('sqlite')]
class ConnectorTest extends DatabaseTestCase
{
    private string $databasePath;

    protected function defineDatabaseMigrations()
    {
        Schema::createDatabase($this->databasePath = database_path('secondary.sqlite'));
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropDatabaseIfExists($this->databasePath);
    }

    public function testConnectionConfigurations()
    {
        $schema = DB::build([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ])->getSchemaBuilder();

        $this->assertSame(0, $schema->pragma('foreign_keys'));
        $this->assertSame(60000, $schema->pragma('busy_timeout'));
        $this->assertSame('memory', $schema->pragma('journal_mode'));
        $this->assertSame(2, $schema->pragma('synchronous'));

        $schema = DB::build([
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'foreign_key_constraints' => true,
            'busy_timeout' => 12345,
            'journal_mode' => 'wal',
            'synchronous' => 'normal',
            'pragmas' => [
                'query_only' => true,
            ],
        ])->getSchemaBuilder();

        $this->assertSame(1, $schema->pragma('foreign_keys'));
        $this->assertSame(12345, $schema->pragma('busy_timeout'));
        $this->assertSame('wal', $schema->pragma('journal_mode'));
        $this->assertSame(1, $schema->pragma('synchronous'));
        $this->assertSame(1, $schema->pragma('query_only'));

        $schema->pragma('foreign_keys', 0);
        $schema->pragma('busy_timeout', 54321);
        $schema->pragma('journal_mode', 'delete');
        $schema->pragma('synchronous', 0);

        $this->assertSame(0, $schema->pragma('foreign_keys'));
        $this->assertSame(54321, $schema->pragma('busy_timeout'));
        $this->assertSame('delete', $schema->pragma('journal_mode'));
        $this->assertSame(0, $schema->pragma('synchronous'));
    }
}
