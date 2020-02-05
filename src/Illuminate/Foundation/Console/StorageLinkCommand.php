<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class StorageLinkCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the symbolic links configured for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->links() as $link => $target) {
            if (file_exists($link)) {
                $this->error("The [$link] link already exists.");
            } else {
                $this->laravel->make('files')->link($target, $link);

                $this->info("The [$link] link has been connected to [$target].");
            }
        }

        $this->info('The links have been created.');
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
