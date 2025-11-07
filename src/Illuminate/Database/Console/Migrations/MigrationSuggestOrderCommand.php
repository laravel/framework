<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\MigrationDependencyResolver;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate:suggest-order')]
class MigrationSuggestOrderCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:suggest-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suggest optimal execution order for migrations based on dependencies';

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
     * Create a new migration suggest order command instance.
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

        $this->components->info('Calculating optimal migration order...');

        try {
            $analysis = $this->resolver->analyzeDependencies($paths);
            $suggestedOrder = $analysis['suggestedOrder'] ?? [];
            $conflicts = $analysis['conflicts'] ?? [];

            if ($this->option('json')) {
                return $this->outputJson($suggestedOrder, $analysis);
            }

            if ($this->option('commands')) {
                return $this->outputCommands($suggestedOrder, $analysis);
            }

            return $this->outputHuman($suggestedOrder, $analysis);

        } catch (\Exception $e) {
            $this->components->error('Failed to calculate suggested order: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Output suggested order in human-readable format.
     *
     * @param  array  $suggestedOrder
     * @param  array  $analysis
     * @return int
     */
    protected function outputHuman(array $suggestedOrder, array $analysis)
    {
        $conflicts = $analysis['conflicts'] ?? [];

        $this->newLine();

        if (! empty($conflicts)) {
            $this->components->warn('⚠️  Conflicts detected! The suggested order may not be optimal.');
            $this->line('Run <fg=cyan>php artisan migrate:conflicts</> to see detailed conflict information.');
            $this->newLine();
        }

        $this->line('<fg=green;options=bold>🎯 Suggested Migration Execution Order</>');
        $this->newLine();

        if (empty($suggestedOrder)) {
            $this->components->info('No migrations found to order.');

            return 0;
        }

        $this->displayExecutionOrder($suggestedOrder, $analysis);

        if (! $this->option('no-validation')) {
            $this->displayValidation($suggestedOrder, $analysis);
        }

        if (! $this->option('no-comparison')) {
            $this->displayComparison($suggestedOrder, $analysis);
        }

        $this->displayRecommendations($analysis);

        return empty($conflicts) ? 0 : 1;
    }

    /**
     * Display the execution order.
     *
     * @param  array  $suggestedOrder
     * @param  array  $analysis
     * @return void
     */
    protected function displayExecutionOrder(array $suggestedOrder, array $analysis)
    {
        $dependencies = $analysis['dependencies'] ?? [];

        $this->components->twoColumnDetail('Total migrations', count($suggestedOrder));
        $this->components->twoColumnDetail('Migrations with dependencies', count(array_filter($dependencies, fn ($deps) => ! empty($deps))));
        $this->newLine();

        foreach ($suggestedOrder as $index => $migration) {
            $order = $index + 1;
            $migrationName = $this->formatMigrationName($migration);

            $deps = $dependencies[$migration] ?? [];
            $dependencyInfo = '';

            if (! empty($deps)) {
                $depCount = count($deps);
                $dependencyInfo = " <fg=gray>(depends on $depCount migration".($depCount > 1 ? 's' : '').')</>';
            }

            $this->components->twoColumnDetail("$order.", $migrationName.$dependencyInfo);

            // Show dependencies if verbose
            if ($this->option('verbose') && ! empty($deps)) {
                foreach ($deps as $dep) {
                    $this->line("      ↳ <fg=yellow>{$this->formatMigrationName($dep)}</>");
                }
            }
        }

        $this->newLine();
    }

    /**
     * Display validation information.
     *
     * @param  array  $suggestedOrder
     * @param  array  $analysis
     * @return void
     */
    protected function displayValidation(array $suggestedOrder, array $analysis)
    {
        $this->line('<fg=blue;options=bold>🔍 Validation Results</>');
        $this->newLine();

        $dependencies = $analysis['dependencies'] ?? [];
        $violations = [];

        // Check if all dependencies are satisfied in the suggested order
        $indexMap = array_flip($suggestedOrder);

        foreach ($dependencies as $migration => $deps) {
            if (! isset($indexMap[$migration])) {
                continue;
            }

            $migrationIndex = $indexMap[$migration];

            foreach ($deps as $dependency) {
                if (! isset($indexMap[$dependency])) {
                    $violations[] = [
                        'migration' => $migration,
                        'dependency' => $dependency,
                        'issue' => 'missing',
                    ];
                } elseif ($indexMap[$dependency] > $migrationIndex) {
                    $violations[] = [
                        'migration' => $migration,
                        'dependency' => $dependency,
                        'issue' => 'order',
                    ];
                }
            }
        }

        if (empty($violations)) {
            $this->components->info('✅ All dependencies are properly ordered');
        } else {
            $this->components->warn('⚠️  '.count($violations).' dependency violation(s) detected:');

            foreach ($violations as $violation) {
                $issue = $violation['issue'] === 'missing'
                    ? 'dependency not found in migration list'
                    : 'dependency appears after dependent migration';

                $this->line("  • {$this->formatMigrationName($violation['migration'])} → {$this->formatMigrationName($violation['dependency'])} ($issue)");
            }
        }

        $this->newLine();
    }

    /**
     * Display comparison with current order.
     *
     * @param  array  $suggestedOrder
     * @param  array  $analysis
     * @return void
     */
    protected function displayComparison(array $suggestedOrder, array $analysis)
    {
        $this->line('<fg=blue;options=bold>📊 Comparison with Current Order</>');
        $this->newLine();

        // Get current chronological order
        $currentOrder = array_keys($analysis['dependencies'] ?? []);
        sort($currentOrder); // Chronological order

        $changes = $this->calculateOrderChanges($currentOrder, $suggestedOrder);

        if (empty($changes)) {
            $this->components->info('✅ Suggested order matches current chronological order');
        } else {
            $this->components->twoColumnDetail('Migrations that need reordering', count($changes));

            if ($this->option('verbose')) {
                $this->newLine();
                $this->line('<fg=yellow>Migrations that would move:</>');

                foreach ($changes as $change) {
                    $direction = $change['direction'] === 'up' ? '↑' : '↓';
                    $this->line("  $direction {$this->formatMigrationName($change['migration'])} (position {$change['from']} → {$change['to']})");
                }
            }
        }

        $this->newLine();
    }

    /**
     * Calculate order changes between current and suggested order.
     *
     * @param  array  $currentOrder
     * @param  array  $suggestedOrder
     * @return array
     */
    protected function calculateOrderChanges(array $currentOrder, array $suggestedOrder)
    {
        $changes = [];
        $currentIndexMap = array_flip($currentOrder);
        $suggestedIndexMap = array_flip($suggestedOrder);

        foreach ($currentOrder as $migration) {
            if (isset($suggestedIndexMap[$migration])) {
                $currentIndex = $currentIndexMap[$migration];
                $suggestedIndex = $suggestedIndexMap[$migration];

                if ($currentIndex !== $suggestedIndex) {
                    $changes[] = [
                        'migration' => $migration,
                        'from' => $currentIndex + 1,
                        'to' => $suggestedIndex + 1,
                        'direction' => $suggestedIndex > $currentIndex ? 'down' : 'up',
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Display recommendations.
     *
     * @param  array  $analysis
     * @return void
     */
    protected function displayRecommendations(array $analysis)
    {
        $conflicts = $analysis['conflicts'] ?? [];

        $this->line('<fg=green;options=bold>💡 Recommendations</>');
        $this->newLine();

        if (! empty($conflicts)) {
            $this->line('  • <fg=red>Fix conflicts first</> using <fg=cyan>php artisan migrate:conflicts</> for detailed guidance');
        }

        $this->line('  • Always backup your database before running migrations in a new order');
        $this->line('  • Test the suggested order in a development environment first');
        $this->line('  • Consider creating migration dependencies explicitly in your migration files');

        if ($this->option('commands')) {
            $this->line('  • Use <fg=cyan>--commands</> flag to generate executable migration commands');
        }

        if (! $this->option('json')) {
            $this->line('  • Use <fg=cyan>--json</> flag to export the order for scripting');
        }

        $this->newLine();
    }

    /**
     * Output suggested order as executable commands.
     *
     * @param  array  $suggestedOrder
     * @param  array  $analysis
     * @return int
     */
    protected function outputCommands(array $suggestedOrder, array $analysis)
    {
        $conflicts = $analysis['conflicts'] ?? [];

        if (! empty($conflicts)) {
            $this->components->error('Cannot generate commands due to conflicts. Fix conflicts first.');

            return 1;
        }

        $this->line('#!/bin/bash');
        $this->line('# Generated migration execution commands');
        $this->line('# Run these commands in order to execute migrations with proper dependencies');
        $this->line('');

        foreach ($suggestedOrder as $migration) {
            $this->line("php artisan migrate --step=1 --path=database/migrations/{$migration}.php");
        }

        return 0;
    }

    /**
     * Output suggested order in JSON format.
     *
     * @param  array  $suggestedOrder
     * @param  array  $analysis
     * @return int
     */
    protected function outputJson(array $suggestedOrder, array $analysis)
    {
        $output = [
            'suggestedOrder' => $suggestedOrder,
            'hasConflicts' => ! empty($analysis['conflicts'] ?? []),
            'totalMigrations' => count($suggestedOrder),
            'dependencies' => $analysis['dependencies'] ?? [],
            'conflicts' => $analysis['conflicts'] ?? [],
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));

        return empty($analysis['conflicts']) ? 0 : 1;
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
            ['json', null, InputOption::VALUE_NONE, 'Output the suggested order in JSON format'],
            ['commands', null, InputOption::VALUE_NONE, 'Output executable migration commands'],
            ['full-names', null, InputOption::VALUE_NONE, 'Show full migration names including timestamps'],
            ['no-validation', null, InputOption::VALUE_NONE, 'Skip dependency validation display'],
            ['no-comparison', null, InputOption::VALUE_NONE, 'Skip comparison with current order'],
        ];
    }
}
