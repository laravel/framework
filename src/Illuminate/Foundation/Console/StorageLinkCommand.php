<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class StorageLinkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'storage:link';

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

        if ($this->option('force')) {
            $files->delete(public_path('storage'));
        }

        if (file_exists(public_path('storage'))) {
            $this->error('The "public/storage" directory already exists.');

            return;
        }

        $files->link(
            storage_path('app/public'), public_path('storage')
        );

        $this->info('The [public/storage] directory has been linked.');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Override an existing symbolic link.'],
        ];
    }
}
