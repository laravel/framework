<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_mysql')]
class DatabaseMySqlSchemaBuilderAlterTableWithEnumTest extends MySqlTestCase
{
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
            $table->unsignedInteger('age')->change();
        });

        $this->assertSame('int', Schema::getColumnType('users', 'age'));
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
}
