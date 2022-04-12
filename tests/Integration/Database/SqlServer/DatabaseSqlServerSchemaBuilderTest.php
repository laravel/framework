<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class DatabaseSqlServerSchemaBuilderTest extends SqlServerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        if (getenv('DB_CONNECTION') !== 'sqlsrv') {
            $this->markTestSkipped('Test requires a SQLServer connection.');
        }

        $this->driver = 'sqlsrv';
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
            $table->string('age');
            $table->enum('color', ['red', 'blue']);
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('users');
    }

    public function testGetAllTablesAndColumnListing()
    {
        $tables = Schema::getAllTables();

        $this->assertCount(2, $tables);
        $tableProperties = array_values((array) $tables[0]);
        $this->assertEquals(['migrations', 'U '], $tableProperties);

        $this->assertInstanceOf(stdClass::class, $tables[1]);

        $tableProperties = array_values((array) $tables[1]);
        $this->assertEquals(['users', 'U '], $tableProperties);

        $columns = Schema::getColumnListing('users');

        foreach (['id', 'name', 'age', 'color'] as $column) {
            $this->assertContains($column, $columns);
        }

        Schema::create('posts', function (Blueprint $table) {
            $table->integer('id');
            $table->string('title');
        });
        $tables = Schema::getAllTables();
        $this->assertCount(3, $tables);
        Schema::drop('posts');
    }

    public function testGetAllViews()
    {
        DB::connection('sqlsrv')->statement(<<<'SQL'
CREATE VIEW users_view
AS
SELECT name,age from users;
SQL);

        $tableView = Schema::getAllViews();

        $this->assertCount(1, $tableView);
        $this->assertInstanceOf(stdClass::class, $obj = array_values($tableView)[0]);
        $this->assertEquals('users_view', $obj->name);
        $this->assertEquals('V ', $obj->type);

        DB::connection('sqlsrv')->statement(<<<'SQL'
DROP VIEW IF EXISTS users_view;
SQL);

        $this->assertEmpty(Schema::getAllViews());
    }
}
