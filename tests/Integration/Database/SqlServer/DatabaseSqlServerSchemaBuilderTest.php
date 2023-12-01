<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

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
        DB::statement('drop view if exists users_view');
    }

    public function testGetAllTables()
    {
        DB::statement('create view users_view AS select name, age from users');

        $rows = Schema::getAllTables();

        $this->assertContainsOnlyInstancesOf(stdClass::class, $rows);
        $this->assertGreaterThanOrEqual(2, count($rows));
        $this->assertTrue(
            collect($rows)->contains(fn ($row) => $row->name === 'migrations' && $row->type === 'U '),
            'Failed asserting that table "migrations" was returned.'
        );
        $this->assertTrue(
            collect($rows)->contains(fn ($row) => $row->name === 'users' && $row->type === 'U '),
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

    public function testGetAllViews()
    {
        DB::statement('create view users_view AS select name, age from users');

        $rows = Schema::getAllViews();

        $this->assertContainsOnlyInstancesOf(stdClass::class, $rows);
        $this->assertCount(1, $rows);
        $this->assertSame('users_view', $rows[0]->name);
        $this->assertSame('V ', $rows[0]->type);
    }

    public function testGetAllViewsWhenNoneExist()
    {
        $this->assertSame([], Schema::getAllViews());
    }
}
