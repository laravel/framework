<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env:diff')]
class EnvironmentDiffCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:diff
                    {base? : The base environment file to compare against (default: .env.example)}
                    {compare? : The environment file to compare (default: .env)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare environment files and show differences';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $baseFile = $this->argument('base') ?? '.env.example';
        $compareFile = $this->argument('compare') ?? '.env';

        $basePath = base_path($baseFile);
        $comparePath = base_path($compareFile);

        if (! File::exists($basePath)) {
            $this->error("Base file '{$baseFile}' does not exist.");

            return 1;
        }

        if (! File::exists($comparePath)) {
            $this->error("Compare file '{$compareFile}' does not exist.");

            return 1;
        }

        $baseEnv = $this->parseEnvFile($basePath);
        $compareEnv = $this->parseEnvFile($comparePath);

        $this->displayDifferences($baseEnv, $compareEnv, $baseFile, $compareFile);

        return 0;
    }

    /**
     * Parse an environment file and return an array of key-value pairs.
     *
     * @param  string  $filePath
     * @return array
     */
    protected function parseEnvFile(string $filePath): array
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $env = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key=value pairs
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }

        return $env;
    }

    /**
     * Display the differences between two environment files.
     *
     * @param  array  $baseEnv
     * @param  array  $compareEnv
     * @param  string  $baseFile
     * @param  string  $compareFile
     * @return void
     */
    protected function displayDifferences(array $baseEnv, array $compareEnv, string $baseFile, string $compareFile): void
    {
        $this->info("Comparing {$baseFile} with {$compareFile}");
        $this->newLine();

        $added = array_diff_key($compareEnv, $baseEnv);
        $removed = array_diff_key($baseEnv, $compareEnv);
        $changed = [];

        // Find changed values
        foreach ($baseEnv as $key => $baseValue) {
            if (isset($compareEnv[$key]) && $compareEnv[$key] !== $baseValue) {
                $changed[$key] = [
                    'base' => $baseValue,
                    'compare' => $compareEnv[$key],
                ];
            }
        }

        $hasDifferences = ! empty($added) || ! empty($removed) || ! empty($changed);

        if (! $hasDifferences) {
            $this->info('No differences found between the environment files.');

            return;
        }

        // Display added variables
        if (! empty($added)) {
            $this->warn('Added variables:');
            foreach ($added as $key => $value) {
                $this->line("  <fg=green>+ {$key}={$value}</>");
            }
            $this->newLine();
        }

        // Display removed variables
        if (! empty($removed)) {
            $this->warn('Removed variables:');
            foreach ($removed as $key => $value) {
                $this->line("  <fg=red>- {$key}={$value}</>");
            }
            $this->newLine();
        }

        // Display changed variables
        if (! empty($changed)) {
            $this->warn('Changed variables:');
            foreach ($changed as $key => $values) {
                $this->line("  <fg=yellow>~ {$key}</>");
                $this->line("    <fg=red>- {$values['base']}</>");
                $this->line("    <fg=green>+ {$values['compare']}</>");
            }
            $this->newLine();
        }

        // Summary
        $this->info(sprintf(
            'Summary: %d added, %d removed, %d changed',
            count($added),
            count($removed),
            count($changed)
        ));
    }

}