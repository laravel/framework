<?php

namespace Illuminate\Database\Migrations;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MigrationDependencyResolver
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Cache for parsed migration content.
     *
     * @var array
     */
    protected $migrationCache = [];

    /**
     * Cache for dependency analysis.
     *
     * @var array
     */
    protected $dependencyCache = [];

    /**
     * Create a new dependency resolver instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     */
    public function __construct(Filesystem $files, Migrator $migrator)
    {
        $this->files = $files;
        $this->migrator = $migrator;
    }

    /**
     * Analyze migration dependencies for the given paths.
     *
     * @param  array  $paths
     * @return array
     */
    public function analyzeDependencies(array $paths)
    {
        $migrations = $this->migrator->getMigrationFiles($paths);

        // First pass: analyze each migration to populate cache
        foreach ($migrations as $name => $path) {
            $this->analyzeMigration($path, $name);
        }

        $dependencies = [];
        $tables = [];
        $foreignKeys = [];

        // Second pass: calculate dependencies and extract data from cache
        foreach ($migrations as $name => $path) {
            $analysis = $this->migrationCache[$name];

            $dependencies[$name] = $this->calculateMigrationDependencies($name);
            $tables[$name] = $analysis['tables'];
            $foreignKeys[$name] = $analysis['foreignKeys'];
        }

        return [
            'dependencies' => $dependencies,
            'tables' => $tables,
            'foreignKeys' => $foreignKeys,
            'conflicts' => $this->detectConflicts($dependencies, $tables, $foreignKeys),
            'suggestedOrder' => $this->calculateOptimalOrder($dependencies),
        ];
    }

    /**
     * Calculate dependencies for a specific migration.
     *
     * @param  string  $migrationName
     * @return array
     */
    protected function calculateMigrationDependencies(string $migrationName)
    {
        $dependencies = [];
        $analysis = $this->migrationCache[$migrationName] ?? [];

        // Get foreign key dependencies
        foreach ($analysis['foreignKeys'] ?? [] as $foreignKey) {
            $referencedTable = $foreignKey['on'];
            $tableDependencies = $this->findMigrationsCreatingTable($referencedTable, $migrationName);
            $dependencies = array_merge($dependencies, $tableDependencies);
        }

        return array_unique($dependencies);
    }

    /**
     * Analyze a single migration file.
     *
     * @param  string  $path
     * @param  string  $name
     * @return array
     */
    protected function analyzeMigration(string $path, string $name)
    {
        if (isset($this->migrationCache[$name])) {
            return $this->migrationCache[$name];
        }

        $content = $this->files->get($path);

        $analysis = [
            'dependencies' => $this->extractDependencies($content, $name),
            'tables' => $this->extractTables($content),
            'foreignKeys' => $this->extractForeignKeys($content),
            'indexes' => $this->extractIndexes($content),
            'columns' => $this->extractColumns($content),
        ];

        $this->migrationCache[$name] = $analysis;

        return $analysis;
    }

    /**
     * Extract table dependencies from migration content.
     *
     * @param  string  $content
     * @param  string  $migrationName
     * @return array
     */
    protected function extractDependencies(string $content, string $migrationName)
    {
        $dependencies = [];

        // Extract foreign key table references
        $foreignKeyTables = $this->extractForeignKeyTableReferences($content);

        // For now, return empty dependencies - they will be calculated in the second pass
        return $dependencies;
    }

    /**
     * Extract foreign key table references.
     *
     * @param  string  $content
     * @return array
     */
    protected function extractForeignKeyTableReferences(string $content)
    {
        $tables = [];

        // Pattern for ->foreign()->references()->on('table')
        if (preg_match_all('/->foreign\([^)]*\)->references\([^)]*\)->on\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }

        // Pattern for ->foreignId()->constrained('table')
        if (preg_match_all('/->foreignId\([^)]*\)->constrained\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }

        // Pattern for ->foreignIdFor(Model::class)
        if (preg_match_all('/->foreignIdFor\(([^:]+)::class\)/', $content, $matches)) {
            foreach ($matches[1] as $model) {
                // Convert model class to table name (basic pluralization)
                $table = Str::snake(Str::pluralStudly(class_basename($model)));
                $tables[] = $table;
            }
        }

        return array_unique($tables);
    }

    /**
     * Find migrations that create a specific table.
     *
     * @param  string  $tableName
     * @param  string  $excludeMigration
     * @return array
     */
    protected function findMigrationsCreatingTable(string $tableName, ?string $excludeMigration = null)
    {
        $dependencies = [];

        foreach ($this->migrationCache as $name => $analysis) {
            if ($name === $excludeMigration) {
                continue;
            }

            if (in_array($tableName, $analysis['tables']['created'] ?? [])) {
                $dependencies[] = $name;
            }
        }

        return $dependencies;
    }

    /**
     * Extract table operations from migration content.
     *
     * @param  string  $content
     * @return array
     */
    protected function extractTables(string $content)
    {
        $tables = [
            'created' => [],
            'modified' => [],
            'dropped' => [],
        ];

        // Created tables
        if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $tables['created'] = array_merge($tables['created'], $matches[1]);
        }

        // Modified tables
        if (preg_match_all('/Schema::table\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $tables['modified'] = array_merge($tables['modified'], $matches[1]);
        }

        // Dropped tables
        if (preg_match_all('/Schema::drop(?:IfExists)?\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $tables['dropped'] = array_merge($tables['dropped'], $matches[1]);
        }

        // Renamed tables
        if (preg_match_all('/Schema::rename\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $tables['dropped'] = array_merge($tables['dropped'], $matches[1]);
            $tables['created'] = array_merge($tables['created'], $matches[2]);
        }

        return [
            'created' => array_unique($tables['created']),
            'modified' => array_unique($tables['modified']),
            'dropped' => array_unique($tables['dropped']),
        ];
    }

    /**
     * Extract foreign key information from migration content.
     *
     * @param  string  $content
     * @return array
     */
    protected function extractForeignKeys(string $content)
    {
        $foreignKeys = [];

        // Standard foreign key syntax
        if (preg_match_all('/->foreign\([\'"]([^\'"]+)[\'"]\)->references\([\'"]([^\'"]+)[\'"]\)->on\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $foreignKeys[] = [
                    'column' => $matches[1][$i],
                    'references' => $matches[2][$i],
                    'on' => $matches[3][$i],
                ];
            }
        }

        // Foreign ID constrained syntax
        if (preg_match_all('/->foreignId\([\'"]([^\'"]+)[\'"]\)->constrained\(\)/', $content, $matches)) {
            foreach ($matches[1] as $column) {
                $table = Str::plural(str_replace('_id', '', $column));
                $foreignKeys[] = [
                    'column' => $column,
                    'references' => 'id',
                    'on' => $table,
                ];
            }
        }

        if (preg_match_all('/->foreignId\([\'"]([^\'"]+)[\'"]\)->constrained\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $foreignKeys[] = [
                    'column' => $matches[1][$i],
                    'references' => 'id',
                    'on' => $matches[2][$i],
                ];
            }
        }

        return $foreignKeys;
    }

    /**
     * Extract index information from migration content.
     *
     * @param  string  $content
     * @return array
     */
    protected function extractIndexes(string $content)
    {
        $indexes = [];

        // Various index types
        $indexTypes = ['index', 'unique', 'primary', 'spatialIndex'];

        foreach ($indexTypes as $type) {
            if (preg_match_all("/->$type\(([^)]+)\)/", $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $indexes[] = [
                        'type' => $type,
                        'definition' => trim($match),
                    ];
                }
            }
        }

        return $indexes;
    }

    /**
     * Extract column information from migration content.
     *
     * @param  string  $content
     * @return array
     */
    protected function extractColumns(string $content)
    {
        $columns = [
            'added' => [],
            'modified' => [],
            'dropped' => [],
        ];

        // Column creation patterns
        $columnTypes = [
            'bigIncrements', 'bigInteger', 'binary', 'boolean', 'char', 'dateTimeTz',
            'dateTime', 'date', 'decimal', 'double', 'enum', 'float', 'foreignId',
            'foreignIdFor', 'foreignUlid', 'foreignUuid', 'geometry', 'geometryCollection',
            'id', 'increments', 'integer', 'ipAddress', 'json', 'jsonb', 'lineString',
            'longText', 'macAddress', 'mediumIncrements', 'mediumInteger', 'mediumText',
            'morphs', 'multiLineString', 'multiPoint', 'multiPolygon', 'nullableMorphs',
            'nullableTimestamps', 'nullableUuidMorphs', 'point', 'polygon', 'rememberToken',
            'set', 'smallIncrements', 'smallInteger', 'softDeletes', 'softDeletesTz',
            'string', 'text', 'timeTz', 'time', 'timestampTz', 'timestamp', 'timestamps',
            'timestampsTz', 'tinyIncrements', 'tinyInteger', 'tinyText', 'unsignedBigInteger',
            'unsignedDecimal', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger',
            'unsignedTinyInteger', 'uuidMorphs', 'uuid', 'year',
        ];

        foreach ($columnTypes as $type) {
            if (preg_match_all("/->$type\([\'\"]+([^\'\"]+)[\'\"]/", $content, $matches)) {
                foreach ($matches[1] as $column) {
                    $columns['added'][] = [
                        'name' => $column,
                        'type' => $type,
                    ];
                }
            }
        }

        // Dropped columns
        if (preg_match_all('/->dropColumn\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $columns['dropped'] = array_merge($columns['dropped'], $matches[1]);
        }

        // Drop multiple columns
        if (preg_match_all('/->dropColumn\(\[(.*?)\]\)/', $content, $matches)) {
            foreach ($matches[1] as $match) {
                preg_match_all('/[\'"]([^\'"]+)[\'"]/', $match, $columnMatches);
                $columns['dropped'] = array_merge($columns['dropped'], $columnMatches[1]);
            }
        }

        return $columns;
    }

    /**
     * Detect potential conflicts in migrations.
     *
     * @param  array  $dependencies
     * @param  array  $tables
     * @param  array  $foreignKeys
     * @return array
     */
    protected function detectConflicts(array $dependencies, array $tables, array $foreignKeys)
    {
        $conflicts = [];

        // Check for missing table dependencies
        foreach ($foreignKeys as $migration => $keys) {
            foreach ($keys as $key) {
                $referencedTable = $key['on'];
                $tableDependencies = $this->findMigrationsCreatingTable($referencedTable, $migration);

                if (empty($tableDependencies)) {
                    $conflicts[] = [
                        'type' => 'missing_table',
                        'migration' => $migration,
                        'message' => "References table '{$referencedTable}' but no migration creates this table",
                        'severity' => 'error',
                    ];
                }
            }
        }

        // Check for circular dependencies
        $circularDeps = $this->detectCircularDependencies($dependencies);
        foreach ($circularDeps as $cycle) {
            $conflicts[] = [
                'type' => 'circular_dependency',
                'migrations' => $cycle,
                'message' => 'Circular dependency detected: '.implode(' -> ', $cycle),
                'severity' => 'error',
            ];
        }

        // Check for table creation conflicts
        foreach ($tables as $migration => $tableOps) {
            foreach ($tableOps['created'] as $table) {
                $otherCreators = array_filter($tables, function ($ops, $name) use ($table, $migration) {
                    return $name !== $migration && in_array($table, $ops['created'] ?? []);
                }, ARRAY_FILTER_USE_BOTH);

                if (count($otherCreators) > 0) {
                    $conflicts[] = [
                        'type' => 'duplicate_table_creation',
                        'migration' => $migration,
                        'table' => $table,
                        'conflictingMigrations' => array_keys($otherCreators),
                        'message' => "Table '{$table}' is created in multiple migrations",
                        'severity' => 'error',
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect circular dependencies.
     *
     * @param  array  $dependencies
     * @return array
     */
    protected function detectCircularDependencies(array $dependencies)
    {
        $cycles = [];
        $visited = [];
        $recStack = [];

        foreach (array_keys($dependencies) as $migration) {
            if (! isset($visited[$migration])) {
                $cycle = $this->dfsForCycles($migration, $dependencies, $visited, $recStack, []);
                if ($cycle) {
                    $cycles[] = $cycle;
                }
            }
        }

        return $cycles;
    }

    /**
     * Depth-first search for cycles.
     *
     * @param  string  $migration
     * @param  array  $dependencies
     * @param  array  &$visited
     * @param  array  &$recStack
     * @param  array  $path
     * @return array|null
     */
    protected function dfsForCycles($migration, $dependencies, &$visited, &$recStack, $path)
    {
        $visited[$migration] = true;
        $recStack[$migration] = true;
        $path[] = $migration;

        foreach ($dependencies[$migration] ?? [] as $dependency) {
            if (! isset($visited[$dependency])) {
                $cycle = $this->dfsForCycles($dependency, $dependencies, $visited, $recStack, $path);
                if ($cycle) {
                    return $cycle;
                }
            } elseif (isset($recStack[$dependency]) && $recStack[$dependency]) {
                // Found a cycle - return the cycle path
                $cycleStart = array_search($dependency, $path);

                return array_slice($path, $cycleStart);
            }
        }

        $recStack[$migration] = false;

        return null;
    }

    /**
     * Calculate optimal migration order using topological sort.
     *
     * @param  array  $dependencies
     * @return array
     */
    protected function calculateOptimalOrder(array $dependencies)
    {
        $inDegree = [];
        $migrations = array_keys($dependencies);

        // Initialize in-degree for all migrations
        foreach ($migrations as $migration) {
            $inDegree[$migration] = 0;
        }

        // Calculate in-degrees
        foreach ($dependencies as $migration => $deps) {
            foreach ($deps as $dependency) {
                if (isset($inDegree[$dependency])) {
                    $inDegree[$migration]++;
                }
            }
        }

        // Topological sort using Kahn's algorithm
        $queue = [];
        $result = [];

        // Start with migrations that have no dependencies
        foreach ($inDegree as $migration => $degree) {
            if ($degree === 0) {
                $queue[] = $migration;
            }
        }

        while (! empty($queue)) {
            $current = array_shift($queue);
            $result[] = $current;

            // For each migration that depends on current migration
            foreach ($migrations as $migration) {
                if (in_array($current, $dependencies[$migration] ?? [])) {
                    $inDegree[$migration]--;
                    if ($inDegree[$migration] === 0) {
                        $queue[] = $migration;
                    }
                }
            }
        }

        // If we haven't included all migrations, there's a cycle
        if (count($result) !== count($migrations)) {
            // Return chronological order as fallback
            return $migrations;
        }

        return $result;
    }

    /**
     * Get migration analysis in JSON format.
     *
     * @param  array  $paths
     * @return string
     */
    public function getAnalysisJson(array $paths)
    {
        return json_encode($this->analyzeDependencies($paths), JSON_PRETTY_PRINT);
    }
}
