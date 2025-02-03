<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'key:rotate')]
class RotateKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:rotate 
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate application encryption key and store previous keys';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // 1. Get current APP_KEY
        $currentKey = $this->laravel['config']['app.key'];

        // 2. Get current APP_PREVIOUS_KEYS as array and prepend the current key
        $previousKeys = Arr::prepend($this->laravel['config']['app.previous_keys'] ?? [], $currentKey);

        // 3. Update .env file with the new APP_PREVIOUS_KEYS and clear APP_KEY
        if (! $this->updateEnvFile($currentKey, $previousKeys)) {
            $this->components->error('Failed to update the environment file.');

            return;
        }

        // 4. Notify the user and generate a new key
        $this->components->info('Current application key has been saved successfully. Running php artisan key:generate --force to generate a new key.');
        $this->call('key:generate', ['--force' => true]);
        $this->components->info('Application key has been rotated successfully.');
    }

    /**
     * Update the .env file with the new APP_PREVIOUS_KEYS and clear APP_KEY.
     */
    protected function updateEnvFile(string $currentKey, array $previousKeys): bool
    {
        $envPath = $this->laravel->environmentFilePath();
        $contents = file_get_contents($envPath);

        // Convert array to comma-separated string and wrap in quotes
        $quotedPreviousKeys = '"'.implode(',', $previousKeys).'"';

        // Update APP_PREVIOUS_KEYS
        if (str_contains($contents, 'APP_PREVIOUS_KEYS=')) {
            // If APP_PREVIOUS_KEYS already exists, update its value
            $contents = preg_replace($this->previousKeysPattern(), 'APP_PREVIOUS_KEYS='.$quotedPreviousKeys, $contents);
        } else {
            // If APP_PREVIOUS_KEYS does not exist, insert it after APP_KEY
            $contents = preg_replace($this->keyPattern(), "APP_KEY=\nAPP_PREVIOUS_KEYS=".$quotedPreviousKeys, $contents);
        }

        // Clear APP_KEY
        $contents = preg_replace($this->keyPattern(), 'APP_KEY=', $contents);
        $this->laravel['config']['app.key'] = null;

        return file_put_contents($envPath, $contents) !== false;
    }

    /**
     * Generate a regex pattern to match the current APP_KEY line.
     */
    protected function keyPattern(): string
    {
        return '/^APP_KEY='.preg_quote($this->laravel['config']['app.key'], '/').'/m';
    }

    /**
     * Generate a regex pattern to match the current APP_PREVIOUS_KEYS line.
     */
    protected function previousKeysPattern(): string
    {
        return '/^APP_PREVIOUS_KEYS="'.preg_quote(implode(',', (array) $this->laravel['config']['app.previous_keys']), '/').'"/m';
    }
}
