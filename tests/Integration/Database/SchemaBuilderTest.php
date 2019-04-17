<?php

namespace Illuminate\Tests\Integration\Database\SchemaTest;

use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Types\TinyInteger;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class SchemaBuilderTest extends DatabaseTestCase
{
    public function test_drop_all_tables()
    {
        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });

        $this->assertTrue(true);
    }

    public function test_drop_all_views()
    {
        DB::statement('create view "view"("id") as select 1');

        Schema::dropAllViews();

        DB::statement('create view "view"("id") as select 1');

        $this->assertTrue(true);
    }

    public function test_register_custom_doctrine_type()
    {
        Schema::registerCustomDoctrineType(TinyInteger::class, TinyInteger::NAME, 'TINYINT');

        Schema::create('test', function (Blueprint $table) {
            $table->string('test_column');
        });

        $blueprint = new Blueprint('test', function (Blueprint $table) {
            $table->tinyInteger('test_column')->change();
        });

        $expected = [
            'CREATE TEMPORARY TABLE __temp__test AS SELECT test_column FROM test',
            'DROP TABLE test',
            'CREATE TABLE test (test_column TINYINT NOT NULL COLLATE BINARY)',
            'INSERT INTO test (test_column) SELECT test_column FROM __temp__test',
            'DROP TABLE __temp__test',
        ];

        $statements = $blueprint->toSql($this->getConnection(), new SQLiteGrammar());

        $blueprint->build($this->getConnection(), new SQLiteGrammar());

        $this->assertArrayHasKey(TinyInteger::NAME, Type::getTypesMap());
        $this->assertEquals('tinyinteger', Schema::getColumnType('test', 'test_column'));
        $this->assertEquals($expected, $statements);
    }
}
