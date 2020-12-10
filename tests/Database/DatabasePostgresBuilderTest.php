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
