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
                    {--absolute : Use an absolute pathname in the link (default is relative)}
                    {--force : Overwrite existing link}';

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
        $publicPath = public_path('storage');
        $targetPath = storage_path('app/public');

        // For broken symlinks, `file_exists()` returns `false`, but `is_link()` returns `true`
        if (is_link($publicPath) || file_exists($publicPath)) {
            if (! $this->option('force')) {
                return $this->error('The "public/storage" directory already exists.');
            }

            if (! unlink($publicPath)) {
                return $this->error('Failed to remove existing "public/storage" directory.');
            }

            $this->warn('Removed existing "public/storage" directory.');
        }

        $files = $this->laravel->make('files');

        if (! $this->option('absolute')) {
            $targetPath = $files->relativePath($targetPath, public_path());
        }

        $files->link($targetPath, $publicPath);

        $this->info('The [public/storage] directory has been linked.');
    }
}
