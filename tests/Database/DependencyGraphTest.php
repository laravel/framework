<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Migrations\DependencyGraph;
use PHPUnit\Framework\TestCase;

class DependencyGraphTest extends TestCase
{
    public function testTopologicalSortWithSimpleDependencies()
    {
        $dependencies = [
            'migration_c' => ['migration_a', 'migration_b'],
            'migration_b' => ['migration_a'],
            'migration_a' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $sorted = $graph->topologicalSort();

        $this->assertEquals('migration_a', $sorted[0]);
        $this->assertEquals('migration_b', $sorted[1]);
        $this->assertEquals('migration_c', $sorted[2]);
    }

    public function testDetectsSimpleCycle()
    {
        $dependencies = [
            'migration_a' => ['migration_b'],
            'migration_b' => ['migration_a'],
        ];

        $graph = new DependencyGraph($dependencies);
        $cycles = $graph->detectCycles();

        $this->assertNotEmpty($cycles);
        $this->assertContains('migration_a', $cycles[0]);
        $this->assertContains('migration_b', $cycles[0]);
    }

    public function testDetectsComplexCycle()
    {
        $dependencies = [
            'migration_a' => ['migration_b'],
            'migration_b' => ['migration_c'],
            'migration_c' => ['migration_a'],
            'migration_d' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $cycles = $graph->detectCycles();

        $this->assertNotEmpty($cycles);
        $this->assertCount(1, $cycles);
        $this->assertCount(4, $cycles[0]); // Should include the closing node
    }

    public function testDetectsNoCyclesInAcyclicGraph()
    {
        $dependencies = [
            'migration_a' => [],
            'migration_b' => ['migration_a'],
            'migration_c' => ['migration_a'],
            'migration_d' => ['migration_b', 'migration_c'],
        ];

        $graph = new DependencyGraph($dependencies);
        $cycles = $graph->detectCycles();

        $this->assertEmpty($cycles);
        $this->assertTrue($graph->isAcyclic());
    }

    public function testFindsShortestPath()
    {
        $dependencies = [
            'migration_a' => [],
            'migration_b' => ['migration_a'],
            'migration_c' => ['migration_a'],
            'migration_d' => ['migration_b', 'migration_c'],
        ];

        $graph = new DependencyGraph($dependencies);
        $path = $graph->findShortestPath('migration_a', 'migration_d');

        $this->assertNotNull($path);
        $this->assertEquals('migration_a', $path[0]);
        $this->assertEquals('migration_d', end($path));
        $this->assertLessThanOrEqual(3, count($path)); // Should find efficient path
    }

    public function testFindsNoPathWhenImpossible()
    {
        $dependencies = [
            'migration_a' => [],
            'migration_b' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $path = $graph->findShortestPath('migration_a', 'migration_b');

        $this->assertNull($path);
    }

    public function testGetDirectDependencies()
    {
        $dependencies = [
            'migration_c' => ['migration_a', 'migration_b'],
            'migration_b' => ['migration_a'],
            'migration_a' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $deps = $graph->getDependencies('migration_c');

        $this->assertContains('migration_a', $deps);
        $this->assertContains('migration_b', $deps);
        $this->assertCount(2, $deps);
    }

    public function testGetRecursiveDependencies()
    {
        $dependencies = [
            'migration_d' => ['migration_c'],
            'migration_c' => ['migration_b'],
            'migration_b' => ['migration_a'],
            'migration_a' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $deps = $graph->getDependencies('migration_d', true);

        $this->assertContains('migration_a', $deps);
        $this->assertContains('migration_b', $deps);
        $this->assertContains('migration_c', $deps);
        $this->assertCount(3, $deps);
    }

    public function testGetDirectDependents()
    {
        $dependencies = [
            'migration_c' => ['migration_a'],
            'migration_b' => ['migration_a'],
            'migration_a' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $dependents = $graph->getDependents('migration_a');

        $this->assertContains('migration_b', $dependents);
        $this->assertContains('migration_c', $dependents);
        $this->assertCount(2, $dependents);
    }

    public function testGetRecursiveDependents()
    {
        $dependencies = [
            'migration_d' => ['migration_c'],
            'migration_c' => ['migration_b'],
            'migration_b' => ['migration_a'],
            'migration_a' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $dependents = $graph->getDependents('migration_a', true);

        $this->assertContains('migration_b', $dependents);
        $this->assertContains('migration_c', $dependents);
        $this->assertContains('migration_d', $dependents);
        $this->assertCount(3, $dependents);
    }

    public function testCalculatesGraphStatistics()
    {
        $dependencies = [
            'migration_c' => ['migration_a', 'migration_b'],
            'migration_b' => ['migration_a'],
            'migration_a' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $stats = $graph->getStatistics();

        $this->assertEquals(3, $stats['nodes']);
        $this->assertEquals(3, $stats['edges']); // a->b, a->c, b->c
        $this->assertEquals(0, $stats['cycles']);
        $this->assertTrue($stats['isAcyclic']);
    }

    public function testExportsToDotFormat()
    {
        $dependencies = [
            'migration_b' => ['migration_a'],
            'migration_a' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $dot = $graph->toDot();

        $this->assertStringContainsString('digraph MigrationDependencies', $dot);
        $this->assertStringContainsString('migration_a', $dot);
        $this->assertStringContainsString('migration_b', $dot);
        $this->assertStringContainsString('->', $dot);
    }

    public function testHandlesEmptyGraph()
    {
        $graph = new DependencyGraph([]);

        $this->assertEmpty($graph->topologicalSort());
        $this->assertEmpty($graph->detectCycles());
        $this->assertTrue($graph->isAcyclic());
        $this->assertEquals(0, $graph->getStatistics()['nodes']);
    }

    public function testGetStronglyConnectedComponents()
    {
        $dependencies = [
            'migration_a' => ['migration_b'],
            'migration_b' => ['migration_c'],
            'migration_c' => ['migration_a'],
            'migration_d' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $components = $graph->getStronglyConnectedComponents();

        $this->assertNotEmpty($components);
        $this->assertCount(1, $components); // One strongly connected component (the cycle)
        $this->assertCount(3, $components[0]); // Three nodes in the cycle
    }

    public function testTopologicalSortWithNoDependencies()
    {
        $dependencies = [
            'migration_a' => [],
            'migration_b' => [],
            'migration_c' => [],
        ];

        $graph = new DependencyGraph($dependencies);
        $sorted = $graph->topologicalSort();

        $this->assertCount(3, $sorted);
        $this->assertContains('migration_a', $sorted);
        $this->assertContains('migration_b', $sorted);
        $this->assertContains('migration_c', $sorted);
    }
}
