<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
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
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new KeyGenerateCommand command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
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
    public function fire()
    {
        $key = $this->getRandomKey($this->laravel['config']['app.cipher']);

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        if ($this->files->exists($path = base_path('.env'))) {
            $this->updateEnvironmentFile($path, $key);
        }

        $this->laravel['config']['app.key'] = $key;

        $this->info("Application key [$key] set successfully.");
    }

    /**
     * Update the environment file.
     *
     * @param  string  $path
     * @param  string  $key
     * @return bool
     */
    protected function updateEnvironmentFile($path, $key)
    {
        $data = 'APP_KEY='.$key;

        if (Str::contains($content = $this->files->get($path), 'APP_KEY')) {
            return $this->files->put($path, str_replace('APP_KEY='.$this->laravel['config']['app.key'], $data, $content));
        }

        return $this->files->append($path, $data);
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
