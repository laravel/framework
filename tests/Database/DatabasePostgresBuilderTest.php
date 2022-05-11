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

    /**
     * Ensure that when the reference is unqualified (i.e., does not contain a
     * database name or a schema), and the search_path is empty, the database
     * specified on the connection is used, and the default schema ('public')
     * is used.
     */
    public function testWhenSearchPathEmptyHasTableWithUnqualifiedReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('select')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'public', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    /**
     * Ensure that when the reference is unqualified (i.e., does not contain a
     * database name or a schema), and the first schema in the search_path is
     * NOT the default ('public'), the database specified on the connection is
     * used, and the first schema in the search_path is used.
     */
    public function testWhenSearchPathNotEmptyHasTableWithUnqualifiedSchemaReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('select')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    /**
     * Ensure that when the reference is unqualified (i.e., does not contain a
     * database name or a schema), and the first schema in the search_path is
     * the special variable '$user', the database specified on the connection is
     * used, the first schema in the search_path is used, and the variable
     * resolves to the username specified on the connection.
     */
    public function testWhenFirstSchemaInSearchPathIsVariableHasTableWithUnqualifiedSchemaReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('$user');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('select')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'foouser', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    /**
     * Ensure that when the reference is qualified only with a schema, that
     * the database specified on the connection is used, and the specified
     * schema is used, even if it is not within the search_path.
     */
    public function testWhenSchemaNotInSearchPathHasTableWithQualifiedSchemaReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('select')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('myapp.foo');
    }

    /**
     * Ensure that when the reference is qualified with a database AND a schema,
     * and the database is NOT the database configured for the connection, the
     * specified database is used instead.
     */
    public function testWhenDatabaseNotDefaultHasTableWithFullyQualifiedReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('select')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['mydatabase', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('mydatabase.myapp.foo');
    }

    /**
     * Ensure that when the reference is unqualified (i.e., does not contain a
     * database name or a schema), and the search_path is empty, the database
     * specified on the connection is used, and the default schema ('public')
     * is used.
     */
    public function testWhenSearchPathEmptyGetColumnListingWithUnqualifiedReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('select')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'public', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    /**
     * Ensure that when the reference is unqualified (i.e., does not contain a
     * database name or a schema), and the first schema in the search_path is
     * NOT the default ('public'), the database specified on the connection is
     * used, and the first schema in the search_path is used.
     */
    public function testWhenSearchPathNotEmptyGetColumnListingWithUnqualifiedSchemaReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('select')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    /**
     * Ensure that when the reference is unqualified (i.e., does not contain a
     * database name or a schema), and the first schema in the search_path is
     * the special variable '$user', the database specified on the connection is
     * used, the first schema in the search_path is used, and the variable
     * resolves to the username specified on the connection.
     */
    public function testWhenFirstSchemaInSearchPathIsVariableGetColumnListingWithUnqualifiedSchemaReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('$user');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('select')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'foouser', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    /**
     * Ensure that when the reference is qualified only with a schema, that
     * the database specified on the connection is used, and the specified
     * schema is used, even if it is not within the search_path.
     */
    public function testWhenSchemaNotInSearchPathGetColumnListingWithQualifiedSchemaReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('select')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['laravel', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('myapp.foo');
    }

    /**
     * Ensure that when the reference is qualified with a database AND a schema,
     * and the database is NOT the database configured for the connection, the
     * specified database is used instead.
     */
    public function testWhenDatabaseNotDefaultGetColumnListingWithFullyQualifiedReferenceIsCorrect()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumnListing')->andReturn('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?');
        $connection->shouldReceive('select')->with('select column_name from information_schema.columns where table_catalog = ? and table_schema = ? and table_name = ?', ['mydatabase', 'myapp', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(PostgresProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumnListing')->andReturn(['some_column']);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('mydatabase.myapp.foo');
    }

    /**
     * Ensure that when the search_path contains just one schema, only that
     * schema is passed into the query that is executed to acquire the list
     * of tables to be dropped.
     */
    public function testDropAllTablesWithOneSchemaInSearchPath()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileGetAllTables')->with(['public'])->andReturn("select tablename from pg_catalog.pg_tables where schemaname in ('public')");
        $connection->shouldReceive('select')->with("select tablename from pg_catalog.pg_tables where schemaname in ('public')")->andReturn(['users']);
        $grammar->shouldReceive('compileDropAllTables')->with(['users'])->andReturn('drop table "'.implode('","', ['users']).'" cascade');
        $connection->shouldReceive('statement')->with('drop table "'.implode('","', ['users']).'" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    /**
     * Ensure that when the search_path contains more than one schema, both
     * schemas are passed into the query that is executed to acquire the list
     * of tables to be dropped. Furthermore, ensure that the special '$user'
     * variable is resolved to the username specified on the database connection
     * in the process.
     */
    public function testDropAllTablesWithMoreThanOneSchemaInSearchPath()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('"$user", public');
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileGetAllTables')->with(['foouser', 'public'])->andReturn("select tablename from pg_catalog.pg_tables where schemaname in ('foouser','public')");
        $connection->shouldReceive('select')->with("select tablename from pg_catalog.pg_tables where schemaname in ('foouser','public')")->andReturn(['users', 'users']);
        $grammar->shouldReceive('compileDropAllTables')->with(['users', 'users'])->andReturn('drop table "'.implode('","', ['users', 'users']).'" cascade');
        $connection->shouldReceive('statement')->with('drop table "'.implode('","', ['users', 'users']).'" cascade');
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
