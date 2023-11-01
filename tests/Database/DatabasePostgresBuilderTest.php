<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabasePostgresBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabase()
    {
        $grammar = new PostgresGrammar;

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database "my_temporary_database" encoding "utf8"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new PostgresGrammar;

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists "my_database_a"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathMissing()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(null);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('selectFromWriteConnection')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'public', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('selectFromWriteConnection')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFallbackFilled()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(['myapp', 'public']);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('selectFromWriteConnection')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathIsUserVariable()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('$user');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('selectFromWriteConnection')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'foouser', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    public function testHasTableWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('selectFromWriteConnection')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('myapp.foo');
    }

    public function testHasTableWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('selectFromWriteConnection')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['mydatabase', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('mydatabase.myapp.foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathMissing()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(null);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('selectFromWriteConnection')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'public', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('selectFromWriteConnection')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathIsUserVariable()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('$user');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('selectFromWriteConnection')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'foouser', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function testGetColumnListingWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('selectFromWriteConnection')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('myapp.foo');
    }

    public function testGetColumnWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('selectFromWriteConnection')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['mydatabase', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('mydatabase.myapp.foo');
    }

    public function testDropAllTablesWhenSearchPathIsString()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileGetAllTables')->with(['public'])->andReturn("select tablename, concat('\"', schemaname, '\".\"', tablename, '\"') as qualifiedname from pg_catalog.pg_tables where schemaname in ('public')");
        $connection->shouldReceive('select')->with("select tablename, concat('\"', schemaname, '\".\"', tablename, '\"') as qualifiedname from pg_catalog.pg_tables where schemaname in ('public')")->andReturn([['tablename' => 'users', 'qualifiedname' => '"public"."users"']]);
        $grammar->shouldReceive('escapeNames')->with(['foo'])->andReturn(['"foo"']);
        $grammar->shouldReceive('escapeNames')->with(['tablename' => 'users', 'qualifiedname' => '"public"."users"'])->andReturn(['tablename' => '"users"', 'qualifiedname' => '"public"."users"']);
        $grammar->shouldReceive('compileDropAllTables')->with(['"public"."users"'])->andReturn('drop table "public"."users" cascade');
        $connection->shouldReceive('statement')->with('drop table "public"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function testDropAllTablesWhenSearchPathIsStringOfMany()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('"$user", public, foo_bar-Baz.Áüõß');
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileGetAllTables')->with(['foouser', 'public', 'foo_bar-Baz.Áüõß'])->andReturn("select tablename, concat('\"', schemaname, '\".\"', tablename, '\"') as qualifiedname from pg_catalog.pg_tables where schemaname in ('foouser','public','foo_bar-Baz.Áüõß')");
        $connection->shouldReceive('select')->with("select tablename, concat('\"', schemaname, '\".\"', tablename, '\"') as qualifiedname from pg_catalog.pg_tables where schemaname in ('foouser','public','foo_bar-Baz.Áüõß')")->andReturn([['tablename' => 'users', 'qualifiedname' => '"foouser"."users"']]);
        $grammar->shouldReceive('escapeNames')->with(['foo'])->andReturn(['"foo"']);
        $grammar->shouldReceive('escapeNames')->with(['tablename' => 'users', 'qualifiedname' => '"foouser"."users"'])->andReturn(['tablename' => '"users"', 'qualifiedname' => '"foouser"."users"']);
        $grammar->shouldReceive('compileDropAllTables')->with(['"foouser"."users"'])->andReturn('drop table "foouser"."users" cascade');
        $connection->shouldReceive('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function testDropAllTablesWhenSearchPathIsArrayOfMany()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn([
            '$user',
            '"dev"',
            "'test'",
            'spaced schema',
        ]);
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileGetAllTables')->with(['foouser', 'dev', 'test', 'spaced schema'])->andReturn("select tablename, concat('\"', schemaname, '\".\"', tablename, '\"') as qualifiedname from pg_catalog.pg_tables where schemaname in ('foouser','dev','test','spaced schema')");
        $connection->shouldReceive('select')->with("select tablename, concat('\"', schemaname, '\".\"', tablename, '\"') as qualifiedname from pg_catalog.pg_tables where schemaname in ('foouser','dev','test','spaced schema')")->andReturn([['tablename' => 'users', 'qualifiedname' => '"foouser"."users"']]);
        $grammar->shouldReceive('escapeNames')->with(['foo'])->andReturn(['"foo"']);
        $grammar->shouldReceive('escapeNames')->with(['tablename' => 'users', 'qualifiedname' => '"foouser"."users"'])->andReturn(['tablename' => '"users"', 'qualifiedname' => '"foouser"."users"']);
        $grammar->shouldReceive('compileDropAllTables')->with(['"foouser"."users"'])->andReturn('drop table "foouser"."users" cascade');
        $connection->shouldReceive('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    protected function getBuilder($connection)
    {
        return new PostgresBuilder($connection);
    }

    protected function getGrammar()
    {
        return new PostgresGrammar;
    }
}
