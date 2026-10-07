<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use JMac\Testing\Double;
use PHPUnit\Framework\TestCase;

class DatabasePostgresBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $connection = Double::for(Connection::class);
        $grammar = new PostgresGrammar($connection);

        $connection->expects('getConfig')->with('charset')->returns('utf8');
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with('create database "my_temporary_database" encoding "utf8"')->returns(true);

        $builder = $this->getBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = Double::for(Connection::class);
        $grammar = new PostgresGrammar($connection);

        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('statement')->with('drop database if exists "my_database_a"')->returns(true);

        $builder = $this->getBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathMissing()
    {
        $connection = $this->getConnection();
        $grammar = Double::for(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $grammar->expects('compileTableExists')->times(2)->returns('sql');
        $connection->expects('scalar')->times(2)->with('sql')->returns(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('public.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $grammar = Double::for(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $grammar->expects('compileTableExists')->times(2)->returns('sql');
        $connection->expects('scalar')->times(2)->with('sql')->returns(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathFallbackFilled()
    {
        $connection = $this->getConnection();
        $grammar = Double::for(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $grammar->expects('compileTableExists')->times(2)->returns('sql');
        $connection->expects('scalar')->times(2)->with('sql')->returns(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenSchemaUnqualifiedAndSearchPathIsUserVariable()
    {
        $connection = $this->getConnection();
        $grammar = Double::for(PostgresGrammar::class);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $grammar->expects('compileTableExists')->times(2)->returns('sql');
        $connection->expects('scalar')->times(2)->with('sql')->returns(1);
        $connection->expects('getTablePrefix')->times(2);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('foouser.foo'));
    }

    public function testHasTableWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('scalar')->with($grammar->compileTableExists('myapp', 'foo'))->returns(1);
        $connection->expects('getTablePrefix');
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function testHasTableWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $builder = $this->getBuilder($connection);

        $builder->hasTable('mydatabase.myapp.foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathMissing()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->allows('getServerVersion')->returns('12.0.0');
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'foo'))->returns([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Double::for(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->returns($processor);
        $processor->expects('processColumns')->returns([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('foo'));
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathFilled()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->allows('getServerVersion')->returns('12.0.0');
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'foo'))->returns([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Double::for(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->returns($processor);
        $processor->expects('processColumns')->returns([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('foo'));
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndSearchPathIsUserVariable()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->allows('getServerVersion')->returns('12.0.0');
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns(null, 'foo'))->returns([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Double::for(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->returns($processor);
        $processor->expects('processColumns')->returns([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('foo'));
    }

    public function testGetColumnListingWhenSchemaQualifiedAndSearchPathMismatches()
    {
        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->allows('getServerVersion')->returns('12.0.0');
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileColumns('myapp', 'foo'))->returns([['name' => 'some_column']]);
        $connection->expects('getTablePrefix');
        $processor = Double::for(PostgresProcessor::class);
        $connection->expects('getPostProcessor')->returns($processor);
        $processor->expects('processColumns')->returns([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $this->assertSame(['some_column'], $builder->getColumnListing('myapp.foo'));
    }

    public function testGetColumnWhenDatabaseAndSchemaQualifiedAndSearchPathMismatches()
    {
        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $grammar = new PostgresGrammar($connection);
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('mydatabase.myapp.foo');
    }

    public function testDropAllTablesWhenSearchPathIsString()
    {
        $connection = $this->getConnection();
        $connection->expects('getConfig')->with('search_path')->returns('public');
        $connection->expects('getConfig')->with('dont_drop')->returns(['foo']);
        $grammar = new PostgresGrammar($connection);
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('getPostProcessor')->returns($processor);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileTables(['public']))->returns([(object) ['name' => 'users', 'schema' => 'public']]);
        $connection->expects('statement')->with('drop table "public"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function testDropAllTablesWhenSearchPathIsStringOfMany()
    {
        $connection = $this->getConnection();
        $connection->expects('getConfig')->with('username')->returns('foouser');
        $connection->expects('getConfig')->with('search_path')->returns('"$user", public, foo_bar-Baz.Áüõß');
        $connection->expects('getConfig')->with('dont_drop')->returns(['foo']);
        $grammar = new PostgresGrammar($connection);
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('getPostProcessor')->returns($processor);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileTables(['foouser', 'public', 'foo_bar-Baz.Áüõß']))->returns([(object) ['name' => 'users', 'schema' => 'foouser']]);
        $connection->expects('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function testDropAllTablesWhenSearchPathIsArrayOfMany()
    {
        $connection = $this->getConnection();
        $connection->expects('getConfig')->with('username')->returns('foouser');
        $connection->expects('getConfig')->with('search_path')->returns([
            '$user',
            '"dev"',
            "'test'",
            'spaced schema',
        ]);
        $connection->expects('getConfig')->with('dont_drop')->returns(['foo']);
        $grammar = new PostgresGrammar($connection);
        $processor = new PostgresProcessor;
        $connection->expects('getSchemaGrammar')->returns($grammar);
        $connection->expects('getPostProcessor')->returns($processor);
        $connection->expects('selectFromWriteConnection')->with($grammar->compileTables(['foouser', 'dev', 'test', 'spaced schema']))->returns([(object) ['name' => 'users', 'schema' => 'foouser']]);
        $connection->expects('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    protected function getConnection()
    {
        return Double::for(Connection::class);
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
