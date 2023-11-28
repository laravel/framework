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

    public function testChangeToTextColumn()
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        Schema::create('test', function (Blueprint $table) {
            $table->integer('test_column');
        });

        foreach (['tinyText', 'text', 'mediumText', 'longText'] as $type) {
            $blueprint = new Blueprint('test', function ($table) use ($type) {
                $table->$type('test_column')->change();
            });

            $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

            $uppercase = strtoupper($type);

            $expected = ["ALTER TABLE test CHANGE test_column test_column $uppercase NOT NULL"];

            $this->assertEquals($expected, $queries);
        }
    }

    public function testChangeTextColumnToTextColumn()
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        Schema::create('test', static function (Blueprint $table) {
            $table->text('test_column');
        });

        foreach (['tinyText', 'mediumText', 'longText'] as $type) {
            $blueprint = new Blueprint('test', function ($table) use ($type) {
                $table->$type('test_column')->change();
            });

            $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

            $uppercase = strtoupper($type);

            $this->assertContains($queries, [
                ["ALTER TABLE test CHANGE test_column test_column $uppercase NOT NULL"], // MySQL
                ["ALTER TABLE test CHANGE test_column test_column $uppercase NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`"], // MariaDB
            ]);
        }
    }

    public function testGetTables()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->comment('This is a comment');
            $table->increments('id');
        });

        Schema::create('bar', function (Blueprint $table) {
            $table->string('name');
        });

        Schema::create('baz', function (Blueprint $table) {
            $table->integer('votes');
        });

        $tables = Schema::getTables();

        $this->assertEmpty(array_diff(['foo', 'bar', 'baz'], array_column($tables, 'name')));

        if (in_array($this->driver, ['mysql', 'pgsql'])) {
            $this->assertNotEmpty(array_filter($tables, function ($table) {
                return $table['name'] === 'foo' && $table['comment'] === 'This is a comment';
            }));
        }
    }

    public function testGetViews()
    {
        DB::statement('create view foo (id) as select 1');
        DB::statement('create view bar (name) as select 1');
        DB::statement('create view baz (votes) as select 1');

        $views = Schema::getViews();

        $this->assertEmpty(array_diff(['foo', 'bar', 'baz'], array_column($views, 'name')));
    }
}
