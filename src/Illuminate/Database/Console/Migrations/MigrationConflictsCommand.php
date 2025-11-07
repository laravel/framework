<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\MigrationDependencyResolver;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:conflicts')]
class MigrationConflictsCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:conflicts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect potential conflicts in migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * The dependency resolver instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationDependencyResolver
     */
    protected $resolver;

    /**
     * Create a new migration conflicts command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @param  \Illuminate\Database\Migrations\MigrationDependencyResolver  $resolver
     */
    public function __construct(Migrator $migrator, MigrationDependencyResolver $resolver)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $paths = $this->getMigrationPaths();

        if (empty($paths)) {
            $this->components->error('No migration paths found.');

            return 1;
        }

        $this->components->info('Analyzing migration conflicts...');

        try {
            $analysis = $this->resolver->analyzeDependencies($paths);
            $conflicts = $analysis['conflicts'] ?? [];

            if ($this->option('json')) {
                return $this->outputJson($conflicts);
            }

            return $this->outputHuman($conflicts, $analysis);

        } catch (\Exception $e) {
            $this->components->error('Failed to analyze conflicts: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Output conflicts in human-readable format.
     *
     * @param  array  $conflicts
     * @param  array  $analysis
     * @return int
     */
    protected function outputHuman(array $conflicts, array $analysis)
    {
        if (empty($conflicts)) {
            $this->components->info('✅ No conflicts detected in migrations!');
            $this->newLine();
            $this->displayHealthyStats($analysis);

            return 0;
        }

        $this->newLine();
        $this->line('<fg=red;options=bold>⚠️  Migration Conflicts Detected</>');
        $this->newLine();

        // Group conflicts by type
        $conflictsByType = $this->groupConflictsByType($conflicts);

        foreach ($conflictsByType as $type => $typeConflicts) {
            $this->displayConflictType($type, $typeConflicts);
        }

        $this->displayConflictSummary($conflicts);
        $this->displayRecommendations($conflicts, $analysis);

        return 1; // Return error code to indicate conflicts found
    }

    /**
     * Group conflicts by type.
     *
     * @param  array  $conflicts
     * @return array
     */
    protected function groupConflictsByType(array $conflicts)
    {
        $grouped = [];

        foreach ($conflicts as $conflict) {
            $type = $conflict['type'] ?? 'unknown';
            $grouped[$type][] = $conflict;
        }

        return $grouped;
    }

    /**
     * Display conflicts of a specific type.
     *
     * @param  string  $type
     * @param  array  $conflicts
     * @return void
     */
    protected function displayConflictType(string $type, array $conflicts)
    {
        $typeNames = [
            'missing_table' => 'Missing Table Dependencies',
            'circular_dependency' => 'Circular Dependencies',
            'duplicate_table_creation' => 'Duplicate Table Creation',
            'foreign_key_mismatch' => 'Foreign Key Mismatches',
            'unknown' => 'Unknown Conflicts',
        ];

        $typeName = $typeNames[$type] ?? ucfirst(str_replace('_', ' ', $type));

        $this->line("<fg=yellow;options=bold>🔍 $typeName (".count($conflicts).'):</>');
        $this->newLine();

        foreach ($conflicts as $conflict) {
            $this->displaySingleConflict($conflict);
        }

        $this->newLine();
    }

    /**
     * Display a single conflict.
     *
     * @param  array  $conflict
     * @return void
     */
    protected function displaySingleConflict(array $conflict)
    {
        $severity = $conflict['severity'] ?? 'error';
        $icon = $severity === 'error' ? '❌' : '⚠️';
        $color = $severity === 'error' ? 'red' : 'yellow';

        $this->line("  $icon <fg=$color;options=bold>{$conflict['message']}</>");

        // Display additional context based on conflict type
        switch ($conflict['type']) {
            case 'missing_table':
                $this->line("     Migration: <fg=cyan>{$this->formatMigrationName($conflict['migration'])}</>");
                break;

            case 'circular_dependency':
                if (isset($conflict['migrations'])) {
                    $cycle = implode(' → ', array_map([$this, 'formatMigrationName'], $conflict['migrations']));
                    $this->line("     Cycle: <fg=cyan>$cycle</>");
                }
                break;

            case 'duplicate_table_creation':
                $this->line("     Table: <fg=cyan>{$conflict['table']}</>");
                $this->line("     Migration: <fg=cyan>{$this->formatMigrationName($conflict['migration'])}</>");
                if (isset($conflict['conflictingMigrations'])) {
                    $others = implode(', ', array_map([$this, 'formatMigrationName'], $conflict['conflictingMigrations']));
                    $this->line("     Also created by: <fg=cyan>$others</>");
                }
                break;
        }

        // Add spacing between conflicts
        $this->line('');
    }

    /**
     * Display conflict summary.
     *
     * @param  array  $conflicts
     * @return void
     */
    protected function displayConflictSummary(array $conflicts)
    {
        $errors = array_filter($conflicts, fn ($c) => ($c['severity'] ?? 'error') === 'error');
        $warnings = array_filter($conflicts, fn ($c) => ($c['severity'] ?? 'error') === 'warning');

        $this->line('<fg=red;options=bold>📊 Summary:</>');
        $this->components->twoColumnDetail('Total conflicts', count($conflicts));
        $this->components->twoColumnDetail('Errors', count($errors));
        $this->components->twoColumnDetail('Warnings', count($warnings));
        $this->newLine();
    }

    /**
     * Display recommendations.
     *
     * @param  array  $conflicts
     * @param  array  $analysis
     * @return void
     */
    protected function displayRecommendations(array $conflicts, array $analysis)
    {
        $this->line('<fg=green;options=bold>💡 Recommendations:</>');
        $this->newLine();

        $recommendations = $this->generateRecommendations($conflicts, $analysis);

        foreach ($recommendations as $recommendation) {
            $this->line("  • $recommendation");
        }

        $this->newLine();

        $this->line('<fg=blue;options=bold>🛠️  Next Steps:</>');
        $this->line('  1. Fix the conflicts listed above');
        $this->line('  2. Run <fg=cyan>php artisan migrate:dependencies</> to verify fixes');
        $this->line('  3. Use <fg=cyan>php artisan migrate:suggest-order</> for optimal execution order');

        $this->newLine();
    }

    /**
     * Generate recommendations based on conflicts.
     *
     * @param  array  $conflicts
     * @param  array  $analysis
     * @return array
     */
    protected function generateRecommendations(array $conflicts, array $analysis)
    {
        $recommendations = [];

        foreach ($conflicts as $conflict) {
            switch ($conflict['type']) {
                case 'missing_table':
                    $table = $conflict['table'] ?? 'referenced';
                    $recommendations[] = "Create a migration to establish the '$table' table before using it in foreign keys";
                    break;

                case 'circular_dependency':
                    $recommendations[] = 'Break circular dependencies by moving foreign key constraints to separate migrations';
                    break;

                case 'duplicate_table_creation':
                    $table = $conflict['table'] ?? 'table';
                    $recommendations[] = "Remove duplicate creation of '$table' - only one migration should create each table";
                    break;
            }
        }

        // General recommendations
        $hasCircularDeps = collect($conflicts)->contains(fn ($c) => $c['type'] === 'circular_dependency');
        if ($hasCircularDeps) {
            $recommendations[] = 'Consider using Schema::disableForeignKeyConstraints() temporarily during migrations';
        }

        $hasMissingTables = collect($conflicts)->contains(fn ($c) => $c['type'] === 'missing_table');
        if ($hasMissingTables) {
            $recommendations[] = 'Ensure migrations are run in dependency order using the suggested execution order';
        }

        return array_unique($recommendations);
    }

    /**
     * Display healthy statistics when no conflicts exist.
     *
     * @param  array  $analysis
     * @return void
     */
    protected function displayHealthyStats(array $analysis)
    {
        $totalMigrations = count($analysis['dependencies']);
        $migrationsWithDeps = count(array_filter($analysis['dependencies'], fn ($deps) => ! empty($deps)));

        $this->components->twoColumnDetail('Total migrations analyzed', $totalMigrations);
        $this->components->twoColumnDetail('Migrations with dependencies', $migrationsWithDeps);
        $this->components->twoColumnDetail('Foreign key relationships', $this->countForeignKeys($analysis['foreignKeys']));

        $this->newLine();
        $this->line('<fg=green>Your migrations are well-structured and conflict-free! 🎉</>');
        $this->line('<fg=gray>Run</> <fg=cyan>migrate:dependencies</> <fg=gray>to see the full dependency graph.</>');
    }

    /**
     * Count total foreign keys across all migrations.
     *
     * @param  array  $foreignKeys
     * @return int
     */
    protected function countForeignKeys(array $foreignKeys)
    {
        $count = 0;
        foreach ($foreignKeys as $keys) {
            $count += count($keys);
        }

        return $count;
    }

    /**
     * Output conflicts in JSON format.
     *
     * @param  array  $conflicts
     * @return int
     */
    protected function outputJson(array $conflicts)
    {
        $output = [
            'hasConflicts' => ! empty($conflicts),
            'totalConflicts' => count($conflicts),
            'conflicts' => $conflicts,
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));

        return empty($conflicts) ? 0 : 1;
    }

    /**
     * Format migration name for display.
     *
     * @param  string  $migration
     * @return string
     */
    protected function formatMigrationName(string $migration)
    {
        // Remove timestamp prefix for cleaner display unless --full-names is set
        if (! $this->option('full-names') && preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $migration, $matches)) {
            return $matches[1];
        }

        return $migration;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to use'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['json', null, InputOption::VALUE_NONE, 'Output the conflicts in JSON format'],
            ['full-names', null, InputOption::VALUE_NONE, 'Show full migration names including timestamps'],
        ];
    }
}
