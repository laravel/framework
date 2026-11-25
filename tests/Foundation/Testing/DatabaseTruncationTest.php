<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class DatabaseTruncationTest extends TestCase
{
    use DatabaseTruncation;
    use VerifiesDoubles;

    private ?array $app;

    private ?array $tablesToTruncate = null;

    private ?array $exceptTables = null;

    protected function setUp(): void
    {
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
        $this->app = null;
        static::$allTables = [];
        $this->tablesToTruncate = null;
        $this->exceptTables = null;
    }

    public function testTruncateTables()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => null, 'name' => 'foo', 'schema_qualified_name' => 'foo'],
            ['schema' => null, 'name' => 'bar', 'schema_qualified_name' => 'bar'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['foo', 'bar'], $truncatedTables);
    }

    public function testTruncateTablesWithTablesToTruncateProperty()
    {
        $this->tablesToTruncate = ['foo', 'bar', 'qux'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => null, 'name' => 'migrations', 'schema_qualified_name' => 'migrations'],
            ['schema' => null, 'name' => 'foo', 'schema_qualified_name' => 'foo'],
            ['schema' => null, 'name' => 'bar', 'schema_qualified_name' => 'bar'],
            ['schema' => null, 'name' => 'baz', 'schema_qualified_name' => 'baz'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['foo', 'bar'], $truncatedTables);
    }

    public function testTruncateTablesWithExceptTablesProperty()
    {
        $this->exceptTables = ['baz', 'qux'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => null, 'name' => 'migrations', 'schema_qualified_name' => 'migrations'],
            ['schema' => null, 'name' => 'foo', 'schema_qualified_name' => 'foo'],
            ['schema' => null, 'name' => 'bar', 'schema_qualified_name' => 'bar'],
            ['schema' => null, 'name' => 'baz', 'schema_qualified_name' => 'baz'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['foo', 'bar'], $truncatedTables);
    }

    public function testTruncateTablesWithSchema()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations', 'schema_qualified_name' => 'public.migrations'],
            ['schema' => 'public', 'name' => 'foo', 'schema_qualified_name' => 'public.foo'],
            ['schema' => 'public', 'name' => 'bar', 'schema_qualified_name' => 'public.bar'],
            ['schema' => 'private', 'name' => 'migrations', 'schema_qualified_name' => 'private.migrations'],
            ['schema' => 'private', 'name' => 'foo', 'schema_qualified_name' => 'private.foo'],
            ['schema' => 'private', 'name' => 'baz', 'schema_qualified_name' => 'private.baz'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.foo', 'public.bar', 'private.foo', 'private.baz'], $truncatedTables);
    }

    public function testTruncateTablesWithSchemaTablesToTruncateProperty()
    {
        $this->tablesToTruncate = ['foo', 'public.bar'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations', 'schema_qualified_name' => 'public.migrations'],
            ['schema' => 'public', 'name' => 'foo', 'schema_qualified_name' => 'public.foo'],
            ['schema' => 'public', 'name' => 'bar', 'schema_qualified_name' => 'public.bar'],
            ['schema' => 'public', 'name' => 'baz', 'schema_qualified_name' => 'public.baz'],
            ['schema' => 'private', 'name' => 'migrations', 'schema_qualified_name' => 'private.migrations'],
            ['schema' => 'private', 'name' => 'foo', 'schema_qualified_name' => 'private.foo'],
            ['schema' => 'private', 'name' => 'bar', 'schema_qualified_name' => 'private.bar'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.foo', 'public.bar', 'private.foo'], $truncatedTables);
    }

    public function testTruncateTablesWithSchemaAndExceptTablesProperty()
    {
        $this->exceptTables = ['foo', 'public.bar'];

        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations', 'schema_qualified_name' => 'public.migrations'],
            ['schema' => 'public', 'name' => 'foo', 'schema_qualified_name' => 'public.foo'],
            ['schema' => 'public', 'name' => 'bar', 'schema_qualified_name' => 'public.bar'],
            ['schema' => 'public', 'name' => 'baz', 'schema_qualified_name' => 'public.baz'],
            ['schema' => 'private', 'name' => 'migrations', 'schema_qualified_name' => 'private.migrations'],
            ['schema' => 'private', 'name' => 'foo', 'schema_qualified_name' => 'private.foo'],
            ['schema' => 'private', 'name' => 'bar', 'schema_qualified_name' => 'private.bar'],
        ]);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.baz', 'private.bar'], $truncatedTables);
    }

    public function testTruncateTablesWithConnectionPrefix()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'my_migrations', 'schema_qualified_name' => 'public.my_migrations'],
            ['schema' => 'public', 'name' => 'my_foo', 'schema_qualified_name' => 'public.my_foo'],
            ['schema' => 'public', 'name' => 'my_baz', 'schema_qualified_name' => 'public.my_baz'],
            ['schema' => 'private', 'name' => 'my_migrations', 'schema_qualified_name' => 'private.my_migrations'],
            ['schema' => 'private', 'name' => 'my_foo', 'schema_qualified_name' => 'private.my_foo'],
        ], 'my_');

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.my_foo', 'public.my_baz', 'private.my_foo'], $truncatedTables);
    }

    public function testTruncateTablesOnPgsqlWithSearchPath()
    {
        $connection = $this->arrangeConnection($truncatedTables, [
            ['schema' => 'public', 'name' => 'migrations', 'schema_qualified_name' => 'public.migrations'],
            ['schema' => 'public', 'name' => 'foo', 'schema_qualified_name' => 'public.foo'],
            ['schema' => 'public', 'name' => 'bar', 'schema_qualified_name' => 'public.bar'],
            ['schema' => 'my_schema', 'name' => 'foo', 'schema_qualified_name' => 'my_schema.foo'],
            ['schema' => 'my_schema', 'name' => 'baz', 'schema_qualified_name' => 'my_schema.baz'],
            ['schema' => 'private', 'name' => 'migrations', 'schema_qualified_name' => 'private.migrations'],
            ['schema' => 'private', 'name' => 'foo', 'schema_qualified_name' => 'private.foo'],
            ['schema' => 'private', 'name' => 'baz', 'schema_qualified_name' => 'private.baz'],
        ], '', PostgresBuilder::class, ['my_schema', 'public']);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['public.foo', 'public.bar', 'my_schema.foo', 'my_schema.baz'], $truncatedTables);
    }

    public function testTruncateTablesInChunks()
    {
        $connection = $this->arrangeConnection($truncatedTables, array_map(
            fn ($i) => ['schema' => null, 'name' => "foo_{$i}", 'schema_qualified_name' => "foo_{$i}"], range(1, 101)
        ), tablesWithRows: ['foo_1', 'foo_100', 'foo_101'], emptiedTables: ['foo_100']);

        $this->truncateTablesForConnection($connection, 'test');

        $this->assertEquals(['foo_1', 'foo_101'], $truncatedTables);
        $connection->received('query')->times(2);
    }

    private function arrangeConnection(
        ?array &$actual, array $allTables, string $prefix = '', ?string $builder = null, ?array $schemas = [], ?array $tablesWithRows = null, array $emptiedTables = []
    ): Connection {
        $actual = [];
        $tablesWithRows ??= array_column($allTables, 'schema_qualified_name');

        $schema = Double::for($builder ?? Builder::class);
        $schema->expects('getTables')->with($schemas)->returns(empty($schemas)
                ? $allTables
                : array_filter($allTables, fn ($table) => in_array($table['schema'], $schemas)));
        $schema->expects('getCurrentSchemaListing')->returns($schemas);

        $connection = Double::for(Connection::class);
        $connection->allows('getTablePrefix')->returns($prefix);
        $dispatcher = new Dispatcher;
        $connection->expects('getEventDispatcher')->returns($dispatcher);
        $connection->expects('unsetEventDispatcher');
        $connection->expects('setEventDispatcher')->with($dispatcher);
        $connection->expects('getSchemaBuilder')->returns($schema);
        $connection->allows('withoutTablePrefix')->resolves(function ($callback) use ($connection) {
            $callback($connection);
        });
        $connection->allows('query')->resolves(function () use ($tablesWithRows) {
            $columns = [];

            $query = Double::for(QueryBuilder::class);
            $query->allows('selectSub')->resolves(function ($table, $key) use ($query, &$columns) {
                $columns[$key] = $table->from;

                return $query;
            });
            $query->allows('first')->resolves(function () use ($tablesWithRows, &$columns) {
                return (object) array_map(fn ($table) => in_array($table, $tablesWithRows), $columns);
            });

            return $query;
        });
        $connection->allows('table')->resolves(function (string $tableName) use (&$actual, $emptiedTables) {
            $table = Double::for(QueryBuilder::class);
            $table->from = $tableName;
            $table->allows('selectRaw')->returns($table);
            $table->allows('limit')->returns($table);
            $table->allows('exists')->returns(! in_array($tableName, $emptiedTables));
            $table->allows('truncate')->resolves(function () use (&$actual, $tableName) {
                $actual[] = $tableName;
            });

            return $table;
        });

        return $connection;
    }
}
