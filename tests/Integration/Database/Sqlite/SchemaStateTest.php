<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Orchestra\Testbench\Factories\UserFactory;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[WithMigration]
class SchemaStateTest extends DatabaseTestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'database/schema',
    ];

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testSchemaDumpOnSqlite()
    {
        if ($this->driver !== 'sqlite') {
            $this->markTestSkipped('Test requires a SQLite connection.');
        }

        UserFactory::new()->create();

        $connection = DB::connection();
        $connection->statement('PRAGMA optimize;');

        $this->app['files']->ensureDirectoryExists(database_path('schema'));

        $connection->getSchemaState()->dump($connection, database_path('schema/sqlite-schema.sql'));

        $this->assertFileDoesNotContains([
            'sqlite_sequence',
            'sqlite_stat1',
            'sqlite_stat4',
        ], 'database/schema/sqlite-schema.sql');
    }
}
