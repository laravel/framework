<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'key:generate')]
class KeyGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        // Next, we will replace the application key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (! $this->setKeyInEnvironmentFile($key)) {
            return;
        }

        $this->laravel['config']['app.key'] = $key;

        $this->components->info('Application key set successfully.');
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
            Encrypter::generateKey($this->laravel['config']['app.cipher'])
        );
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $this->laravel['config']['app.key'];

        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        if (! $this->writeNewEnvironmentFileWith($key)) {
            return false;
        }

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return bool
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        $input = file_get_contents($this->laravel->environmentFilePath());

        $currentKey = $this->laravel['config']['app.key'];

        if (strlen($currentKey) !== 0) {
            $input = $this->moveToPreviousKeys($input, $currentKey);
        }

        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'APP_KEY='.$key,
            $input
        );

        if ($replaced === $input || $replaced === null) {
            $this->error('Unable to set application key. No APP_KEY variable was found in the .env file.');

            return false;
        }

        file_put_contents($this->laravel->environmentFilePath(), $replaced);

        return true;
    }

    /**
     * Move the current APP_KEY to APP_PREVIOUS_KEYS.
     *
     * @param  string  $envContent
     * @param  string  $currentKey
     * @return string
     */
    protected function moveToPreviousKeys($envContent, $currentKey)
    {
        preg_match('/^APP_PREVIOUS_KEYS=(.*)$/m', $envContent, $matches);

        $existingPreviousKeys = isset($matches[1]) ? trim($matches[1]) : '';

        $previousKeysArray = array_filter(explode(',', $existingPreviousKeys));
        $previousKeysArray[] = $currentKey;
        $newPreviousKeys = implode(',', $previousKeysArray);

        if (preg_match('/^APP_PREVIOUS_KEYS=/m', $envContent)) {
            $envContent = preg_replace(
                '/^APP_PREVIOUS_KEYS=.*$/m',
                'APP_PREVIOUS_KEYS='.$newPreviousKeys,
                $envContent
            );
        } else {
            $envContent = preg_replace(
                '/^(APP_KEY=.*$)/m',
                "$1\nAPP_PREVIOUS_KEYS=".$newPreviousKeys,
                $envContent
            );
        }

        return $envContent;
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.$this->laravel['config']['app.key'], '/');

        return "/^APP_KEY{$escaped}/m";
    }
}
