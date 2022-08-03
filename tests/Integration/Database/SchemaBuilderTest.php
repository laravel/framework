<?php

namespace Illuminate\Tests\Integration\Database;

use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\Fixtures\TinyInteger;

class SchemaBuilderTest extends DatabaseTestCase
{
    protected function destroyDatabaseMigrations()
    {
        Schema::dropAllViews();
    }

    public function testDropAllTables()
    {
        $this->expectNotToPerformAssertions();

        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        $this->artisan('migrate:install');

        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    public function testDropAllViews()
    {
        $this->expectNotToPerformAssertions();

        DB::statement('create view foo (id) as select 1');

        Schema::dropAllViews();

        DB::statement('create view foo (id) as select 1');
    }

    public function testRegisterCustomDoctrineType()
    {
        if ($this->driver !== 'sqlite') {
            $this->markTestSkipped('Test requires a SQLite connection.');
        }

        Schema::getConnection()->registerDoctrineType(TinyInteger::class, TinyInteger::NAME, 'TINYINT');

        Schema::create('test', function (Blueprint $table) {
            $table->string('test_column');
        });

        $blueprint = new Blueprint('test', function (Blueprint $table) {
            $table->tinyInteger('test_column')->change();
        });

        $blueprint->build($this->getConnection(), new SQLiteGrammar);

        $this->assertArrayHasKey(TinyInteger::NAME, Type::getTypesMap());
        $this->assertSame('tinyinteger', Schema::getColumnType('test', 'test_column'));
    }

    public function testRegisterCustomDoctrineTypeASecondTime()
    {
        if ($this->driver !== 'sqlite') {
            $this->markTestSkipped('Test requires a SQLite connection.');
        }

        Schema::getConnection()->registerDoctrineType(TinyInteger::class, TinyInteger::NAME, 'TINYINT');

        Schema::create('test', function (Blueprint $table) {
            $table->string('test_column');
        });

        $blueprint = new Blueprint('test', function (Blueprint $table) {
            $table->tinyInteger('test_column')->change();
        });

        $blueprint->build($this->getConnection(), new SQLiteGrammar);

        $this->assertArrayHasKey(TinyInteger::NAME, Type::getTypesMap());
        $this->assertSame('tinyinteger', Schema::getColumnType('test', 'test_column'));
    }

    public function testChangingDateTimeAndTimestampColumns()
    {
        if (! in_array($this->driver, ['pgsql', 'mysql', 'sqlsrv'])) {
            $this->markTestSkipped('Test requires PostgreSQL, MySQL or SQLServer connection.');
        }

        Schema::create('test', function ($table) {
            $table->dateTime('datetime_column');
        });

        if ($this->driver === 'mysql') {
            $blueprint = new Blueprint('test', function ($table) {
                $table->timestamp('datetime_column')->change();
            });

            $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

            $expected = ['ALTER TABLE test CHANGE datetime_column datetime_column TIMESTAMP NOT NULL'];

            $this->assertEquals($expected, $queries);
        }

        if ($this->driver === 'pgsql') {
            $blueprint = new Blueprint('test', function ($table) {
                $table->timestampTz('datetime_column')->change();
            });

            $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

            $expected = [
                'ALTER TABLE test ALTER datetime_column TYPE TIMESTAMP(0) WITH TIME ZONE',
                'ALTER TABLE test ALTER datetime_column SET NOT NULL',
            ];

            $this->assertEquals($expected, $queries);
        }

        if ($this->driver === 'sqlsrv') {
            $blueprint = new Blueprint('test', function ($table) {
                $table->dateTimeTz('datetime_column')->change();
            });

            $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

            $expected = ['ALTER TABLE test ALTER COLUMN datetime_column DATETIMEOFFSET(6) NOT NULL'];

            $this->assertEquals($expected, $queries);
        }
    }
}
