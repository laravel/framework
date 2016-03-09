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
        $app = $this->laravel;

        $key = $this->getRandomKey($app['config']['app.cipher']);

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        $path = $app->environmentPath().'/'.$app->environmentFile();

        if (file_exists($path)) {
            $content = str_replace('APP_KEY='.$app['config']['app.key'], 'APP_KEY='.$key, file_get_contents($path));

            if (! Str::contains($content, 'APP_KEY')) {
                $content = sprintf("%s\nAPP_KEY=%s\n", $content, $key);
            }

            file_put_contents($path, $content);
        }

        $app['config']['app.key'] = $key;

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
