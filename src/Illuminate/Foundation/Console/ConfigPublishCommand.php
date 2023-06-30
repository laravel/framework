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
        $config = $this->getBaseConfigurationFiles();

        $name = $this->argument('name');

        if (! is_null($name) && ! isset($config[$name])) {
            $this->components->error('Unrecognized configuration file.');

            return 1;
        }

        foreach ($config as $key => $file) {
            if ($key === $name || is_null($name)) {
                $this->publish($key, $file, $this->laravel->configPath().'/'.$key.'.php');
            }
        }
    }

    /**
     * Publish the given file to the given destination.
     *
     * @param  string  $name
     * @param  string  $file
     * @param  string  $destination
     * @return void
     */
    protected function publish(string $name, string $file, string $destination)
    {
        if (file_exists($destination) && ! $this->option('force')) {
            $this->components->error("The '{$name}' configuration file already exists.");

            return;
        }

        copy($file, $destination);

        $this->components->info("Published '{$name}' configuration file.");
    }

    /**
     * Get an array containing the base configuration files.
     *
     * @return array
     */
    protected function getBaseConfigurationFiles()
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(__DIR__.'/../../../../config') as $file) {
            $config[basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return collect($config)->sortKeys()->all();
    }
}
