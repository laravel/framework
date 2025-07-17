<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaForeignKeyAutoIndexTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testForeignKeyWithAutomaticIndexCreationOnPostgreSQL()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $connection->shouldReceive('getSchemaGrammar')->andReturn(m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class));
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreignId('user_id')->constrained();

        // Trigger the addAutoForeignKeyIndexes
        $reflection = new \ReflectionMethod($blueprint, 'addAutoForeignKeyIndexes');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have both foreign and index commands
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(1, $indexCommands);
        $this->assertEquals(['user_id'], array_values($indexCommands)[0]->columns);
    }

    public function testForeignKeyWithoutAutomaticIndexCreationOnSQLite()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        // Even with config enabled, SQLite should not auto-create indexes
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $grammar = m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->create();
        $blueprint->id();
        $blueprint->foreignId('user_id')->constrained();

        // Trigger the full command processing
        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should only have foreign command, no index for SQLite (will be handled in separate PR)
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);
    }

    public function testForeignKeyWithoutAutomaticIndexCreationOnMySQL()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldReceive('getSchemaGrammar')->andReturn(m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class));
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreignId('user_id')->constrained();

        // Trigger the addAutoForeignKeyIndexes
        $reflection = new \ReflectionMethod($blueprint, 'addAutoForeignKeyIndexes');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should only have foreign command, no index for MySQL
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);
    }

    public function testForeignKeyWithExplicitIndexDoesNotCreateDuplicateIndex()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $connection->shouldReceive('getSchemaGrammar')->andReturn(m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class)->shouldReceive('getFluentCommands')->andReturn([])->getMock());
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreignId('user_id')->index()->constrained();

        // Trigger the addFluentIndexes and addAutoForeignKeyIndexes
        $reflectionFluent = new \ReflectionMethod($blueprint, 'addFluentIndexes');
        $reflectionFluent->setAccessible(true);
        $reflectionFluent->invoke($blueprint);

        $reflectionAuto = new \ReflectionMethod($blueprint, 'addAutoForeignKeyIndexes');
        $reflectionAuto->setAccessible(true);
        $reflectionAuto->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have one foreign and no additional index (auto-creation skipped because of explicit index)
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(1, $indexCommands);
    }

    public function testCompoundForeignKeyWithAutomaticIndexCreation()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $connection->shouldReceive('getSchemaGrammar')->andReturn(m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class));
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreign(['user_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('users');

        // Trigger the addAutoForeignKeyIndexes
        $reflection = new \ReflectionMethod($blueprint, 'addAutoForeignKeyIndexes');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have both foreign and index commands
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(1, $indexCommands);
        $this->assertEquals(['user_id', 'tenant_id'], array_values($indexCommands)[0]->columns);
    }

    public function testForeignKeyAutomaticIndexCreationDisabledByConfig()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(false);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $connection->shouldReceive('getSchemaGrammar')->andReturn(m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class));
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreignId('user_id')->constrained();

        // Trigger the addAutoForeignKeyIndexes
        $reflection = new \ReflectionMethod($blueprint, 'addAutoForeignKeyIndexes');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should only have foreign command when config is disabled
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);
    }

    public function testForeignKeyWithoutIndexMethod()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $connection->shouldReceive('getSchemaGrammar')->andReturn(m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class));
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreign('user_id')->references('id')->on('users')->withoutIndex();

        // Trigger the addAutoForeignKeyIndexes
        $reflection = new \ReflectionMethod($blueprint, 'addAutoForeignKeyIndexes');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should only have foreign command when withoutIndex is used
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);
    }

    public function testForeignKeyWithoutIndexMethodOnMySQL()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('foreign_key_implicit_index_creation', false)->andReturn(true);
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldReceive('getSchemaGrammar')->andReturn(m::mock(\Illuminate\Database\Schema\Grammars\Grammar::class));
        $connection->shouldReceive('getSchemaBuilder')->andReturn(m::mock());

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreign('user_id')->references('id')->on('users')->withoutIndex();

        // Trigger the addAutoForeignKeyIndexes
        $reflection = new \ReflectionMethod($blueprint, 'addAutoForeignKeyIndexes');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should only have foreign command, no index (MySQL doesn't auto-create anyway)
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);

        // The withoutIndex() flag should still be set even though it has no effect on MySQL
        $this->assertTrue(isset(array_values($foreignCommands)[0]->withoutIndex));
    }

    public function testForeignIdWithoutIndexConstrained()
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
        $blueprint->foreignId('user_id')->withoutIndex()->constrained();

        // Trigger the addImpliedCommands to simulate full processing
        $reflection = new \ReflectionMethod($blueprint, 'addImpliedCommands');
        $reflection->setAccessible(true);
        $reflection->invoke($blueprint);

        $commands = $blueprint->getCommands();
        $foreignCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'foreign');
        $indexCommands = array_filter($commands, fn ($cmd) => $cmd->name === 'index');

        // Should have foreign command but no index
        $this->assertCount(1, $foreignCommands);
        $this->assertCount(0, $indexCommands);

        // The withoutIndex() flag should be set
        $this->assertTrue(isset(array_values($foreignCommands)[0]->withoutIndex));
    }
}
