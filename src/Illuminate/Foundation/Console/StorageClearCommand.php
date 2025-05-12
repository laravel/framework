<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'storage:clear')]
class StorageClearCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:clear 
                            {--disk=local : The storage disk to clear}
                            {--folder= : The specific folder within the disk}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear a specific folder or all contents of a given storage disk (e.g., local, public, s3)';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(FilesystemFactory $filesystem)
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $diskName = $this->option('disk');
        $folder = $this->option('folder');
        $folder = $folder ? trim($folder, '/') : null;

        try {
            $disk = $filesystem->disk($diskName);
        } catch (InvalidArgumentException $e) {
            $availableDisks = implode(', ', array_keys(config('filesystems.disks')));
            $this->error("Disk [{$diskName}] is not configured. Available disks: {$availableDisks}");

            return;
        }

        try {
            if ($folder) {
                if (! $disk->exists($folder)) {
                    $this->error("The folder [{$folder}] does not exist on the [{$diskName}] disk.");

                    return;
                }

                $this->clearFolder($disk, $folder);
                $this->line("Cleared folder [{$folder}] on disk [{$diskName}].");
            } else {
                $this->clearDisk($disk, $diskName);
                $this->line("Cleared all contents on disk [{$diskName}].");
            }
        } catch (\Throwable $e) {
            $this->error("An error occurred while clearing storage: {$e->getMessage()}");
        }
    }

    protected function clearDisk(Filesystem $disk, string $diskName): void
    {
        $files = $disk->files();
        $directories = $disk->directories();

        foreach ($files as $file) {
            if ($diskName === 'local' && basename($file) === '.gitignore') {
                $this->line("Skipping [.gitignore] on [{$diskName}] disk.");
                continue;
            }

            $disk->delete($file);
        }

        foreach ($directories as $directory) {
            $disk->deleteDirectory($directory);
        }
    }

    protected function clearFolder(Filesystem $disk, string $folder): void
    {
        $files = $disk->allFiles($folder);
        $directories = $disk->allDirectories($folder);

        foreach ($files as $file) {
            $disk->delete($file);
        }

        foreach (array_reverse($directories) as $directory) {
            $disk->deleteDirectory($directory);
        }
    }
}