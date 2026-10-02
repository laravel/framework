<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabasePostgresBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new PostgresGrammar($connection);

        $connection->expects('getConfig')->with('charset')->andReturn('utf8');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with(
            'create database "my_temporary_database" encoding "utf8"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Mockery::mock(Connection::class);
        $grammar = new PostgresGrammar($connection);

        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('statement')->with(
            'drop database if exists "my_database_a"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathMissing()
    {
        $connection = $this->getConnection();
        $grammar = Mockery::mock(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $grammar->expects('compileTableExists')->times(2)->andReturn('sql');
        $connection->expects('scalar')->times(2)->with('sql')->andReturn(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('public.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $grammar = Mockery::mock(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $grammar->expects('compileTableExists')->times(2)->andReturn('sql');
        $connection->expects('scalar')->times(2)->with('sql')->andReturn(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFallbackFilled()
    {
        $connection = $this->getConnection();
        $grammar = Mockery::mock(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $grammar->expects('compileTableExists')->times(2)->andReturn('sql');
        $connection->expects('scalar')->times(2)->with('sql')->andReturn(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathIsUserVariable()
    {
        $connection = $this->getConnection();
        $grammar = Mockery::mock(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $grammar->expects('compileTableExists')->times(2)->andReturn('sql');
        $connection->expects('scalar')->times(2)->with('sql')->andReturn(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('foouser.foo'));
    }

    public function testHasTableWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('scalar')->with($grammar->compileTableExists('myapp', 'foo'))->andReturn(1);
        $connection->expects('getTablePrefix');
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = $this->getBuilder($connection);

        $builder->hasTable('mydatabase.myapp.foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathMissing()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->shouldReceive('getServerVersion')->andReturn('12.0.0');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'foo'))->andReturn([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Mockery::mock(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $processor->expects('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('foo'));
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->shouldReceive('getServerVersion')->andReturn('12.0.0');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'foo'))->andReturn([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Mockery::mock(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $processor->expects('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('foo'));
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathIsUserVariable()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->shouldReceive('getServerVersion')->andReturn('12.0.0');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'foo'))->andReturn([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Mockery::mock(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $processor->expects('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('foo'));
    }

    public function testGetColumnListingWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->shouldReceive('getServerVersion')->andReturn('12.0.0');
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns('myapp', 'foo'))->andReturn([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Mockery::mock(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $processor->expects('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('myapp.foo'));
    }

    public function testGetColumnWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('mydatabase.myapp.foo');
    }

    public function testDropAllTablesWhenSearchPathIsString()
    {
        $connection = $this->getConnection();
        $connection->expects('getConfig')->with('search_path')->andReturn('public');
        $connection->expects('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = new PostgresGrammar($connection);
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileTables(['public']))
            ->andReturn([(object) ['name' => 'users', 'schema' => 'public']]);
        $connection->expects('statement')->with('drop table "public"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function testDropAllTablesWhenSearchPathIsStringOfMany()
    {
        $connection = $this->getConnection();
        $connection->expects('getConfig')->with('username')->andReturn('foouser');
        $connection->expects('getConfig')->with('search_path')->andReturn('"$user", public, foo_bar-Baz.Áüõß');
        $connection->expects('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = new PostgresGrammar($connection);
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $connection->expects('selectFromWriteConnection')
            ->with($grammar->compileTables(['foouser', 'public', 'foo_bar-Baz.Áüõß']))
            ->andReturn([(object) ['name' => 'users', 'schema' => 'foouser']]);
        $connection->expects('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function testDropAllTablesWhenSearchPathIsArrayOfMany()
    {
        $connection = $this->getConnection();
        $connection->expects('getConfig')->with('username')->andReturn('foouser');
        $connection->expects('getConfig')->with('search_path')->andReturn([
            '$user',
            '"dev"',
            "'test'",
            'spaced schema',
        ]);
        $connection->expects('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = new PostgresGrammar($connection);
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->andReturn($grammar);
        $connection->expects('getPostProcessor')->andReturn($processor);
        $connection->expects('selectFromWriteConnection')
            ->with($grammar->compileTables(['foouser', 'dev', 'test', 'spaced schema']))
            ->andReturn([(object) ['name' => 'users', 'schema' => 'foouser']]);
        $connection->expects('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    protected function getConnection()
    {
        return Mockery::mock(Connection::class);
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
