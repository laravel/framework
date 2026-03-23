<?php

namespace Illuminate\Tests\Foundation\Exceptions\Renderer;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ListenerTest extends TestCase
{
    public function test_queries_returns_expected_shape_after_query_executed()
    {
        $connection = m::mock();

        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->with(['foo'])->andReturn(['foo']);

        $event = new QueryExecuted('select * from users where id = ?', ['foo'], 5.2, $connection);

        $listener = new Listener();

        $listener->onQueryExecuted($event);

        $queries = $listener->queries();

        $this->assertIsArray($queries);
        $this->assertCount(1, $queries);

        $query = $queries[0];

        $this->assertArrayHasKey('connectionName', $query);
        $this->assertArrayHasKey('time', $query);
        $this->assertArrayHasKey('sql', $query);
        $this->assertArrayHasKey('bindings', $query);

        $this->assertEquals('testing', $query['connectionName']);
        $this->assertEquals(5.2, $query['time']);
        $this->assertEquals('select * from users where id = ?', $query['sql']);
        $this->assertEquals(['foo'], $query['bindings']);
    }

    public function test_listener_caps_at_100_queries()
    {
        $listener = new Listener();

        $connection = m::mock();
        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->andReturnUsing(fn ($b) => $b);

        for ($i = 0; $i < 150; $i++) {
            $listener->onQueryExecuted(
                new QueryExecuted("select {$i}", [], 1.0, $connection)
            );
        }

        $this->assertCount(100, $listener->queries());
        $this->assertEquals('select 0', $listener->queries()[0]['sql']);
        $this->assertEquals('select 99', $listener->queries()[99]['sql']);
    }

    public function test_large_sql_is_truncated()
    {
        $listener = new Listener();

        $connection = m::mock();
        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->andReturnUsing(fn ($b) => $b);

        $largeSql = str_repeat('x', 5000);
        $listener->onQueryExecuted(
            new QueryExecuted($largeSql, [], 1.0, $connection)
        );

        $this->assertLessThanOrEqual(2000, strlen($listener->queries()[0]['sql']));
    }

    public function test_bindings_match_placeholder_count_in_truncated_sql()
    {
        $listener = new Listener();

        $connection = m::mock();
        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->andReturnUsing(fn ($b) => $b);

        // Build SQL with 500 placeholders — when truncated to 2000 bytes,
        // only some ? will remain, and bindings should match that count.
        $placeholders = implode(', ', array_fill(0, 500, '?'));
        $sql = "INSERT INTO t (a) VALUES ({$placeholders})";
        $bindings = array_fill(0, 500, 'value');

        $listener->onQueryExecuted(
            new QueryExecuted($sql, $bindings, 1.0, $connection)
        );

        $storedQuery = $listener->queries()[0];
        $storedPlaceholders = substr_count($storedQuery['sql'], '?');

        $this->assertCount($storedPlaceholders, $storedQuery['bindings']);
    }

    public function test_excess_bindings_are_trimmed_to_match_placeholders()
    {
        $listener = new Listener();

        $connection = m::mock();
        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->andReturnUsing(fn ($b) => $b);

        // 1 placeholder but 1000 bindings — only 1 binding should be kept
        $listener->onQueryExecuted(
            new QueryExecuted('select ?', array_fill(0, 1000, 'v'), 1.0, $connection)
        );

        $this->assertCount(1, $listener->queries()[0]['bindings']);
    }

    public function test_short_sql_and_bindings_are_not_modified()
    {
        $listener = new Listener();

        $connection = m::mock();
        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->andReturnUsing(fn ($b) => $b);

        $sql = 'select * from users where name = ?';
        $listener->onQueryExecuted(
            new QueryExecuted($sql, ['John'], 1.0, $connection)
        );

        $this->assertEquals($sql, $listener->queries()[0]['sql']);
        $this->assertEquals(['John'], $listener->queries()[0]['bindings']);
    }

    public function test_query_with_no_bindings_is_unchanged()
    {
        $listener = new Listener();

        $connection = m::mock();
        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->andReturnUsing(fn ($b) => $b);

        $listener->onQueryExecuted(
            new QueryExecuted('select count(*) from users', [], 1.0, $connection)
        );

        $this->assertEquals('select count(*) from users', $listener->queries()[0]['sql']);
        $this->assertEmpty($listener->queries()[0]['bindings']);
    }

    public function test_normal_query_skips_truncation()
    {
        $listener = new Listener();

        $connection = m::mock();
        $connection->shouldReceive('getName')->andReturn('testing');
        $connection->shouldReceive('prepareBindings')->andReturnUsing(fn ($b) => $b);

        $sql = 'select * from users where id = ? and name = ? and email = ?';
        $bindings = [1, 'John', 'john@example.com'];

        $listener->onQueryExecuted(
            new QueryExecuted($sql, $bindings, 1.0, $connection)
        );

        $storedQuery = $listener->queries()[0];

        // Nothing should be modified — SQL is short and bindings match placeholders
        $this->assertEquals($sql, $storedQuery['sql']);
        $this->assertEquals($bindings, $storedQuery['bindings']);
    }
}
