<?php

namespace Illuminate\Database\Migrations;

class DependencyGraph
{
    /**
     * The graph adjacency list.
     *
     * @var array
     */
    protected $graph = [];

    /**
     * The reverse graph (for finding dependents).
     *
     * @var array
     */
    protected $reverseGraph = [];

    /**
     * Create a new dependency graph.
     *
     * @param  array  $dependencies
     */
    public function __construct(array $dependencies = [])
    {
        $this->buildGraph($dependencies);
    }

    /**
     * Build the graph from dependencies array.
     *
     * @param  array  $dependencies
     * @return void
     */
    public function buildGraph(array $dependencies)
    {
        $this->graph = [];
        $this->reverseGraph = [];

        // Initialize all nodes
        foreach ($dependencies as $migration => $deps) {
            if (! isset($this->graph[$migration])) {
                $this->graph[$migration] = [];
                $this->reverseGraph[$migration] = [];
            }

            foreach ($deps as $dependency) {
                if (! isset($this->graph[$dependency])) {
                    $this->graph[$dependency] = [];
                    $this->reverseGraph[$dependency] = [];
                }
            }
        }

        // Build adjacency lists
        foreach ($dependencies as $migration => $deps) {
            foreach ($deps as $dependency) {
                $this->graph[$dependency][] = $migration;
                $this->reverseGraph[$migration][] = $dependency;
            }
        }
    }

    /**
     * Perform topological sort using Kahn's algorithm.
     *
     * @return array
     */
    public function topologicalSort()
    {
        $inDegree = $this->calculateInDegrees();
        $queue = [];
        $result = [];

        // Find all nodes with no incoming edges
        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $queue[] = $node;
            }
        }

        while (! empty($queue)) {
            // Sort queue to ensure deterministic ordering
            sort($queue);
            $current = array_shift($queue);
            $result[] = $current;

            // For each neighbor of current node
            foreach ($this->graph[$current] ?? [] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }

        return $result;
    }

    /**
     * Calculate in-degrees for all nodes.
     *
     * @return array
     */
    protected function calculateInDegrees()
    {
        $inDegree = [];

        // Initialize all nodes with 0 in-degree
        foreach ($this->graph as $node => $neighbors) {
            $inDegree[$node] = 0;
        }

        // Calculate actual in-degrees
        foreach ($this->graph as $node => $neighbors) {
            foreach ($neighbors as $neighbor) {
                $inDegree[$neighbor] = ($inDegree[$neighbor] ?? 0) + 1;
            }
        }

        return $inDegree;
    }

    /**
     * Detect cycles in the graph using DFS.
     *
     * @return array
     */
    public function detectCycles()
    {
        $visited = [];
        $recStack = [];
        $cycles = [];

        foreach (array_keys($this->graph) as $node) {
            if (! isset($visited[$node])) {
                $cycle = $this->dfsForCycles($node, $visited, $recStack, []);
                if (! empty($cycle)) {
                    $cycles[] = $cycle;
                }
            }
        }

        return $cycles;
    }

    /**
     * DFS helper for cycle detection.
     *
     * @param  string  $node
     * @param  array  &$visited
     * @param  array  &$recStack
     * @param  array  $path
     * @return array
     */
    protected function dfsForCycles($node, &$visited, &$recStack, $path)
    {
        $visited[$node] = true;
        $recStack[$node] = true;
        $path[] = $node;

        foreach ($this->graph[$node] ?? [] as $neighbor) {
            if (! isset($visited[$neighbor])) {
                $cycle = $this->dfsForCycles($neighbor, $visited, $recStack, $path);
                if (! empty($cycle)) {
                    return $cycle;
                }
            } elseif (isset($recStack[$neighbor]) && $recStack[$neighbor]) {
                // Found a cycle - return the cycle path
                $cycleStart = array_search($neighbor, $path);
                $cycle = array_slice($path, $cycleStart);
                $cycle[] = $neighbor; // Close the cycle

                return $cycle;
            }
        }

        $recStack[$node] = false;

        return [];
    }

    /**
     * Find the shortest path between two nodes.
     *
     * @param  string  $start
     * @param  string  $end
     * @return array|null
     */
    public function findShortestPath($start, $end)
    {
        if ($start === $end) {
            return [$start];
        }

        $queue = [[$start]];
        $visited = [$start => true];

        while (! empty($queue)) {
            $path = array_shift($queue);
            $node = end($path);

            foreach ($this->graph[$node] ?? [] as $neighbor) {
                if ($neighbor === $end) {
                    return array_merge($path, [$neighbor]);
                }

                if (! isset($visited[$neighbor])) {
                    $visited[$neighbor] = true;
                    $queue[] = array_merge($path, [$neighbor]);
                }
            }
        }

        return null;
    }

    /**
     * Find all dependencies for a given migration.
     *
     * @param  string  $migration
     * @param  bool  $recursive
     * @return array
     */
    public function getDependencies($migration, $recursive = false)
    {
        if (! $recursive) {
            return $this->reverseGraph[$migration] ?? [];
        }

        $dependencies = [];
        $visited = [];
        $this->collectDependencies($migration, $dependencies, $visited);

        return array_unique($dependencies);
    }

    /**
     * Recursively collect all dependencies.
     *
     * @param  string  $migration
     * @param  array  &$dependencies
     * @param  array  &$visited
     * @return void
     */
    protected function collectDependencies($migration, &$dependencies, &$visited)
    {
        if (isset($visited[$migration])) {
            return;
        }

        $visited[$migration] = true;

        foreach ($this->reverseGraph[$migration] ?? [] as $dependency) {
            $dependencies[] = $dependency;
            $this->collectDependencies($dependency, $dependencies, $visited);
        }
    }

    /**
     * Find all dependents for a given migration.
     *
     * @param  string  $migration
     * @param  bool  $recursive
     * @return array
     */
    public function getDependents($migration, $recursive = false)
    {
        if (! $recursive) {
            return $this->graph[$migration] ?? [];
        }

        $dependents = [];
        $visited = [];
        $this->collectDependents($migration, $dependents, $visited);

        return array_unique($dependents);
    }

    /**
     * Recursively collect all dependents.
     *
     * @param  string  $migration
     * @param  array  &$dependents
     * @param  array  &$visited
     * @return void
     */
    protected function collectDependents($migration, &$dependents, &$visited)
    {
        if (isset($visited[$migration])) {
            return;
        }

        $visited[$migration] = true;

        foreach ($this->graph[$migration] ?? [] as $dependent) {
            $dependents[] = $dependent;
            $this->collectDependents($dependent, $dependents, $visited);
        }
    }

    /**
     * Get strongly connected components using Tarjan's algorithm.
     *
     * @return array
     */
    public function getStronglyConnectedComponents()
    {
        $index = 0;
        $stack = [];
        $indices = [];
        $lowLinks = [];
        $onStack = [];
        $components = [];

        foreach (array_keys($this->graph) as $node) {
            if (! isset($indices[$node])) {
                $this->tarjan($node, $index, $stack, $indices, $lowLinks, $onStack, $components);
            }
        }

        return $components;
    }

    /**
     * Tarjan's algorithm helper.
     *
     * @param  string  $node
     * @param  int  &$index
     * @param  array  &$stack
     * @param  array  &$indices
     * @param  array  &$lowLinks
     * @param  array  &$onStack
     * @param  array  &$components
     * @return void
     */
    protected function tarjan($node, &$index, &$stack, &$indices, &$lowLinks, &$onStack, &$components)
    {
        $indices[$node] = $index;
        $lowLinks[$node] = $index;
        $index++;

        array_push($stack, $node);
        $onStack[$node] = true;

        foreach ($this->graph[$node] ?? [] as $neighbor) {
            if (! isset($indices[$neighbor])) {
                $this->tarjan($neighbor, $index, $stack, $indices, $lowLinks, $onStack, $components);
                $lowLinks[$node] = min($lowLinks[$node], $lowLinks[$neighbor]);
            } elseif ($onStack[$neighbor]) {
                $lowLinks[$node] = min($lowLinks[$node], $indices[$neighbor]);
            }
        }

        if ($lowLinks[$node] === $indices[$node]) {
            $component = [];
            do {
                $w = array_pop($stack);
                $onStack[$w] = false;
                $component[] = $w;
            } while ($w !== $node);

            if (count($component) > 1) {
                $components[] = $component;
            }
        }
    }

    /**
     * Check if the graph is acyclic (DAG).
     *
     * @return bool
     */
    public function isAcyclic()
    {
        return empty($this->detectCycles());
    }

    /**
     * Get graph statistics.
     *
     * @return array
     */
    public function getStatistics()
    {
        $nodeCount = count($this->graph);
        $edgeCount = 0;

        foreach ($this->graph as $neighbors) {
            $edgeCount += count($neighbors);
        }

        $cycles = $this->detectCycles();
        $components = $this->getStronglyConnectedComponents();

        return [
            'nodes' => $nodeCount,
            'edges' => $edgeCount,
            'cycles' => count($cycles),
            'stronglyConnectedComponents' => count($components),
            'isAcyclic' => empty($cycles),
        ];
    }

    /**
     * Get the graph as an adjacency list.
     *
     * @return array
     */
    public function getGraph()
    {
        return $this->graph;
    }

    /**
     * Get the reverse graph as an adjacency list.
     *
     * @return array
     */
    public function getReverseGraph()
    {
        return $this->reverseGraph;
    }

    /**
     * Export graph to DOT format for visualization.
     *
     * @return string
     */
    public function toDot()
    {
        $dot = "digraph MigrationDependencies {\n";
        $dot .= "    rankdir=LR;\n";
        $dot .= "    node [shape=box];\n\n";

        foreach ($this->graph as $node => $neighbors) {
            $safeNode = $this->escapeDotId($node);

            if (empty($neighbors)) {
                $dot .= "    \"$safeNode\";\n";
            } else {
                foreach ($neighbors as $neighbor) {
                    $safeNeighbor = $this->escapeDotId($neighbor);
                    $dot .= "    \"$safeNode\" -> \"$safeNeighbor\";\n";
                }
            }
        }

        $dot .= "}\n";

        return $dot;
    }

    /**
     * Escape DOT identifier.
     *
     * @param  string  $id
     * @return string
     */
    protected function escapeDotId($id)
    {
        return str_replace('"', '\\"', $id);
    }
}
