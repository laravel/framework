<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use LogicException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseSchemaBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabase()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = new Builder($connection);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support creating databases.');

        $builder->createDatabase('foo');
    }

    public function testDropDatabaseIfExists()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = new Builder($connection);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support dropping databases.');

        $builder->dropDatabaseIfExists('foo');
    }

    public function testHasTableCorrectlyCallsGrammar()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = new Builder($connection);
        $grammar->shouldReceive('compileTableExists')->once()->andReturn('sql');
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('selectFromWriteConnection')->once()->with('sql', ['prefix_table'])->andReturn(['prefix_table']);

        $this->assertTrue($builder->hasTable('table'));
    }

    public function testTableHasColumns()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $builder = m::mock(Builder::class.'[getColumnListing]', [$connection]);
        $builder->shouldReceive('getColumnListing')->with('users')->twice()->andReturn(['id', 'firstname']);

        $this->assertTrue($builder->hasColumns('users', ['id', 'firstname']));
        $this->assertFalse($builder->hasColumns('users', ['id', 'address']));
    }

    public function testGetColumnTypeAddsPrefix()
    {
        $connection = m::mock(Connection::class);
        $column = m::mock(stdClass::class);
        $type = m::mock(stdClass::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $builder = new Builder($connection);
        $connection->shouldReceive('getTablePrefix')->once()->andReturn('prefix_');
        $connection->shouldReceive('getDoctrineColumn')->once()->with('prefix_users', 'id')->andReturn($column);
        $column->shouldReceive('getType')->once()->andReturn($type);
        $type->shouldReceive('getName')->once()->andReturn('integer');

        $this->assertSame('integer', $builder->getColumnType('users', 'id'));
    }

    public function testTableHasIndex()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getConfig')
            ->andReturn('');

        $blueprint = m::mock(Blueprint::class.'[createDefaultIndexName]', ['users']);
        $blueprint->shouldReceive('createDefaultIndexName')
            ->with(['users', ['country_id']])->andReturn('users_country_id_foreign');

        $builder = m::mock(Builder::class.'[getIndexListing]', [$connection])
            ->shouldAllowMockingProtectedMethods();

        $builder->shouldReceive('getIndexListing')->with('users')->times(3)
            ->andReturn(['primary', 'users_country_id_foreign']);

        $builder->shouldReceive('createBlueprint')->with('users')
            ->andReturn($blueprint);

        $this->assertTrue($builder->hasIndex('users', 'primary'));
        $this->assertTrue($builder->hasIndex('users', ['country_id'], 'foreign'));

        $this->assertFalse($builder->hasIndex('users', 'not_exists'));
    }

    public function testTableHasIndexes()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(stdClass::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getConfig')
            ->andReturn('');

        $blueprint = m::mock(Blueprint::class.'[createDefaultIndexName]', ['users']);
        $blueprint->shouldReceive('createDefaultIndexName')
            ->with(['users', ['country_id']])->andReturn('users_country_id_foreign');

        $builder = m::mock(Builder::class.'[getIndexListing]', [$connection])
            ->shouldAllowMockingProtectedMethods();

        $builder->shouldReceive('getIndexListing')->with('users')->times(3)
            ->andReturn(['primary', 'users_country_id_foreign']);

        $builder->shouldReceive('createBlueprint')->with('users')
            ->andReturn($blueprint);

        $this->assertTrue($builder->hasIndexes('users', ['primary', 'users_country_id_foreign']));
        $this->assertTrue($builder->hasIndexes('users', [['country_id']], 'foreign'));

        $this->assertFalse($builder->hasIndexes('users', ['not_exists']));
    }
}
