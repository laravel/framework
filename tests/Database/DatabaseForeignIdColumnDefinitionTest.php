<?php
namespace Illuminate\Tests\Database\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testConstrainedMethodSetsIndexProperty()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn(new PostgresGrammar($connection));
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(false);

        $blueprint = new Blueprint($connection, 'posts');
        $column = $blueprint->foreignId('user_id');
        $this->assertObjectNotHasProperty('index', $column);
        $column->constrained();
        $this->assertTrue($column->index);
    }

    public function testConstrainedPreservesExplicitIndexFalse()
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn(new PostgresGrammar($connection));
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(false);

        $blueprint = new Blueprint($connection, 'posts');
        $column = $blueprint->foreignId('user_id');
        $column->index = false;
        $column->constrained();
        $this->assertFalse($column->index);
    }

    public function testAddFluentIndexesCreatesIndexForForeignId()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(false);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $grammar->shouldReceive('compileAdd')->andReturn('');
        $grammar->shouldReceive('compileForeign')->andReturn('');
        $grammar->shouldReceive('compileIndex')->andReturn('');

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->foreignId('user_id')->constrained();
        $blueprint->toSql();
        $commands = $blueprint->getCommands();
        $indexCommands = array_filter($commands, function ($command) {
            return $command->name === 'index' && in_array('user_id', (array) $command->columns);
        });

        $this->assertNotEmpty($indexCommands, 'An index command should be created for the foreign key column');
    }

    public function testConstrainedWithCompositeUniqueStillCreatesIndex()
    {
        $connection = m::mock(Connection::class);
        $grammar = m::mock(PostgresGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(false);
        $grammar->shouldReceive('getFluentCommands')->andReturn([]);
        $grammar->shouldReceive('compileAdd')->andReturn('');
        $grammar->shouldReceive('compileForeign')->andReturn('');
        $grammar->shouldReceive('compileUnique')->andReturn('');
        $grammar->shouldReceive('compileIndex')->andReturn('');

        $blueprint = new Blueprint($connection, 'books');
        $blueprint->id();
        $blueprint->foreignId('author_id')->constrained();
        $blueprint->foreignId('category_id')->constrained();
        $blueprint->string('title');
        $blueprint->unique(['author_id', 'title']);
        $blueprint->toSql();
        $commands = $blueprint->getCommands();
        $authorIndexCommands = array_filter($commands, function ($command) {
            return $command->name === 'index' &&
                is_array($command->columns) &&
                count($command->columns) === 1 &&
                $command->columns[0] === 'author_id';
        });
        $categoryIndexCommands = array_filter($commands, function ($command) {
            return $command->name === 'index' &&
                is_array($command->columns) &&
                count($command->columns) === 1 &&
                $command->columns[0] === 'category_id';
        });

        $this->assertNotEmpty($authorIndexCommands, 'Index should be created for author_id despite composite unique');
        $this->assertNotEmpty($categoryIndexCommands, 'Index should be created for category_id');
    }
}
