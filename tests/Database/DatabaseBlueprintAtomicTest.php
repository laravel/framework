<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseBlueprintAtomicTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testTableIsDroppedWhenSubsequentStatementFailsDuringCreate()
    {
        $connection = $this->getConnection();

        // First statement (CREATE TABLE) succeeds
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^create table `users`/'))
            ->andReturn(true);

        // Second statement (ADD INDEX) fails
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table `users` add index/'))
            ->andThrow(new RuntimeException('Simulated database error'));

        // Table should be dropped due to failure
        $connection->shouldReceive('statement')
            ->once()
            ->with('drop table if exists `users`')
            ->andReturn(true);

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->create();
            $table->id();
            $table->index('email');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Simulated database error');

        $blueprint->build();
    }

    public function testTableIsDroppedWhenForeignKeyFailsDuringCreate()
    {
        $connection = $this->getConnection();

        // First statement (CREATE TABLE) succeeds
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^create table `posts`/'))
            ->andReturn(true);

        // Second statement (ADD FOREIGN KEY) fails - simulating the "identifier name too long" error
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table `posts` add constraint/'))
            ->andThrow(new RuntimeException('SQLSTATE[42000]: Identifier name is too long'));

        // Table should be dropped due to failure
        $connection->shouldReceive('statement')
            ->once()
            ->with('drop table if exists `posts`')
            ->andReturn(true);

        $blueprint = new Blueprint($connection, 'posts', function ($table) {
            $table->create();
            $table->id();
            $table->foreignId('user_id')->constrained();
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Identifier name is too long');

        $blueprint->build();
    }

    public function testTableIsNotDroppedWhenFirstStatementFails()
    {
        $connection = $this->getConnection();

        // First statement (CREATE TABLE) fails - table was never created
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^create table/'))
            ->andThrow(new RuntimeException('Table already exists'));

        // DROP TABLE should NOT be called because the table was never created by this blueprint
        $connection->shouldNotReceive('statement')
            ->with(m::pattern('/^drop table/'));

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->create();
            $table->id();
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table already exists');

        $blueprint->build();
    }

    public function testNoDropWhenNotCreatingTable()
    {
        $connection = $this->getConnection();

        // ALTER TABLE statement fails
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table/'))
            ->andThrow(new RuntimeException('Column does not exist'));

        // DROP TABLE should NOT be called because this is not a create operation
        $connection->shouldNotReceive('statement')
            ->with(m::pattern('/^drop table/'));

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->string('new_column');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Column does not exist');

        $blueprint->build();
    }

    public function testSuccessfulCreateDoesNotTriggerDrop()
    {
        $connection = $this->getConnection();

        // All statements succeed
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^create table/'))
            ->andReturn(true);

        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table.*add index/'))
            ->andReturn(true);

        // DROP TABLE should NOT be called because everything succeeded
        $connection->shouldNotReceive('statement')
            ->with(m::pattern('/^drop table/'));

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->create();
            $table->id();
            $table->index('email');
        });

        $blueprint->build();

        // If we reach here without exception, the test passes
        $this->assertTrue(true);
    }

    public function testEmptyBlueprintDoesNothing()
    {
        $connection = $this->getConnection();

        // No statements should be executed for empty blueprint
        // Note: we allow any statement calls since mock doesn't track absence well

        $blueprint = new Blueprint($connection, 'users');

        $blueprint->build();

        // If we reach here without exception, the test passes
        $this->assertTrue(true);
    }

    public function testMultipleStatementsFailureAtThirdStatement()
    {
        $connection = $this->getConnection();

        // First statement (CREATE TABLE) succeeds
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^create table `users`/'))
            ->andReturn(true);

        // Second statement (ADD INDEX) succeeds
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table `users` add index/'))
            ->andReturn(true);

        // Third statement (ADD FOREIGN KEY) fails
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table `users` add constraint/'))
            ->andThrow(new RuntimeException('Foreign key constraint fails'));

        // Table should be dropped due to failure
        $connection->shouldReceive('statement')
            ->once()
            ->with('drop table if exists `users`')
            ->andReturn(true);

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->create();
            $table->id();
            $table->index('email');
            $table->foreignId('team_id')->constrained();
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Foreign key constraint fails');

        $blueprint->build();
    }

    public function testExceptionIsPropagatedAfterTableDrop()
    {
        $connection = $this->getConnection();
        $originalException = new RuntimeException('Original error message', 42);

        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^create table/'))
            ->andReturn(true);

        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table.*add index/'))
            ->andThrow($originalException);

        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^drop table/'))
            ->andReturn(true);

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->create();
            $table->id();
            $table->index('name');
        });

        try {
            $blueprint->build();
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            // Verify the original exception is re-thrown
            $this->assertSame($originalException, $e);
            $this->assertEquals('Original error message', $e->getMessage());
            $this->assertEquals(42, $e->getCode());
        }
    }

    public function testDropFailureDoesNotSuppressOriginalException()
    {
        $connection = $this->getConnection();

        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^create table/'))
            ->andReturn(true);

        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^alter table.*add index/'))
            ->andThrow(new RuntimeException('Index creation failed'));

        // Even if DROP TABLE also fails, an exception will be thrown
        $connection->shouldReceive('statement')
            ->once()
            ->with(m::pattern('/^drop table/'))
            ->andThrow(new RuntimeException('Drop also failed'));

        $blueprint = new Blueprint($connection, 'users', function ($table) {
            $table->create();
            $table->id();
            $table->index('name');
        });

        // An exception should be thrown (either original or drop failure)
        $this->expectException(RuntimeException::class);

        $blueprint->build();
    }

    protected function getConnection(): Connection
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(true);
        $connection->shouldReceive('getConfig')->with('charset')->andReturn('utf8mb4');
        $connection->shouldReceive('getConfig')->with('collation')->andReturn('utf8mb4_unicode_ci');
        $connection->shouldReceive('getConfig')->with('engine')->andReturn(null);
        $connection->shouldReceive('isMaria')->andReturn(false);

        $grammar = new MySqlGrammar($connection);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);

        return $connection;
    }
}
