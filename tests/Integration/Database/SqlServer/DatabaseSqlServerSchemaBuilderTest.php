<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSqlServerSchemaBuilderTest extends SqlServerTestCase
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
        Schema::dropIfExists('computed');
        DB::statement('drop view if exists users_view');
    }

    public function testGetTables()
    {
        DB::statement('create view users_view AS select name, age from users');

        $rows = Schema::getTables();

        $this->assertGreaterThanOrEqual(2, count($rows));
        $this->assertTrue(
            collect($rows)->contains('name', 'migrations'),
            'Failed asserting that table "migrations" was returned.'
        );
        $this->assertTrue(
            collect($rows)->contains('name', 'users'),
            'Failed asserting that table "users" was returned.'
        );
        $this->assertFalse(
            collect($rows)->contains('name', 'users_view'),
            'Failed asserting that view "users_view" was not returned.'
        );
    }

    public function testColumnListing()
    {
        $this->assertSame(['id', 'name', 'age', 'color'], Schema::getColumnListing('users'));
    }

    public function testGetViews()
    {
        DB::statement('create view users_view AS select name, age from users');

        $rows = Schema::getViews();

        $this->assertCount(1, $rows);
        $this->assertSame('users_view', $rows[0]['name']);
    }

    public function testGetViewsWhenNoneExist()
    {
        $this->assertSame([], Schema::getViews());
    }

    public function testComputedColumnsListing()
    {
        DB::statement('create table dbo.computed (id int identity (1,1) not null, computed as id + 1)');

        $userColumns = Schema::getColumns('users');
        $this->assertNull($userColumns[1]['generation']);
    }

    public function testCreateIndexesOnline()
    {
        Schema::create('table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('title', 200);

            $table->unique('title')->online();
            $table->index(['created_at'])->online();
        });

        $indexes = Schema::getIndexes('table');
        $indexNames = collect($indexes)->pluck('name');

        $this->assertContains('table_title_unique', $indexNames);
        $this->assertContains('table_created_at_index', $indexNames);
    }
}
