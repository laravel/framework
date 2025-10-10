<?php

namespace Illuminate\Console\Concerns;

use Symfony\Component\Console\Input\InputOption;

trait DryRunnable
{
    /**
     * Indicates whether the command is running in dry-run mode.
     *
     * @var bool
     */
    protected $isDryRun = false;

    /**
     * Collection of operations that would be performed in dry-run mode.
     *
     * @var array
     */
    protected $dryRunOperations = [];

    /**
     * Configure the command to support dry-run.
     */
    protected function configureDryRun(): void
    {
        $this->getDefinition()->addOption(new InputOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Preview the operations that would be performed without executing them'
        ));
    }

    /**
     * Determine if the command is running in dry-run mode.
     *
     * @return bool
     */
    protected function isDryRun()
    {
        return $this->option('dry-run') === true;
    }

    /**
     * Record a dry-run operation.
     *
     * @param  string  $type
     * @param  string  $description
     * @param  array  $details
     */
    protected function recordDryRunOperation(string $type, string $description, array $details = []): void
    {
        $this->dryRunOperations[] = compact('type', 'description', 'details');
    }

    /**
     * Display all recorded dry-run operations.
     */
    protected function displayDryRunOperations(): void
    {
        if (empty($this->dryRunOperations)) {
            $this->components->info('No operations would be performed.');

            return;
        }

        $this->components->warn('DRY RUN MODE - No changes will be made');
        $this->newLine();

        $this->components->info(sprintf(
            'The following %s operation(s) would be performed:',
            count($this->dryRunOperations)
        ));

        $this->newLine();

        foreach ($this->dryRunOperations as $index => $operation) {
            $number = $index + 1;

            $this->components->twoColumnDetail(
                "<fg=cyan>[{$number}] {$operation['type']}</>",
                $operation['description']
            );

            if (! empty($operation['details'])) {
                foreach ($operation['details'] as $key => $value) {
                    $displayKey = is_string($key) ? $key : '';
                    $this->line("    <fg=gray>└─</> <fg=yellow>{$displayKey}:</> {$value}");
                }
            }

            if ($index < count($this->dryRunOperations) - 1) {
                $this->newLine();
            }
        }

        $this->newLine();
        $this->components->info('Run the command without --dry-run to execute these operations.');
    }

    /**
     * Clear all recorded dry-run operations.
     */
    protected function clearDryRunOperations(): void
    {
        $this->dryRunOperations = [];
    }

    /**
     * Get all recorded dry-run operations.
     *
     * @return array<int, array{type: string, description: string, details: array}>
     */
    protected function getDryRunOperations(): array
    {
        return $this->dryRunOperations;
    }
}
