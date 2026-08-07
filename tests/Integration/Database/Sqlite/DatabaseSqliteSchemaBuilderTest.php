<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;

#[RequiresDatabase('sqlite')]
class DatabaseSqliteSchemaBuilderTest extends DatabaseTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

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
        $this->assertSame('users_view', $tableView[0]['name']);

        DB::connection('conn1')->statement(<<<'SQL'
DROP VIEW IF EXISTS users_view;
SQL);

        $this->assertEmpty(Schema::getViews());
    }

    public function testGetRawIndex()
    {
        Schema::create('table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->rawIndex('(strftime("%Y", created_at))', 'table_raw_index');
        });

        $indexes = Schema::getIndexes('table');

        $this->assertSame([], collect($indexes)->firstWhere('name', 'table_raw_index')['columns']);
    }

    public function testPartialIndexIsEnforcedAndSurvivesATableRebuild()
    {
        Schema::create('teams', fn (Blueprint $table) => $table->id());

        Schema::create('table', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->softDeletes();

            $table->unique('email')->where(fn ($query) => $query->whereNull('deleted_at'));
        });

        $this->assertSame(
            '"deleted_at" is null',
            collect(Schema::getIndexes('table'))->firstWhere('name', 'table_email_unique')['where']
        );

        // Adding a constrained column forces SQLite to rebuild the table, which
        // regenerates its indexes from the introspected schema. The predicate of
        // the partial index has to be carried over to the rebuilt index...
        Schema::table('table', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained();
        });

        $this->assertSame(
            '"deleted_at" is null',
            collect(Schema::getIndexes('table'))->firstWhere('name', 'table_email_unique')['where']
        );

        DB::table('table')->insert(['email' => 'taylor@laravel.com', 'deleted_at' => null]);
        DB::table('table')->insert(['email' => 'taylor@laravel.com', 'deleted_at' => '2026-01-01 00:00:00']);

        $this->assertSame(2, DB::table('table')->count());

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('table')->insert(['email' => 'taylor@laravel.com', 'deleted_at' => null]);
    }
}
