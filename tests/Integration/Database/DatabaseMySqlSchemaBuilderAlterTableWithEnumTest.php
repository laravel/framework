<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseMySqlSchemaBuilderAlterTableWithEnumTest extends DatabaseMySqlTestCase
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

        $this->assertEquals('integer', Schema::getColumnType('users', 'age'));
    }

    public function testRenameEnumColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->renameColumn('color', 'pill');
        $statements = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("ALTER TABLE users CHANGE color pill ENUM('red','blue') NOT NULL", $statements[0]);
    }

    public function testChangeEnumColumnToString()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('color')->change();
        });

        $this->assertEquals('string', Schema::getColumnType('users', 'color'));
    }

    public function testChangeStringColumnToEnum()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('color', ['red', 'blue', 'green'])->change();
        $statements = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

        $this->assertCount(1, $statements);
        $this->assertStringStartsWith("ALTER TABLE users CHANGE color color ENUM('red','blue','green') NOT NULL", $statements[0]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
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
