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
    protected $description = 'Create symbolic links';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach($this->laravel['config']['filesystems.links'] ?? [] as $link => $target) {

            if (file_exists($link)) {
                $this->error('The [$link] directory already exists.');
            } else {
                $this->laravel->make('files')->link($target, $link);

                $this->info("The [$link] directory has been linked to [$target].");
            }
        }

        $this->info('The links have been created.');
    }
}
