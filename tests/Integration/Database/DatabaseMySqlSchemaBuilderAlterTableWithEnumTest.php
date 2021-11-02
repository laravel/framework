<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use stdClass;

/**
 * @requires extension pdo_mysql
 * @requires OS Linux|Darwin
 */
class DatabaseMySqlSchemaBuilderAlterTableWithEnumTest extends DatabaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
            $table->string('age');
            $table->enum('color', ['red', 'blue']);
        });
    }

    protected function tearDown(): void
    {
        Schema::drop('users');

        parent::tearDown();
    }

    public function testRenameColumnOnTableWithEnum()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'username');
        });

        $this->assertTrue(Schema::hasColumn('users', 'username'));
    }

    public function testChangeColumnOnTableWithEnum()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('age')->charset('')->change();
        });

        $this->assertSame('integer', Schema::getColumnType('users', 'age'));
    }

    public function testGetAllTablesAndColumnListing()
    {
        $tables = Schema::getAllTables();

        $this->assertCount(1, $tables);
        $this->assertInstanceOf(stdClass::class, $tables[0]);

        $tableProperties = array_values((array) $tables[0]);
        $this->assertEquals(['users', 'BASE TABLE'], $tableProperties);

        $columns = Schema::getColumnListing('users');

        foreach (['id', 'name', 'age', 'color'] as $column) {
            $this->assertContains($column, $columns);
        }

        Schema::create('posts', function (Blueprint $table) {
            $table->integer('id');
            $table->string('title');
        });
        $tables = Schema::getAllTables();
        $this->assertCount(2, $tables);
        Schema::drop('posts');
    }
}
