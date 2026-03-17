<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ConfigWriter;
use Illuminate\Support\Env;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\autocomplete;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'env:set')]
class EnvironmentSetCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'env:set
                    {key? : The environment variable name (optionally with =value)}
                    {value? : The environment variable value}
                    {--config-key= : Config key in dot notation}
                    {--default= : Default value for the config env() call}
                    {--example : Add to .env.example}
                    {--force : Overwrite existing values without asking}';

    /**
     * The console command description.
     */
    protected $description = 'Set an environment variable';

    /**
     * Create a new command instance.
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        [$key, $value] = $this->parseKeyAndValue();

        if ($value === null) {
            $value = $this->argument('value') ?? $this->whenInteractive(fn () => password('What is the value?'));
        }

        if ($value === null && ! $this->input->isInteractive()) {
            $this->fail('The value argument is required in non-interactive mode.');
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
        $key = $this->argument('key') ?? $this->whenInteractive(fn () => text(
            'What is the environment variable name?',
            required: true,
            placeholder: 'E.g. MY_API_KEY',
        ));

        if ($key === null && ! $this->input->isInteractive()) {
            $this->fail('The key argument is required in non-interactive mode.');
        }

        if (! str_contains($key, '=')) {
            return [$key, null];
        }

        [$key, $value] = explode('=', $key, 2);

        $value = $this->unquote($value);

        $this->components->info('Key/value pair detected, extracted value automatically.');

        return [$key, $value];
    }

    /**
     * Remove surrounding quotes from a value.
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
     */
    protected function handleExample(string $key): void
    {
        $examplePath = $this->laravel->environmentPath().'/.env.example';

        if (! $this->files->exists($examplePath)) {
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
     */
    protected function handleConfig(string $key, string $value): void
    {
        $configKey = $this->option('config-key') ?? $this->whenInteractive(fn () => autocomplete(
            label: 'What config key should this be associated with? (Optional)',
            options: fn (string $value) => $this->getConfigKeySuggestions($value),
            placeholder: 'E.g. services.stripe.key',
            validate: fn ($value) => $this->validateConfigKey($value),
            hint: 'Enter a new or existing config key',
        ));

        if (! $configKey) {
            return;
        }

        if ($failMessage = $this->validateConfigKey($configKey)) {
            $this->fail($failMessage);
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

        $default = $this->option('default') ?? $this->whenInteractive(fn () => password(
            'What is the default value for the env() call? (Optional)',
            required: false,
            placeholder: 'E.g. null',
        ));

        $writer = new ConfigWriter($this->files);
        $writer->write($configPath, $segments, $key, $default ?? '');

        $this->components->info("Config [{$configKey}] set to env('{$key}').");
    }

    /**
     * Execute a callback when the input is interactive.
     *
     * @template TReturn
     *
     * @param  \Closure(): TReturn  $callback
     * @return TReturn|null
     */
    protected function whenInteractive(Closure $callback): mixed
    {
        return $this->input->isInteractive() ? $callback() : null;
    }

    /**
     * Validate a config key.
     *
     * @param  string  $value
     * @return string|null
     */
    protected function validateConfigKey(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (! str_contains($value, '.')) {
            return 'Config key must include at least a file and a key (e.g. services.stripe).';
        }

        if (preg_match('/[^a-zA-Z0-9_.\\-]/', $value)) {
            return 'Config key must contain only letters, numbers, underscores, dashes, and dots.';
        }

        if (preg_match('/\\.\\.|^\\.|\\.$/', $value)) {
            return 'Config key must not contain consecutive dots or leading/trailing dots.';
        }

        return null;
    }

    /**
     * Check if a variable exists in the given env file.
     */
    protected function variableExistsInFile(string $path, string $key): bool
    {
        $contents = $this->files->get($path);

        foreach (explode(PHP_EOL, $contents) as $line) {
            if (str_starts_with($line, $key.'=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format a config value for display.
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
     */
    protected function getConfigKeySuggestions(string $value): array
    {
        $segments = $value !== '' ? explode('.', $value) : [];
        $currentInput = array_pop($segments) ?? '';
        $prefix = count($segments) ? implode('.', $segments).'.' : '';

        $items = $this->getConfigItems($segments);

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

    /**
     * Get the config items.
     */
    protected function getConfigItems(array $segments): ?array
    {
        if (count($segments)) {
            return config(implode('.', $segments));
        }

        $keys = array_map(
            fn ($file) => basename($file, '.php'),
            glob($this->laravel->configPath('*.php'))
        );

        return array_combine(
            $keys,
            array_map(fn ($key) => config($key), $keys),
        );
    }
}
