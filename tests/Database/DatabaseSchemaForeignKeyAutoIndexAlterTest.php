<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaForeignKeyAutoIndexAlterTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testForeignKeyWithAutomaticIndexCreationOnAlterTable()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        // Simulate ALTER table (no create command)
        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreignId('user_id')->constrained();

        // Trigger the addImpliedCommands to simulate full processing
        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();

        // In ALTER mode, we should have:
        // 1. An 'add' command for the column
        // 2. A 'foreign' command
        // 3. An 'index' command (auto-created)

        $addCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'add');
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        $this->assertCount(1, $addCommands);
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(1, $indexCommands);
        $this->assertEquals(['user_id'], array_values($indexCommands)[0]->columns);
    }

    public function testForeignKeyOnExistingColumnWithoutAutomaticIndexCreationOnSQLite()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        // Simulate adding a foreign key to an existing column
        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreign('existing_user_id')->references('id')->on('users');

        // Trigger the addImpliedCommands
        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should only create foreign key, no index for SQLite (will be handled in separate PR)
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);
    }

    public function testAlterTableAddMultipleForeignKeysWithAutomaticIndexes()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        // Add multiple foreign keys in one ALTER
        $blueprint->foreignId('user_id')->constrained();
        $blueprint->foreignId('category_id')->constrained();
        $blueprint->foreign('tenant_id')->references('id')->on('tenants');

        // Trigger the addImpliedCommands
        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');
        $addCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'add');

        // Should have 2 add commands (foreignId creates columns), 3 foreign keys, and 3 indexes
        $this->assertCount(2, $addCommands); // user_id and category_id columns
        $this->assertCount(3, $foreignCommands);
        $this->assertCount(3, $indexCommands);

        // Verify each index corresponds to a foreign key
        $indexColumns = array_map(fn ($cmd) => $cmd->columns, array_values($indexCommands));
        $this->assertContains(['user_id'], $indexColumns);
        $this->assertContains(['category_id'], $indexColumns);
        $this->assertContains(['tenant_id'], $indexColumns);
    }

    public function testAlterTableMixedWithAndWithoutIndex()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        // Mix of with and without index
        $blueprint->foreign('auto_index_id')->references('id')->on('users');
        $blueprint->foreign('no_index_id')->references('id')->on('users')->withoutIndex();
        $blueprint->foreignId('explicit_index_id')->index()->constrained('users');

        // First add fluent indexes
        $reflectionFluent = new \ReflectionMethod($blueprint, 'addFluentIndexes');
        $reflectionFluent->setAccessible(true);
        $reflectionFluent->invoke($blueprint);

        // Then add implied commands
        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have 3 foreign keys but only 2 indexes (auto + explicit, not no_index)
        $this->assertCount(3, $foreignCommands);
        $this->assertCount(2, $indexCommands);

        // Verify which indexes were created
        $indexColumns = array_map(fn ($cmd) => $cmd->columns, array_values($indexCommands));
        $this->assertContains(['auto_index_id'], $indexColumns);
        $this->assertContains(['explicit_index_id'], $indexColumns);
        $this->assertNotContains(['no_index_id'], $indexColumns);
    }

    public function testCreateTableVsAlterTableBehavior()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        // Test CREATE table
        $createBlueprint = new Blueprint($connection, 'posts');
        $createBlueprint->create();
        $createBlueprint->id();
        $createBlueprint->foreignId('user_id')->constrained();

        $reflection = new \ReflectionMethod($createBlueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($createBlueprint);

        $createCommands = $createBlueprint->getCommands();
        $createForeignCommands = array_filter($createCommands, fn ($cmd) => $cmd->name === 'foreign');
        $createIndexCommands = array_filter($createCommands, fn ($cmd) => $cmd->name === 'index');

        // Test ALTER table
        $alterBlueprint = new Blueprint($connection, 'posts');
        $alterBlueprint->foreignId('user_id')->constrained();

        $reflection->invoke($alterBlueprint);

        $alterCommands = $alterBlueprint->getCommands();
        $alterForeignCommands = array_filter($alterCommands, fn ($cmd) => $cmd->name === 'foreign');
        $alterIndexCommands = array_filter($alterCommands, fn ($cmd) => $cmd->name === 'index');

        // Both should have the same number of foreign keys and indexes
        $this->assertCount(1, $createForeignCommands);
        $this->assertCount(1, $createIndexCommands);
        $this->assertCount(1, $alterForeignCommands);
        $this->assertCount(1, $alterIndexCommands);

        // ALTER should also have an 'add' command for the column
        $alterAddCommands = array_filter($alterCommands, fn ($cmd) => $cmd->name === 'add');
        $this->assertCount(1, $alterAddCommands);
    }

    public function testCompoundForeignKeyAlterTableWithAutomaticIndex()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        // ALTER table scenario with compound foreign key
        $blueprint = new Blueprint($connection, 'orders');
        $blueprint->foreign(['user_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('users');

        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have compound foreign key and compound index
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(1, $indexCommands);
        $this->assertEquals(['user_id', 'tenant_id'], array_values($indexCommands)[0]->columns);
    }

    public function testCompoundForeignKeyWithWithoutIndexMethod()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'orders');
        $blueprint->foreign(['user_id', 'tenant_id'])
            ->references(['id', 'tenant_id'])
            ->on('users')
            ->withoutIndex();

        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have compound foreign key but NO index
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);
    }

    public function testCompoundForeignKeyCreateTableWithExplicitIndex()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        // CREATE table with explicit index on compound columns
        $blueprint = new Blueprint($connection, 'orders');
        $blueprint->create();
        $blueprint->id();
        $blueprint->unsignedBigInteger('user_id');
        $blueprint->unsignedBigInteger('tenant_id');
        $blueprint->index(['user_id', 'tenant_id']); // Explicit index
        $blueprint->foreign(['user_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('users');

        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have one foreign key and one index (no duplicate)
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(1, $indexCommands);
        $this->assertEquals(['user_id', 'tenant_id'], array_values($indexCommands)[0]->columns);
    }
}
