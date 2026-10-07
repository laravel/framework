<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
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
        $connection = Double::for(SQLiteConnection::class);
        $connection->expects('getConfig')->returns($config);
        $connection->expects('getDatabaseName')->returns($config['database']);

        $process = Double::for(Process::class);
        $command = null;
        $processFactory = function ($givenCommand) use ($process, &$command) {
            $command = $givenCommand;

            return $process;
        };

        $schemaState = new SqliteSchemaState($connection, null, $processFactory);
        $schemaState->load('database/schema/sqlite-schema.dump');

        $this->assertSame('sqlite3 "${:LARAVEL_LOAD_DATABASE}" < "${:LARAVEL_LOAD_PATH}"', $command);

        $process->received('mustRun')->with(null, [
            'LARAVEL_LOAD_DATABASE' => 'database/database.sqlite',
            'LARAVEL_LOAD_PATH' => 'database/schema/sqlite-schema.dump',
        ]);
    }

    public function testLoadSchemaToInMemory(): void
    {
        $config = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true, 'name' => 'sqlite'];
        $connection = Double::for(SQLiteConnection::class);
        $connection->expects('getDatabaseName')->returns($config['database']);
        $pdo = Double::for(PDO::class);
        $connection->expects('getPdo')->returns($pdo);

        $files = Double::for(Filesystem::class);
        $files->expects('get')->returns('CREATE TABLE IF NOT EXISTS "migrations" ("id" integer not null primary key autoincrement, "migration" varchar not null, "batch" integer not null);');

        $schemaState = new SqliteSchemaState($connection, $files);
        $schemaState->load('database/schema/sqlite-schema.dump');

        $pdo->received('exec')->with('CREATE TABLE IF NOT EXISTS "migrations" ("id" integer not null primary key autoincrement, "migration" varchar not null, "batch" integer not null);');
    }

    public function testDumpRemovesShadowTables(): void
    {
        $connection = new SQLiteConnection(new PDO('sqlite::memory:'), config: ['database' => ':memory:']);
        $connection->statement('create virtual table posts using fts5(body)');
        $connection->statement('create table "logs_data" ("id" integer primary key)');
        $connection->statement('create virtual table temp.logs using fts5(message)');

        $process = Double::for(Process::class);
        $process->allows('setTimeout')->returns($process);
        $process->allows('mustRun')->returns($process);
        $process->allows('getOutput')->returns(<<<'SQL'
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

        $files = Double::for(Filesystem::class);

        (new SqliteSchemaState($connection, $files, fn () => $process))
            ->withMigrationTable(null)
            ->dump($connection, 'schema.sql');

        $files->received('put')->with('schema.sql', <<<'SQL'
            CREATE VIRTUAL TABLE posts using fts5(body)
            /* posts(body) */;
            CREATE TABLE IF NOT EXISTS "logs_data"("id" integer primary key);

            SQL.PHP_EOL);
    }
}
