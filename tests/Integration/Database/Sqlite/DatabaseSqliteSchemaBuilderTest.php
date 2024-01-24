<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class DatabaseSqliteSchemaBuilderTest extends DatabaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        if (getenv('DB_CONNECTION') !== 'testing') {
            $this->markTestSkipped('Test requires a Sqlite connection.');
        }

        $app['config']->set('database.default', 'conn1');

        $app['config']->set('database.connections.conn1', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function afterRefreshingDatabase()
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

    public function testGetTablesAndColumnListing()
    {
        $tables = Schema::getTables();

        $this->assertCount(2, $tables);
        $this->assertEquals(['migrations', 'users'], array_column($tables, 'name'));

        $columns = Schema::getColumnListing('users');

        foreach (['id', 'name', 'age', 'color'] as $column) {
            $this->assertContains($column, $columns);
        }

        Schema::create('posts', function (Blueprint $table) {
            $table->integer('id');
            $table->string('title');
        });
        $tables = Schema::getTables();
        $this->assertCount(3, $tables);
        Schema::drop('posts');
    }

    public function testGetViews()
    {
        DB::connection('conn1')->statement(<<<'SQL'
CREATE VIEW users_view
AS
SELECT name,age from users;
SQL);

        $tableView = Schema::getViews();

        $this->assertCount(1, $tableView);
        $this->assertEquals('users_view', $tableView[0]['name']);

        DB::connection('conn1')->statement(<<<'SQL'
DROP VIEW IF EXISTS users_view;
SQL);

        $this->assertEmpty(Schema::getViews());
    }
}
