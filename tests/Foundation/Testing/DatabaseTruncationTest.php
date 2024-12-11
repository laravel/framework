<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseTruncationTest extends TestCase
{
    use DatabaseTruncation;

    private ?array $app;

    private ?array $tablesToTruncate = null;

    private ?array $exceptTables = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config'] = new Repository([
            'database' => [
                'migrations' => [
                    'table' => 'migrations',
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->app = null;
        static::$allTables = [];
        $this->tablesToTruncate = null;
        $this->exceptTables = null;
    }

    public function testTruncateTables()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => null, 'name' => 'foo'],
            ['schema' => null, 'name' => 'bar'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['foo', 'bar'], $truncatedTables);
    }

    public function testTruncateTablesWithTablesToTruncateProperty()
    {
        $this->tablesToTruncate = ['foo', 'bar', 'qux'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => null, 'name' => 'migrations'],
            ['schema' => null, 'name' => 'foo'],
            ['schema' => null, 'name' => 'bar'],
            ['schema' => null, 'name' => 'baz'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['foo', 'bar'], $truncatedTables);
    }

    public function testTruncateTablesWithExceptTablesProperty()
    {
        $this->exceptTables = ['baz', 'qux'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => null, 'name' => 'migrations'],
            ['schema' => null, 'name' => 'foo'],
            ['schema' => null, 'name' => 'bar'],
            ['schema' => null, 'name' => 'baz'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['foo', 'bar'], $truncatedTables);
    }

    public function testTruncateTablesWithSchema()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations'],
            ['schema' => 'public', 'name' => 'foo'],
            ['schema' => 'public', 'name' => 'bar'],
            ['schema' => 'private', 'name' => 'migrations'],
            ['schema' => 'private', 'name' => 'foo'],
            ['schema' => 'private', 'name' => 'baz'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.foo', 'public.bar', 'private.foo', 'private.baz'], $truncatedTables);
    }

    public function testTruncateTablesWithSchemaTablesToTruncateProperty()
    {
        $this->tablesToTruncate = ['foo', 'public.bar'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations'],
            ['schema' => 'public', 'name' => 'foo'],
            ['schema' => 'public', 'name' => 'bar'],
            ['schema' => 'public', 'name' => 'baz'],
            ['schema' => 'private', 'name' => 'migrations'],
            ['schema' => 'private', 'name' => 'foo'],
            ['schema' => 'private', 'name' => 'bar'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.foo', 'public.bar', 'private.foo'], $truncatedTables);
    }

    public function testTruncateTablesWithSchemaAndExceptTablesProperty()
    {
        $this->exceptTables = ['foo', 'public.bar'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations'],
            ['schema' => 'public', 'name' => 'foo'],
            ['schema' => 'public', 'name' => 'bar'],
            ['schema' => 'public', 'name' => 'baz'],
            ['schema' => 'private', 'name' => 'migrations'],
            ['schema' => 'private', 'name' => 'foo'],
            ['schema' => 'private', 'name' => 'bar'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.baz', 'private.bar'], $truncatedTables);
    }

    public function testTruncateTablesWithConnectionPrefix()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'my_migrations'],
            ['schema' => 'public', 'name' => 'my_foo'],
            ['schema' => 'public', 'name' => 'my_baz'],
            ['schema' => 'private', 'name' => 'my_migrations'],
            ['schema' => 'private', 'name' => 'my_foo'],
        ], 'my_');

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.my_foo', 'public.my_baz', 'private.my_foo'], $truncatedTables);
    }

    public function testTruncateTablesOnPgsqlWithSearchPath()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations'],
            ['schema' => 'public', 'name' => 'foo'],
            ['schema' => 'public', 'name' => 'bar'],
            ['schema' => 'my_schema', 'name' => 'foo'],
            ['schema' => 'my_schema', 'name' => 'baz'],
            ['schema' => 'private', 'name' => 'migrations'],
            ['schema' => 'private', 'name' => 'foo'],
            ['schema' => 'private', 'name' => 'baz'],
        ], '', PostgresBuilder::class, ['my_schema', 'public']);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.foo', 'public.bar', 'my_schema.foo', 'my_schema.baz'], $truncatedTables);
    }

    private function arrangeConnection(
        ?array &$actual, array $allTables, string $prefix = '', ?string $builder = null, ?array $schemas = []
    ): Connection {
        $actual = [];

        $schema = m::mock($builder ?? Builder::class);
        $schema->shouldReceive('getTables')->once()->andReturn($allTables);

        if ($builder === PostgresBuilder::class && $schemas) {
            $schema->shouldReceive('getSchemas')->once()->andReturn($schemas);
        }

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn($prefix);
        $connection->shouldReceive('getEventDispatcher')->once()->andReturn($dispatcher = m::mock(Dispatcher::class));
        $connection->shouldReceive('unsetEventDispatcher')->once();
        $connection->shouldReceive('setEventDispatcher')->once()->with($dispatcher);
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);
        $connection->shouldReceive('withoutTablePrefix')->andReturnUsing(function ($callback) use ($connection) {
            $callback($connection);
        });
        $connection->shouldReceive('table')
            ->andReturnUsing(function (string $tableName) use (&$actual) {
                $actual[] = $tableName;

                $table = m::mock();
                $table->shouldReceive('exists')->andReturnTrue();
                $table->shouldReceive('truncate');

                return $table;
            });

        return $connection;
    }
}
