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

        if ($this->isWindows()) {
            exec('mklink /J "'.public_path('storage').'" "'.storage_path('app/public').'"');
        } else {
            symlink(storage_path('app/public'), public_path('storage'));
        }

        $this->info('The [public/storage] directory has been linked.');
    }

    /**
     * Checks whether the system is running on Windows.
     *
     * @return bool
     */
    protected function isWindows()
    {
        return DIRECTORY_SEPARATOR == '\\';
    }
}
