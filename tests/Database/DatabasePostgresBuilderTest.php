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
        $grammar->shouldReceive('compileTableExists')->andReturn('sql');
        $connection->shouldReceive('scalar')->with('sql')->andReturn(1);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('public.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn('sql');
        $connection->shouldReceive('scalar')->with('sql')->andReturn(1);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFallbackFilled()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(['myapp', 'public']);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn('sql');
        $connection->shouldReceive('scalar')->with('sql')->andReturn(1);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathIsUserVariable()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('$user');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn('sql');
        $connection->shouldReceive('scalar')->with('sql')->andReturn(1);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('foouser.foo'));
    }

    public function testHasTableWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn('sql');
        $connection->shouldReceive('scalar')->with('sql')->andReturn(1);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
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
        $grammar->shouldReceive('compileColumns')->with('public', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('myapp', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
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
        $grammar->shouldReceive('compileColumns')->with('foouser', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function testGetColumnListingWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('myapp', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('myapp.foo');
    }

    public function testGetColumnWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('mydatabase.myapp.foo');
    }

    public function testDropAllTablesWhenSearchPathIsString()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(PostgresGrammar::class);
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'users', 'schema' => 'public']]);
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'users', 'schema' => 'public']]);
        $grammar->shouldReceive('compileDropAllTables')->with(['public.users'])->andReturn('drop table "public"."users" cascade');
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
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('compileDropAllTables')->with(['foouser.users'])->andReturn('drop table "foouser"."users" cascade');
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
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('compileDropAllTables')->with(['foouser.users'])->andReturn('drop table "foouser"."users" cascade');
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
