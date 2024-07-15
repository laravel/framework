<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'storage:recreate')]
class StorageRecreateCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:recreate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreates storage directories configured for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->directories() as $directory) {
            if (file_exists($directory)) {
                $this->components->warn("The storage path [$directory] already exists.");
                continue;
            }

            $this->laravel->make('files')->ensureDirectoryExists($directory);

            $this->components->info("The storage path [$directory] has been created.");
        }
    }

    /**
     * Get the default storage directories that are configured for the application.
     *
     * @return array
     */
    protected function directories()
    {
        return [
            $this->laravel->storagePath('framework/cache/data'),
            $this->laravel->storagePath('framework/sessions'),
            $this->laravel->storagePath('framework/testings'),
            $this->laravel->storagePath('framework/views'),
        ];
    }
}
