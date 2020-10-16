<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class StorageLinkCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:link {--relative : Create the symbolic link using relative paths}';

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
                $linkDirectoryPath = dirname($link);

                if (! file_exists($linkDirectoryPath) && ! mkdir($linkDirectoryPath, 0755, true) && ! is_dir($linkDirectoryPath)) {
                    throw new RuntimeException("Directory [{$linkDirectoryPath}] was not created");
                }

                if ($this->option('relative')) {
                    $target = $this->getRelativeTarget($link, $target);
                }

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

    /**
     * Get the relative path to the target.
     *
     * @param  string  $link
     * @param  string  $target
     * @return string
     */
    protected function getRelativeTarget($link, $target)
    {
        if (! class_exists(SymfonyFilesystem::class)) {
            throw new RuntimeException('To enable support for relative links, please install the symfony/filesystem package.');
        }

        return (new SymfonyFilesystem)->makePathRelative($target, dirname($link));
    }
}
