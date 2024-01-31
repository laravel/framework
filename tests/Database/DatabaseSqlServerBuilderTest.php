<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\SqlServerProcessor;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Database\Schema\SqlServerBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSqlServerBuilderTest extends TestCase
{
    public function testCreateDatabase()
    {
        $grammar = new SqlServerGrammar;

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database "my_temporary_database"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new SqlServerGrammar;

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists "my_database_a"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    public function testHasTableWhenSchemaUnqualifiedAndDefaultSchemaFallback()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(null);
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'"
        );
        $connection->shouldReceive('selectFromWriteConnection')->with(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'",
            ['laravel', 'dbo', 'foo']
        )->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn(null);
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    public function testHasTableWhenSchemaUnqualifiedAndDefaultSchemaFilled()
    {
        $connection = $this->getConnection();
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'"
        );
        $connection->shouldReceive('selectFromWriteConnection')->with(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'",
            ['laravel', 'my_schema', 'foo']
        )->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_schema');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('foo');
    }

    public function testHasTableWithQualifiedAndEscapedNames()
    {
        $connection = $this->getConnection();
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'"
        );
        $connection->shouldReceive('selectFromWriteConnection')->with(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'",
            ['laravel', 'my_schema', 'foo']
        )->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn(null);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasTable('[my_schema].[foo]'));
    }


    public function testHasTableWhenSchemaQualifiedAndDefaultSchemaIsDifferent()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'"
        );
        $connection->shouldReceive('selectFromWriteConnection')->with(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'",
            ['laravel', 'my_schema', 'foo']
        )->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('my_schema.foo');
    }

    public function testHasTableWhenDatabaseAndSchemaQualifiedAndDefaultSchemaIsDifferent()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'"
        );
        $connection->shouldReceive('selectFromWriteConnection')->with(
            "select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'",
            ['mydatabase', 'my_schema', 'foo']
        )->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);

        $builder->hasTable('mydatabase.my_schema.foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndDefaultSchemaMissing()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn(null);
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('laravel', 'dbo', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function testGetColumnListingWhenSchemaUnqualifiedAndDefaultSchemaFilled()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('laravel', 'my_default_schema', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function testGetColumnListingWhenSchemaQualifiedAndDefaultSchemaIsDifferent()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('laravel', 'my_schema', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('my_schema.foo');
    }

    public function testGetColumnListingWhenSchemaQualifiedAndEscapedAndDefaultSchemaIsDifferent()
    {
        // escaped schema and table name by using brackets []
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('laravel', 'my_schema', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('[my_schema].[foo]');
    }


    public function testGetColumnWhenDatabaseAndSchemaQualifiedAndDefaultSchemaIsDifferent()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('my_database', 'my_schema', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn(['countable_result']);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('my_database.my_schema.foo');
    }

    public function testDropAllTables()
    {
        $connection = $this->getConnection();
        $grammar = m::mock(SqlServerGrammar::class)->makePartial();
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('statement')->with(
            "DECLARE @sql NVARCHAR(MAX) = N'';
            SELECT @sql += 'ALTER TABLE '
                + QUOTENAME(OBJECT_SCHEMA_NAME(parent_object_id)) + '.' + + QUOTENAME(OBJECT_NAME(parent_object_id))
                + ' DROP CONSTRAINT ' + QUOTENAME(name) + ';'
            FROM sys.foreign_keys;

            EXEC sp_executesql @sql;"
        );
        $connection->shouldReceive('statement')->with("EXEC sp_msforeachtable 'DROP TABLE ?'");
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function testHasColumnWithDefaultSchemaMissing()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn(null);
        $grammar = m::mock(SqlServerGrammar::class)->makePartial();
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');

        $expectedSql = <<<SQL
select col.name, type.name as type_name,
col.max_length as length, col.precision as precision, col.scale as places,
col.is_nullable as nullable, def.definition as [default],
col.is_identity as autoincrement, col.collation_name as collation,
cast(prop.value as nvarchar(max)) as comment
from sys.columns as col
join sys.types as type on col.user_type_id = type.user_type_id
join sys.objects as obj on col.object_id = obj.object_id
join sys.schemas as scm on obj.schema_id = scm.schema_id
left join sys.default_constraints def on col.default_object_id = def.object_id and col.object_id = def.parent_object_id
left join sys.extended_properties as prop on obj.object_id = prop.major_id and col.column_id = prop.minor_id and prop.name = 'MS_Description'
where obj.type in ('U', 'V')
and obj.[name] = N'foo'
and scm.[name] = N'dbo'
SQL;

        $connection->shouldReceive('selectFromWriteConnection')
            ->with($expectedSql)
            ->andReturn([['name' => 'bar']]);
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'bar']]);
        $builder = $this->getBuilder($connection);

        $this->assertTrue($builder->hasColumn('foo', 'bar'));
    }

    public function testHasColumnWithDefaultSchemaNoColumnsFound()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class)->makePartial();
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');

        $expectedSql = <<<SQL
select col.name, type.name as type_name,
col.max_length as length, col.precision as precision, col.scale as places,
col.is_nullable as nullable, def.definition as [default],
col.is_identity as autoincrement, col.collation_name as collation,
cast(prop.value as nvarchar(max)) as comment
from sys.columns as col
join sys.types as type on col.user_type_id = type.user_type_id
join sys.objects as obj on col.object_id = obj.object_id
join sys.schemas as scm on obj.schema_id = scm.schema_id
left join sys.default_constraints def on col.default_object_id = def.object_id and col.object_id = def.parent_object_id
left join sys.extended_properties as prop on obj.object_id = prop.major_id and col.column_id = prop.minor_id and prop.name = 'MS_Description'
where obj.type in ('U', 'V')
and obj.[name] = N'foo'
and scm.[name] = N'my_default_schema'
SQL;

        $connection->shouldReceive('selectFromWriteConnection')
            ->with($expectedSql)
            ->andReturn([]);
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([]);
        $builder = $this->getBuilder($connection);

        $this->assertFalse($builder->hasColumn('foo', 'bar'));
    }


    public function testHasColumnWithQualifiedSchemaAndDefaultSchemaNoColumnsFound()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('default_schema')->andReturn('my_default_schema');
        $grammar = m::mock(SqlServerGrammar::class)->makePartial();
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');

        $expectedSql = <<<SQL
select col.name, type.name as type_name,
col.max_length as length, col.precision as precision, col.scale as places,
col.is_nullable as nullable, def.definition as [default],
col.is_identity as autoincrement, col.collation_name as collation,
cast(prop.value as nvarchar(max)) as comment
from sys.columns as col
join sys.types as type on col.user_type_id = type.user_type_id
join sys.objects as obj on col.object_id = obj.object_id
join sys.schemas as scm on obj.schema_id = scm.schema_id
left join sys.default_constraints def on col.default_object_id = def.object_id and col.object_id = def.parent_object_id
left join sys.extended_properties as prop on obj.object_id = prop.major_id and col.column_id = prop.minor_id and prop.name = 'MS_Description'
where obj.type in ('U', 'V')
and obj.[name] = N'foo'
and scm.[name] = N'my_schema'
SQL;

        $connection->shouldReceive('selectFromWriteConnection')
            ->with($expectedSql)
            ->andReturn([]);
        $processor = m::mock(SqlServerProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([]);
        $builder = $this->getBuilder($connection);

        $this->assertFalse($builder->hasColumn('my_schema.foo', 'bar'));
    }

    protected function tearDown(): void
    {
        m::close();
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    protected function getBuilder($connection)
    {
        return new SqlServerBuilder($connection);
    }

    protected function getGrammar()
    {
        return new SqlServerGrammar;
    }
}
