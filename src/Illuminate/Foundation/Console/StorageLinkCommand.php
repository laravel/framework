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
    protected $description = 'Create a symbolic link from "public/storage" to "storage/app/public"';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (file_exists(public_path('storage'))) {
            return $this->error('The "public/storage" directory already exists.');
        }

        $this->laravel->make('files')->link(storage_path('app/public'), public_path('storage'));

        $this->info('The [public/storage] directory has been linked.');
    }
}
