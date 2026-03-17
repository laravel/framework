<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ConfigWriter;
use Illuminate\Support\Env;
use Symfony\Component\Console\Attribute\AsCommand;

use Illuminate\Support\Arr;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\autocomplete;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'env:set')]
class EnvironmentSetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:set
                    {key? : The environment variable name (optionally with =value)}
                    {--value= : The environment variable value}
                    {--config-key= : Config key in dot notation}
                    {--default= : Default value for the config env() call}
                    {--example : Add to .env.example}
                    {--no-example : Do not add to .env.example}
                    {--force : Overwrite existing values without asking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set an environment variable';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        [$key, $value] = $this->parseKeyAndValue();

        if ($value === null) {
            $value = $this->option('value') ?? password('What is the value?');
        }

        $key = strtoupper($key);

        $envPath = $this->laravel->environmentFilePath();

        if (! $this->files->exists($envPath)) {
            $this->fail('Environment file not found.');
        }

        if ($this->variableExistsInFile($envPath, $key) && ! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                $this->fail("Environment variable [{$key}] already exists. Use --force to overwrite.");
            }

            if (! confirm("Environment variable [{$key}] already exists. Overwrite?", default: false)) {
                return;
            }
        }

        Env::writeVariable($key, $value, $envPath, overwrite: true);

        $this->components->info("Environment variable [{$key}] set successfully.");

        $this->handleExample($key);
        $this->handleConfig($key, $value);
    }

    /**
     * Parse the key argument, extracting an inline value if present.
     *
     * @return array{string, string|null}
     */
    protected function parseKeyAndValue(): array
    {
        $key = $this->argument('key');

        if ($key === null) {
            if (! $this->input->isInteractive()) {
                $this->fail('The key argument is required in non-interactive mode.');
            }

            $key = text('What is the environment variable name?', required: true);
        }

        if (str_contains($key, '=')) {
            [$key, $value] = explode('=', $key, 2);

            $value = $this->unquote($value);

            $this->components->info("Using value from argument: {$value}");

            return [$key, $value];
        }

        return [$key, null];
    }

    /**
     * Remove surrounding quotes from a value.
     *
     * @param  string  $value
     * @return string
     */
    protected function unquote(string $value): string
    {
        if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
            return $matches[2];
        }

        return $value;
    }

    /**
     * Handle adding the variable to .env.example.
     *
     * @param  string  $key
     * @return void
     */
    protected function handleExample(string $key): void
    {
        $examplePath = $this->laravel->environmentPath() . '/.env.example';

        if (! $this->files->exists($examplePath)) {
            return;
        }

        if ($this->option('no-example')) {
            return;
        }

        $shouldAdd = $this->option('example')
            || ($this->input->isInteractive() && confirm("Add [{$key}] to .env.example?", default: true));

        if ($shouldAdd) {
            Env::writeVariable($key, '', $examplePath);

            $this->components->info("Added [{$key}] to .env.example.");
        }
    }

    /**
     * Handle writing the variable to a config file.
     *
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    protected function handleConfig(string $key, string $value): void
    {
        $configKey = $this->option('config-key');

        if ($configKey === null && $this->input->isInteractive()) {
            $configKey = autocomplete(
                label: 'What config key should this be associated with? (Optional)',
                options: fn (string $value) => $this->getConfigKeySuggestions($value),
                placeholder: 'E.g. services.stripe.key',
                validate: fn (string $value) => $value !== '' && ! str_contains($value, '.')
                    ? 'Config key must include at least a file and a key (e.g. services.stripe).'
                    : null,
            );
        }

        if (! $configKey) {
            return;
        }

        if (! str_contains($configKey, '.')) {
            $this->fail('Config key must include at least a file and a key (e.g. services.stripe).');
        }

        $segments = explode('.', $configKey);
        $file = array_shift($segments);
        $configPath = $this->laravel->configPath("{$file}.php");

        if ($this->files->exists($configPath) && config()->has($configKey) && ! $this->option('force')) {
            $currentValue = config($configKey);

            $this->components->info("Current value of [{$configKey}]: {$this->formatConfigValue($currentValue)}");

            if (! $this->input->isInteractive()) {
                $this->fail("Config key [{$configKey}] already exists. Use --force to overwrite.");
            }

            if (! confirm("Config key [{$configKey}] already exists. Overwrite?", default: false)) {
                return;
            }
        }

        $default = $this->option('default');

        if ($default === null && $this->input->isInteractive()) {
            $default = text(
                'What is the default value for the env() call? (Optional)',
                default: '',
            );
        }

        $writer = new ConfigWriter($this->files);
        $writer->write($configPath, $segments, $key, $default ?? '');

        $this->components->info("Config [{$configKey}] set to env('{$key}').");
    }

    /**
     * Check if a variable exists in the given env file.
     *
     * @param  string  $path
     * @param  string  $key
     * @return bool
     */
    protected function variableExistsInFile(string $path, string $key): bool
    {
        $contents = $this->files->get($path);

        foreach (explode(PHP_EOL, $contents) as $line) {
            if (str_starts_with($line, $key . '=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format a config value for display.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function formatConfigValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Get config key suggestions based on the current input.
     *
     * @param  string  $value
     * @return array
     */
    protected function getConfigKeySuggestions(string $value): array
    {
        $segments = $value !== '' ? explode('.', $value) : [];
        $currentInput = array_pop($segments) ?? '';
        $prefix = count($segments) ? implode('.', $segments).'.' : '';

        $items = count($segments)
            ? config(implode('.', $segments))
            : array_combine(
                $keys = array_map(
                    fn ($file) => basename($file, '.php'),
                    glob($this->laravel->configPath('*.php'))
                ),
                array_map(fn ($key) => config($key), $keys),
            );

        if (! is_array($items)) {
            return [];
        }

        $suggestions = [];

        foreach ($items as $key => $val) {
            $suggestion = $prefix.$key;

            if (is_array($val)) {
                $suggestion .= '.';
            }

            if ($currentInput === '' || str_starts_with(strtolower((string) $key), strtolower($currentInput))) {
                $suggestions[] = $suggestion;
            }
        }

        sort($suggestions);

        return $suggestions;
    }
}
