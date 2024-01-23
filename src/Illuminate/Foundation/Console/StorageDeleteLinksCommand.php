<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'storage:delete-links')]
class StorageDeleteLinksCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:delete-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete existing links configured for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->links() as $link => $target) {
            if (! file_exists($link) || ! is_link($link)) {
                continue;
            }

            $this->laravel->make('files')->delete($link);

            $this->components->info("The [$link] link has been deleted.");
        }
    }

    /**
     * Get the symbolic links that are configured for the application.
     *
     * @return array
     */
    protected function links()
    {
        return $this->laravel['config']['filesystems.links'] ??
               [public_path('storage') => storage_path('app/public')];
    }
}
