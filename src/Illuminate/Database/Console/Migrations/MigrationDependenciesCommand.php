<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\DependencyGraph;
use Illuminate\Database\Migrations\MigrationDependencyResolver;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:dependencies')]
class MigrationDependenciesCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:dependencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show migration dependency relationships';

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
     * Create a new migration dependencies command instance.
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

        $this->components->info('Analyzing migration dependencies...');

        try {
            $analysis = $this->resolver->analyzeDependencies($paths);

            if ($this->option('json')) {
                return $this->outputJson($analysis);
            }

            if ($this->option('dot')) {
                return $this->outputDot($analysis);
            }

            return $this->outputHuman($analysis);

        } catch (\Exception $e) {
            $this->components->error('Failed to analyze dependencies: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Output analysis results in human-readable format.
     *
     * @param  array  $analysis
     * @return int
     */
    protected function outputHuman(array $analysis)
    {
        $this->newLine();
        $this->line('<fg=green;options=bold>Migration Dependency Analysis</>');
        $this->newLine();

        // Statistics
        $this->displayStatistics($analysis);

        // Dependencies
        if (! empty($analysis['dependencies'])) {
            $this->displayDependencies($analysis['dependencies']);
        }

        // Suggested order
        if (! empty($analysis['suggestedOrder'])) {
            $this->displaySuggestedOrder($analysis['suggestedOrder']);
        }

        // Conflicts
        if (! empty($analysis['conflicts'])) {
            $this->displayConflicts($analysis['conflicts']);

            return 1;
        }

        // Tables
        if ($this->option('tables')) {
            $this->displayTables($analysis['tables']);
        }

        // Foreign keys
        if ($this->option('foreign-keys')) {
            $this->displayForeignKeys($analysis['foreignKeys']);
        }

        return 0;
    }

    /**
     * Display statistics.
     *
     * @param  array  $analysis
     * @return void
     */
    protected function displayStatistics(array $analysis)
    {
        $totalMigrations = count($analysis['dependencies']);
        $migrationsWithDeps = count(array_filter($analysis['dependencies'], fn ($deps) => ! empty($deps)));
        $totalConflicts = count($analysis['conflicts']);

        $this->components->twoColumnDetail('Total migrations', $totalMigrations);
        $this->components->twoColumnDetail('Migrations with dependencies', $migrationsWithDeps);
        $this->components->twoColumnDetail('Conflicts detected', $totalConflicts);

        if ($totalConflicts > 0) {
            $this->components->twoColumnDetail('Status', '<fg=red>Issues found</>');
        } else {
            $this->components->twoColumnDetail('Status', '<fg=green>No conflicts</>');
        }

        $this->newLine();
    }

    /**
     * Display dependencies.
     *
     * @param  array  $dependencies
     * @return void
     */
    protected function displayDependencies(array $dependencies)
    {
        $this->line('<fg=yellow;options=bold>Dependencies:</>');
        $this->newLine();

        foreach ($dependencies as $migration => $deps) {
            if (empty($deps)) {
                $this->components->twoColumnDetail($this->formatMigrationName($migration), '<fg=gray>No dependencies</>');
            } else {
                $depsList = implode(', ', array_map([$this, 'formatMigrationName'], $deps));
                $this->components->twoColumnDetail($this->formatMigrationName($migration), $depsList);
            }
        }

        $this->newLine();
    }

    /**
     * Display suggested order.
     *
     * @param  array  $suggestedOrder
     * @return void
     */
    protected function displaySuggestedOrder(array $suggestedOrder)
    {
        $this->line('<fg=yellow;options=bold>Suggested Execution Order:</>');
        $this->newLine();

        foreach ($suggestedOrder as $index => $migration) {
            $order = $index + 1;
            $this->components->twoColumnDetail("$order.", $this->formatMigrationName($migration));
        }

        $this->newLine();
    }

    /**
     * Display conflicts.
     *
     * @param  array  $conflicts
     * @return void
     */
    protected function displayConflicts(array $conflicts)
    {
        $this->line('<fg=red;options=bold>Conflicts Detected:</>');
        $this->newLine();

        foreach ($conflicts as $conflict) {
            $severity = $conflict['severity'] === 'error' ? 'red' : 'yellow';

            $this->line("<fg=$severity;options=bold>{$conflict['type']}:</>");
            $this->line("  <fg=$severity>{$conflict['message']}</>");

            if (isset($conflict['migration'])) {
                $this->line("  Migration: {$this->formatMigrationName($conflict['migration'])}");
            }

            if (isset($conflict['migrations'])) {
                $this->line('  Migrations: '.implode(' → ', array_map([$this, 'formatMigrationName'], $conflict['migrations'])));
            }

            if (isset($conflict['conflictingMigrations'])) {
                $this->line('  Conflicting migrations: '.implode(', ', array_map([$this, 'formatMigrationName'], $conflict['conflictingMigrations'])));
            }

            $this->newLine();
        }
    }

    /**
     * Display tables.
     *
     * @param  array  $tables
     * @return void
     */
    protected function displayTables(array $tables)
    {
        $this->line('<fg=yellow;options=bold>Table Operations:</>');
        $this->newLine();

        foreach ($tables as $migration => $operations) {
            $this->line("<fg=blue;options=bold>{$this->formatMigrationName($migration)}:</>");

            if (! empty($operations['created'])) {
                $this->line('  <fg=green>Created:</> '.implode(', ', $operations['created']));
            }

            if (! empty($operations['modified'])) {
                $this->line('  <fg=yellow>Modified:</> '.implode(', ', $operations['modified']));
            }

            if (! empty($operations['dropped'])) {
                $this->line('  <fg=red>Dropped:</> '.implode(', ', $operations['dropped']));
            }

            $this->newLine();
        }
    }

    /**
     * Display foreign keys.
     *
     * @param  array  $foreignKeys
     * @return void
     */
    protected function displayForeignKeys(array $foreignKeys)
    {
        $this->line('<fg=yellow;options=bold>Foreign Key Relationships:</>');
        $this->newLine();

        foreach ($foreignKeys as $migration => $keys) {
            if (! empty($keys)) {
                $this->line("<fg=blue;options=bold>{$this->formatMigrationName($migration)}:</>");

                foreach ($keys as $key) {
                    $this->line("  {$key['column']} → {$key['on']}.{$key['references']}");
                }

                $this->newLine();
            }
        }
    }

    /**
     * Output analysis results in JSON format.
     *
     * @param  array  $analysis
     * @return int
     */
    protected function outputJson(array $analysis)
    {
        $this->line(json_encode($analysis, JSON_PRETTY_PRINT));

        return 0;
    }

    /**
     * Output analysis results in DOT format.
     *
     * @param  array  $analysis
     * @return int
     */
    protected function outputDot(array $analysis)
    {
        $graph = new DependencyGraph($analysis['dependencies']);
        $this->line($graph->toDot());

        return 0;
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
            ['json', null, InputOption::VALUE_NONE, 'Output the dependencies in JSON format'],
            ['dot', null, InputOption::VALUE_NONE, 'Output the dependencies in DOT format for graph visualization'],
            ['tables', null, InputOption::VALUE_NONE, 'Show table operations for each migration'],
            ['foreign-keys', null, InputOption::VALUE_NONE, 'Show foreign key relationships for each migration'],
            ['full-names', null, InputOption::VALUE_NONE, 'Show full migration names including timestamps'],
        ];
    }
}
