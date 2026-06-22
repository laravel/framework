<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\SQLiteGrammar as QueryGrammar;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as SchemaGrammar;
use Illuminate\Database\SQLiteConnection;
use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseSQLiteStrictIdentifiersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testQueryGrammarWrapsIdentifiersInBackticksWhenEnabled()
    {
        $grammar = $this->getQueryGrammar(strictIdentifiers: true);

        $this->assertSame('`users`', $grammar->wrapTable('users'));
        $this->assertSame('`name`', $grammar->wrap('name'));
    }

    public function testQueryGrammarWrapsIdentifiersInDoubleQuotesWhenDisabled()
    {
        $grammar = $this->getQueryGrammar(strictIdentifiers: false);

        $this->assertSame('"users"', $grammar->wrapTable('users'));
        $this->assertSame('"name"', $grammar->wrap('name'));
    }

    public function testSchemaGrammarWrapsIdentifiersInBackticksWhenEnabled()
    {
        $grammar = $this->getSchemaGrammar(strictIdentifiers: true);

        $this->assertSame('`users`', $grammar->wrapTable('users'));
        $this->assertSame('`name`', $grammar->wrap('name'));
    }

    public function testSchemaGrammarWrapsIdentifiersInDoubleQuotesWhenDisabled()
    {
        $grammar = $this->getSchemaGrammar(strictIdentifiers: false);

        $this->assertSame('"users"', $grammar->wrapTable('users'));
        $this->assertSame('"name"', $grammar->wrap('name'));
    }

    public function testEscapesEmbeddedBackticks()
    {
        $grammar = $this->getQueryGrammar(strictIdentifiers: true);

        $this->assertSame('`col``name`', $grammar->wrap('col`name'));
    }

    public function testEscapesEmbeddedDoubleQuotes()
    {
        $grammar = $this->getQueryGrammar(strictIdentifiers: false);

        $this->assertSame('"col""name"', $grammar->wrap('col"name'));
    }

    public function testStarIsNeverWrapped()
    {
        $grammar = $this->getQueryGrammar(strictIdentifiers: true);

        $this->assertSame('*', $grammar->wrap('*'));
    }

    public function testDottedIdentifiersAreWrappedPerSegment()
    {
        $grammar = $this->getQueryGrammar(strictIdentifiers: true);

        $this->assertSame('`users`.`name`', $grammar->wrap('users.name'));
    }

    public function testSelectStatementUsesBackticksWhenEnabled()
    {
        $connection = $this->createRealConnection(strictIdentifiers: true);
        $grammar = $connection->getQueryGrammar();

        $compiled = $grammar->compileSelect(
            $connection->table('users')->select(['name', 'email'])
        );

        $this->assertSame('select `name`, `email` from `users`', $compiled);
    }

    public function testSelectStatementUsesDoubleQuotesWhenDisabled()
    {
        $connection = $this->createRealConnection(strictIdentifiers: false);
        $grammar = $connection->getQueryGrammar();

        $compiled = $grammar->compileSelect(
            $connection->table('users')->select(['name', 'email'])
        );

        $this->assertSame('select "name", "email" from "users"', $compiled);
    }

    public function testSchemaCreateStatementUsesBackticksWhenEnabled()
    {
        $connection = $this->createRealConnection(strictIdentifiers: true);
        $connection->getSchemaBuilder();

        $blueprint = new Blueprint($connection, 'posts');
        $blueprint->create();
        $blueprint->integer('id');
        $blueprint->string('title');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('create table `posts`', $statements[0]);
        $this->assertStringContainsString('`id` integer not null', $statements[0]);
        $this->assertStringContainsString('`title` varchar not null', $statements[0]);
        $this->assertStringNotContainsString('"', $statements[0]);
    }

    public function testThrowsForNonexistentColumn()
    {
        $connection = $this->createRealConnection(strictIdentifiers: true);

        $connection->getSchemaBuilder()->create('test_table', function ($table) {
            $table->integer('id');
            $table->string('name');
        });

        $connection->table('test_table')->insert(['id' => 1, 'name' => 'Alice']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/no such column/i');

        $connection->table('test_table')->select('nonexistent_column')->get();
    }

    public function testWorksForExistingColumns()
    {
        $connection = $this->createRealConnection(strictIdentifiers: true);

        $connection->getSchemaBuilder()->create('test_table', function ($table) {
            $table->integer('id');
            $table->string('name');
        });

        $connection->table('test_table')->insert(['id' => 1, 'name' => 'Alice']);
        $connection->table('test_table')->insert(['id' => 2, 'name' => 'Bob']);

        $results = $connection->table('test_table')->select('name')->get();

        $this->assertCount(2, $results);
        $this->assertSame('Alice', $results[0]->name);
        $this->assertSame('Bob', $results[1]->name);
    }

    public function testDefaultGrammarDoesNotThrowForNonexistentColumnDueToDqs()
    {
        $connection = $this->createRealConnection(strictIdentifiers: false);

        $connection->getSchemaBuilder()->create('test_table', function ($table) {
            $table->integer('id');
            $table->string('name');
        });

        $connection->table('test_table')->insert(['id' => 1, 'name' => 'Alice']);

        // This demonstrates the DQS problem that strict_identifiers solves.
        try {
            $results = $connection->table('test_table')->select('nonexistent_column')->get();
        } catch (QueryException) {
            $this->markTestSkipped('SQLite build has DQS disabled (SQLITE_DQS=0).');
        }

        $this->assertCount(1, $results);
    }

    public function testWhereClauseWorksWithStrictIdentifiers()
    {
        $connection = $this->createRealConnection(strictIdentifiers: true);

        $connection->getSchemaBuilder()->create('test_table', function ($table) {
            $table->integer('id');
            $table->string('name');
        });

        $connection->table('test_table')->insert(['id' => 1, 'name' => 'Alice']);
        $connection->table('test_table')->insert(['id' => 2, 'name' => 'Bob']);

        $results = $connection->table('test_table')->where('name', 'Alice')->get();

        $this->assertCount(1, $results);
        $this->assertSame(1, $results[0]->id);
    }

    public function testJsonSelectorWorksWithStrictIdentifiers()
    {
        $connection = $this->createRealConnection(strictIdentifiers: true);

        $connection->getSchemaBuilder()->create('test_table', function ($table) {
            $table->integer('id');
            $table->json('meta');
        });

        $connection->table('test_table')->insert(['id' => 1, 'meta' => json_encode(['role' => 'admin'])]);

        $results = $connection->table('test_table')->where('meta->role', 'admin')->get();

        $this->assertCount(1, $results);
        $this->assertSame(1, $results[0]->id);
    }

    public function testTablePrefixWorksWithStrictIdentifiers()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $connection = new SQLiteConnection($pdo, ':memory:', 'app_', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'app_',
            'strict_identifiers' => true,
        ]);

        $grammar = $connection->getQueryGrammar();

        $this->assertSame('`app_users`', $grammar->wrapTable('users'));
    }

    protected function getQueryGrammar(bool $strictIdentifiers = false): QueryGrammar
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');

        $grammar = new QueryGrammar($connection);
        $grammar->useStrictIdentifiers($strictIdentifiers);

        return $grammar;
    }

    protected function getSchemaGrammar(bool $strictIdentifiers = false): SchemaGrammar
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');

        $grammar = new SchemaGrammar($connection);
        $grammar->useStrictIdentifiers($strictIdentifiers);

        return $grammar;
    }

    protected function createRealConnection(bool $strictIdentifiers = false): SQLiteConnection
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return new SQLiteConnection($pdo, ':memory:', '', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'strict_identifiers' => $strictIdentifiers,
        ]);
    }
}
