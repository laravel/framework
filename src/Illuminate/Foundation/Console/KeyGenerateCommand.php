<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Console\ConfirmableTrait;

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
                    {--encrypter= : The name of the encrypter this key will belong to (Defaults to "default")}
                    {--key= : The name of the key being set (Defaults to "APP_KEY")}
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

        $this->getEncrypterConfig()['key'] = $key;

        $this->info('Application key set successfully.');
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
            Encrypter::generateKey($this->getEncrypterConfig()['cipher'])
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
        $currentKey = $this->getCurrentKey();

        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        $line = $this->keyName().'='.$key;
        $contents = file_get_contents($this->laravel->environmentFilePath());

        // If a key is already present in the environment file replace it with
        // the new one, otherwise append the new key to the end of the file
        $newContents = preg_match($this->keyReplacementPattern(), $contents)
            ? preg_replace($this->keyReplacementPattern(), $line, $contents)
            : $contents . PHP_EOL . $line;

        file_put_contents($this->laravel->environmentFilePath(), $newContents);
    }

    /**
     * Get a regex pattern that will match the given key name with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.$this->getCurrentKey(), '/');
        return "/^".$this->keyName()."{$escaped}/m";
    }

    /**
     * @return string|null
     */
    protected function getCurrentKey()
    {
        $config = $this->getEncrypterConfig();
        return $config['key'] ?? null;
    }

    /**
     * @return array
     */
    protected function getEncrypterConfig()
    {
        $name = $this->option('encrypter') ?? 'default';
        $config = $this->laravel['config']['encryption.encrypters'];

        if (! isset($config[$name]) || ! is_array($config[$name])) {
            throw new \InvalidArgumentException("Encrypter [{$name}] not configured.");
        }

        return $config[$name];
    }

    /**
     * @return string
     */
    protected function keyName()
    {
        return $this->option('key') ?? 'APP_KEY';
    }
}
