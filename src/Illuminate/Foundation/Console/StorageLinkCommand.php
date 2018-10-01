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
    protected $signature = 'storage:link
                            {--force : Force the operation to run when symlink already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from "public/storage" to "storage/app/public"';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $files = $this->laravel->make('files');

        if (file_exists(public_path('storage'))) {
            if (! $this->option('force')) {
                $this->error('The "public/storage" directory already exists.');

                return;
            }

            $files->delete(public_path('storage'));
        }

        $files->link(
            storage_path('app/public'), public_path('storage')
        );

        $this->info('The [public/storage] directory has been linked.');
    }
}
