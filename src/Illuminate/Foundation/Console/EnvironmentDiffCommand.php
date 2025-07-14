<?php

namespace Illuminate\Foundation\Console;

use Dotenv\Parser\Parser;
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
        // Read the file content using Laravel's File facade
        $content = File::get($filePath);

        // Use phpdotenv's Parser to parse the .env file content
        $parser = new Parser();
        $entries = $parser->parse($content);

        $env = [];

        foreach ($entries as $entry) {
            // Only include entries with a valid key and defined value
            if ($entry->getName() && $entry->getValue()->isDefined()) {
                $env[$entry->getName()] = $entry->getValue()->get();
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
            if (isset($compareEnv[$key]) && $this->formatValue($baseValue) !== $this->formatValue($compareEnv[$key])) {
                $changed[$key] = [
                    'base' => $this->formatValue($baseValue),
                    'compare' => $this->formatValue($compareEnv[$key]),
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
                $this->line("  <fg=green>+ {$key}=".$this->formatValue($value).'</>');
            }
            $this->newLine();
        }

        // Display removed variables
        if (! empty($removed)) {
            $this->warn('Removed variables:');
            foreach ($removed as $key => $value) {
                $this->line("  <fg=red>- {$key}=".$this->formatValue($value).'</>');
            }
            $this->newLine();
        }

        // Display changed variables
        if (! empty($changed)) {
            $this->warn('Changed variables:');
            foreach ($changed as $key => $values) {
                $this->line("  <fg=yellow>~ {$key}</>");
                $this->line('    <fg=red>- '.$this->formatValue($values['base']).'</>');
                $this->line('    <fg=green>+ '.$this->formatValue($values['compare']).'</>');
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

    /**
     * Format a value for display, handling special cases.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function formatValue($value): string
    {
        if (is_null($value)) {
            return '';
        }

        // Handle Dotenv\Parser\Value objects
        if ($value instanceof \Dotenv\Parser\Value) {
            $value = $value->getChars();
        }

        $value = (string) $value;

        // Escape special characters for display
        $value = str_replace(["\n", "\r"], ['\n', '\r'], $value);

        // Quote values containing spaces or special characters
        if (preg_match('/\s|[#=]/', $value) && $value !== '') {
            $value = '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }
}