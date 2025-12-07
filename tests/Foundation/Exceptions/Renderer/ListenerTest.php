<?php

namespace Illuminate\Tests\Foundation\Exceptions\Renderer;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

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
}
