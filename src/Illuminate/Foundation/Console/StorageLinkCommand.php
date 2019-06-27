<?php

namespace Illuminate\Foundation\Console;

use Webmozart\PathUtil\Path;
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

        $targetPath = storage_path('app/public');

        if (! $this->option('absolute')) {
            $targetPath = Path::makeRelative($targetPath, dirname($publicPath));
        }

        $this->laravel->make('files')->link($targetPath, $publicPath);

        $this->info('The [public/storage] directory has been linked.');
    }
}
