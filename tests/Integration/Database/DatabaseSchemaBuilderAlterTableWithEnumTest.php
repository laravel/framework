<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseSchemaBuilderAlterTableWithEnumTest extends DatabaseMySqlTestCase
{
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
        $this->assertSame('stdClass', get_class($tables[0]));

        $tableProperties = array_values((array) $tables[0]);
        $this->assertEquals(['users', 'BASE TABLE'], $tableProperties);
        $this->assertEquals(['id', 'name', 'age', 'color'], Schema::getColumnListing('users'));

        Schema::create('posts', function (Blueprint $table) {
            $table->integer('id');
            $table->string('title');
        });
        $tables = Schema::getAllTables();
        $this->assertCount(2, $tables);
        Schema::drop('posts');
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
}
