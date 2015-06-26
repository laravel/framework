<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class KeyGenerateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'key:generate';

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
        $key = $this->getRandomKey($this->laravel['config']['app.cipher']);

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        $path = base_path('.env');

        if (! file_exists($path)) {
            $this->error('Missing '.$path.' file.');
            return;
        }

        $envContent = file_get_contents($path);

        if (strpos($envContent, 'APP_KEY=') === false) {
            $this->error('Missing APP_KEY= in .env file.');
            return;
        }

        $envContent = str_replace('APP_KEY='.$this->laravel['config']['app.key'], 'APP_KEY='.$key, $envContent);

        file_put_contents($path, $envContent);

        $this->laravel['config']['app.key'] = $key;

        $this->info("Application key [$key] set successfully.");
    }

    /**
     * Generate a random key for the application.
     *
     * @param  string  $cipher
     * @return string
     */
    protected function getRandomKey($cipher)
    {
        if ($cipher === 'AES-128-CBC') {
            return Str::random(16);
        }

        return Str::random(32);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['show', null, InputOption::VALUE_NONE, 'Simply display the key instead of modifying files.'],
        ];
    }
}
