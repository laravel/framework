<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Schema\SqliteSchemaState;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DatabaseSqliteSchemaStateTest extends TestCase
{
    public function testLoadSchemaToDatabase(): void
    {
        $config = ['driver' => 'sqlite', 'database' => 'database/database.sqlite', 'prefix' => '', 'foreign_key_constraints' => true, 'name' => 'sqlite'];
        $connection = Mockery::mock(SQLiteConnection::class);
        $connection->expects('getConfig')->andReturn($config);
        $connection->expects('getDatabaseName')->andReturn($config['database']);

        $process = Mockery::spy(Process::class);
        $command = null;
        $processFactory = function ($givenCommand) use ($process, &$command) {
            $command = $givenCommand;

            return $process;
        };

        $schemaState = new SqliteSchemaState($connection, null, $processFactory);
        $schemaState->load('database/schema/sqlite-schema.dump');

        $this->assertSame('sqlite3 "${:LARAVEL_LOAD_DATABASE}" < "${:LARAVEL_LOAD_PATH}"', $command);

        $process->shouldHaveReceived('mustRun')->with(null, [
            'LARAVEL_LOAD_DATABASE' => 'database/database.sqlite',
            'LARAVEL_LOAD_PATH' => 'database/schema/sqlite-schema.dump',
        ]);
    }

    public function testLoadSchemaToInMemory(): void
    {
        $config = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true, 'name' => 'sqlite'];
        $connection = Mockery::mock(SQLiteConnection::class);
        $connection->expects('getDatabaseName')->andReturn($config['database']);
        $pdo = Mockery::spy(PDO::class);
        $connection->expects('getPdo')->andReturn($pdo);

        $files = Mockery::mock(Filesystem::class);
        $files->expects('get')->andReturn('CREATE TABLE IF NOT EXISTS "migrations" ("id" integer not null primary key autoincrement, "migration" varchar not null, "batch" integer not null);');

        $schemaState = new SqliteSchemaState($connection, $files);
        $schemaState->load('database/schema/sqlite-schema.dump');

        $pdo->shouldHaveReceived('exec')->with('CREATE TABLE IF NOT EXISTS "migrations" ("id" integer not null primary key autoincrement, "migration" varchar not null, "batch" integer not null);');
    }

    public function testDumpRemovesShadowTables(): void
    {
        $connection = new SQLiteConnection(new PDO('sqlite::memory:'), config: ['database' => ':memory:']);
        $connection->statement('create virtual table posts using fts5(body)');
        $connection->statement('create table "logs_data" ("id" integer primary key)');
        $connection->statement('create virtual table temp.logs using fts5(message)');

        $process = Mockery::mock(Process::class);
        $process->allows('setTimeout')->andReturnSelf();
        $process->allows('mustRun')->andReturnSelf();
        $process->allows('getOutput')->andReturn(<<<'SQL'
            CREATE VIRTUAL TABLE posts using fts5(body)
            /* posts(body) */;
            CREATE TABLE IF NOT EXISTS 'posts_data'(id INTEGER PRIMARY KEY, block BLOB);
            CREATE TABLE IF NOT EXISTS 'posts_idx'(
              segid,
              term,
              pgno,
              PRIMARY KEY(segid, term)
            ) WITHOUT ROWID;
            CREATE TABLE IF NOT EXISTS "logs_data"("id" integer primary key);

            SQL);

        $files = Mockery::spy(Filesystem::class);

        (new SqliteSchemaState($connection, $files, fn () => $process))
            ->withMigrationTable(null)
            ->dump($connection, 'schema.sql');

        $files->shouldHaveReceived('put')->with('schema.sql', <<<'SQL'
            CREATE VIRTUAL TABLE posts using fts5(body)
            /* posts(body) */;
            CREATE TABLE IF NOT EXISTS "logs_data"("id" integer primary key);

            SQL.PHP_EOL);
    }
}
