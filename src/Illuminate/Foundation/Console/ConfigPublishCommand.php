<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'config:publish')]
class ConfigPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:publish
                    {name? : The name of the configuration file to publish}
                    {--force : Overwrite any existing configuration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish configuration files to your application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(__DIR__.'/../../../../config') as $file) {
            $config[basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        $name = $this->argument('name');

        if (! is_null($name) && ! isset($config[$name])) {
            $this->components->error('Unrecognized configuration file.');

            return 1;
        }

        foreach ($config as $key => $file) {
            if ($key !== $name && ! is_null($name)) {
                continue;
            }

            $destination = $this->laravel->configPath().'/'.$key.'.php';

            if (file_exists($destination) && ! $this->option('force')) {
                $this->components->error("The '{$key}' configuration file already exists.");

                continue;
            }

            copy($file, $destination);

            $this->components->info("Published '{$key}' configuration file.");
        }
    }
}
