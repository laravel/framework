<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @requires extension pdo_pgsql
 * @requires OS Linux|Darwin
 */
class PostgresSchemaBuilderTest extends PostgresTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.connections.pgsql.search_path', 'public,private');
    }

    protected function defineDatabaseMigrations()
    {
        parent::defineDatabaseMigrations();

        DB::statement('create schema if not exists private');
    }

    protected function destroyDatabaseMigrations()
    {
        DB::statement('drop table if exists public.table');
        DB::statement('drop table if exists private.table');

        DB::statement('drop view if exists public.foo');
        DB::statement('drop view if exists private.foo');

        DB::statement('drop schema private');

        parent::destroyDatabaseMigrations();
    }

    public function testDropAllTablesOnAllSchemas()
    {
        Schema::create('public.table', function (Blueprint $table) {
            $table->increments('id');
        });
        Schema::create('private.table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        $this->artisan('migrate:install');

        $this->assertFalse(Schema::hasTable('public.table'));
        $this->assertFalse(Schema::hasTable('private.table'));
    }

    public function testDropAllTablesUsesDontDropConfigOnAllSchemas()
    {
        $this->app['config']->set('database.connections.pgsql.dont_drop', ['spatial_ref_sys', 'table']);
        DB::purge('pgsql');

        Schema::create('public.table', function (Blueprint $table) {
            $table->increments('id');
        });
        Schema::create('private.table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        $this->artisan('migrate:install');

        $this->assertTrue(Schema::hasTable('public.table'));
        $this->assertTrue(Schema::hasTable('private.table'));
    }

    public function testDropAllTablesUsesDontDropConfigOnOneSchema()
    {
        $this->app['config']->set('database.connections.pgsql.dont_drop', ['spatial_ref_sys', 'private.table']);
        DB::purge('pgsql');

        Schema::create('public.table', function (Blueprint $table) {
            $table->increments('id');
        });
        Schema::create('private.table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        $this->artisan('migrate:install');

        $this->assertFalse(Schema::hasTable('public.table'));
        $this->assertTrue(Schema::hasTable('private.table'));
    }

    public function testDropAllViewsOnAllSchemas()
    {
        DB::statement('create view public.foo (id) as select 1');
        DB::statement('create view private.foo (id) as select 1');

        Schema::dropAllViews();

        $this->assertFalse($this->hasView('public', 'foo'));
        $this->assertFalse($this->hasView('private', 'foo'));
    }

    public function testAddTableCommentOnNewTable()
    {
        Schema::create('public.posts', function (Blueprint $table) {
            $table->comment('This is a comment');
        });

        $this->assertEquals('This is a comment', DB::selectOne("select obj_description('public.posts'::regclass, 'pg_class')")->obj_description);
    }

    public function testAddTableCommentOnExistingTable()
    {
        Schema::create('public.posts', function (Blueprint $table) {
            $table->id();
            $table->comment('This is a comment');
        });

        Schema::table('public.posts', function (Blueprint $table) {
            $table->comment('This is a new comment');
        });

        $this->assertEquals('This is a new comment', DB::selectOne("select obj_description('public.posts'::regclass, 'pg_class')")->obj_description);
    }

    protected function hasView($schema, $table)
    {
        return DB::table('information_schema.views')
            ->where('table_catalog', $this->app['config']->get('database.connections.pgsql.database'))
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->exists();
    }
}
