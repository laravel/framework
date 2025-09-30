<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\Grammar as SchemaGrammar;
use PHPUnit\Framework\TestCase;

class FakeConnection extends Connection
{
    public function __construct()
    {
    }
    public function prepareBindings(array $bindings)
    {
        return $bindings;
    }
    public function getName()
    {
        return 'mysql';
    }
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar();
    }
    protected function getDefaultPostProcessor()
    {
        return new Processor();
    }
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar();
    }
}

final class ListenerByteBudgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal container + config('app.debug') = true
        $container = new Container();
        Container::setInstance($container);

        $config = new Repository([]);
        $config->set('app.debug', true);
        $container->instance('config', $config);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_respects_memory_budget_and_evicts_old_entries(): void
    {
        $listener = new Listener();
        $conn     = new FakeConnection();

        // Large SQL/binding to force budget pressure
        $hugeSql  = 'INSERT ' . str_repeat('X', 10000); // ~10KB
        $hugeBind = str_repeat('Y', 10000);             // ~10KB

        // Seed buffer (each entry unique; id prefixed so it survives clipping)
        for ($i = 0; $i < 100; $i++) {
            $sql  = $i . ' ' . $hugeSql;
            $bind = $i . ' ' . $hugeBind;

            $listener->onQueryExecuted(new QueryExecuted(
                $sql,
                ['b' => $bind],
                1.0,
                $conn
            ));
        }

        // Inspect internal buffer via reflection
        $ref   = new ReflectionClass($listener);
        $prop  = $ref->getProperty('queries');
        $prop->setAccessible(true);
        $queries = $prop->getValue($listener);

        // Should not exceed HARD_CAP
        $this->assertLessThanOrEqual(100, count($queries));

        // Latest entry should be clipped within byte limits
        $last = end($queries);
        $this->assertArrayHasKey('sql', $last);
        $this->assertArrayHasKey('bindings', $last);
        $this->assertLessThanOrEqual(2001, strlen($last['sql']));
        $this->assertLessThanOrEqual(513, strlen($last['bindings']['b']));

        // Keep a snapshot of the oldest entry
        $firstBefore = $queries[0];

        // Add more entries to trigger eviction under the byte budget
        for ($i = 100; $i < 120; $i++) {
            $sql  = $i . ' ' . $hugeSql;
            $bind = $i . ' ' . $hugeBind;

            $listener->onQueryExecuted(new QueryExecuted(
                $sql,
                ['b' => $bind],
                1.0,
                $conn
            ));
        }

        $queries2 = $prop->getValue($listener);
        $this->assertLessThanOrEqual(100, count($queries2));

        // Oldest entry should have been evicted (content differs)
        $firstAfter = $queries2[0];
        $this->assertNotEquals($firstBefore, $firstAfter);

        // Latest entry should reflect the newest id (e.g., 119 ...)
        $latest = end($queries2);
        $this->assertIsString($latest['sql']);
        $this->assertTrue(str_starts_with($latest['sql'], '119 '));
        $this->assertIsString($latest['bindings']['b']);
        $this->assertTrue(str_starts_with($latest['bindings']['b'], '119 '));
    }
}
