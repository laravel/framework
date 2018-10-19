<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class GenerateLinksCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'links:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create links';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($links = $this->laravel['config']['filesystems.links']) {
            foreach ($links as $link => $target) {
                if (file_exists($link)) {
                    $this->error("The [$link] directory already exists.");
                } else {
                    $this->laravel->make('files')->link($target, $link);

                    $this->info("The [$link] directory has been linked to [$target].");
                }
            }
        } else {
            $this->warn('No links defined in filesystem configuration.');
        }
    }
}
