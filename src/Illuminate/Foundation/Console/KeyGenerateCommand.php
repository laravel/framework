<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
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
    public function fire()
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

        $this->info("Application key [$key] set successfully.");
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
        file_put_contents($this->laravel->environmentFilePath(), str_replace(
            'APP_KEY='.$this->laravel['config']['app.key'],
            'APP_KEY='.$key,
            file_get_contents($this->laravel->environmentFilePath())
        ));

        return true;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(random_bytes(
            $this->laravel['config']['app.cipher'] == 'AES-128-CBC' ? 16 : 32
        ));
    }
}
