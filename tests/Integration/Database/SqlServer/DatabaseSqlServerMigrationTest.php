<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseSqlServerMigrationTest extends DatabaseSqlServerTestCase
{
    public function testSimpleCreateTableMigration()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('name');
        });
        $this->assertTrue(Schema::hasColumn('users', 'name'));
        $this->assertFalse(Schema::hasColumn('users', 'not_existing'));
    }


    public function testDropColumnWithDefaultAlsoDropsConstraint()
    {
        // tests: SqlServerGrammar::compileDropDefaultConstraint()
        Schema::create('foo', function ($table) {
            $table->string('test');
            $table->boolean('bar')->default(false);
        });
        Schema::table('foo', function ($table) {
            $table->dropColumn('bar');
        });
        $this->assertTrue(Schema::hasColumn('foo', 'test'));
    }

    public function testAddingColumnToTable()
    {
        Schema::create('to_add', function (Blueprint $table) {
            $table->string('name');
        });
        Schema::table('to_add', function ($table) {
            $table->string('extra_field');
        });
        $this->assertTrue(Schema::hasColumn('to_add', 'extra_field'));
    }
}
