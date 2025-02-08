<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env:change')]
class EnvironmentChangeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:change 
                    {environment : The environment to change to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the environment file .env to the specified environment file .env.{environment}';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $environment = $this->argument('environment');
        $envFile = base_path('.env');
        $envExampleFile = base_path(".env.$environment");

        if (!File::exists($envExampleFile)) {
            $this->fail("The environment file .env.$environment does not exist.");
            return 1;
        }

        File::copy($envExampleFile, $envFile);
        $this->info("Environment changed to .env.$environment");
    }
}
