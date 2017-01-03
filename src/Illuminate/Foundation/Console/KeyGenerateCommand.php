<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class KeyGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:generate {--show : Display the key instead of modifying files}';

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
        $this->setKeyInEnvironmentFile($key);

        $this->laravel['config']['app.key'] = $key;

        $this->info("Application key [$key] set successfully.");
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return void
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $contents = file_get_contents($this->laravel->environmentFilePath());
        $prefix = 'APP_KEY=';
        if(strpos($contents, $prefix) === false) {
            $search = $contents;
            $replace = $prefix.$key . "\n" . $contents;
        } else {
            $search = $prefix.$this->laravel['config']['app.key'];
            $replace = $prefix.$key;
        }

        file_put_contents($this->laravel->environmentFilePath(), str_replace($search, $replace, $contents));
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
