<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;
use function implode;

#[AsCommand(name: 'storage:recreate')]
class StorageRecreateCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:recreate {--git-ignore: Should add default .gitignore if missing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreates storage directories configured for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $files)
    {
        // In this loop we will traverse for all the default storage directories and
        // "upsert" them. If the "--git-ignore" option is true, we will also create
        // the default ".gitignore" file that each of the directories should have.
        foreach ($this->directories() as $directory => $ignore) {
            $directory = $this->laravel->storagePath($directory);

            if (! $files->isDirectory($directory)) {
                $files->ensureDirectoryExists($directory);
                $this->components->info("The storage path [$directory] has been created.");
            } else {
                $this->components->warn("The storage path [$directory] already exists.");
            }

            if ($this->option('git-ignore') && ! $files->isFile("$directory/.gitignore")) {
                $files->put("$directory/.gitignore", implode("\n", $ignore));
            }
        }
    }

    /**
     * Get the default storage directories that are configured for the application.
     *
     * @return array
     */
    protected function directories()
    {
        $ignore = [
            '*',
            '!.gitignore',
        ];

        return [
            'app' => $ignore,
            'app/public' => array_merge($ignore, ['!public/']),
            'framework' => [
                'config.php',
                'routes.php',
                'schedule-*',
                'compiled.php',
                'services.json',
                'events.scanned.php',
                'routes.scanned.php',
                'down',
            ],
            'framework/cache' => $ignore,
            'framework/cache/data' => $ignore,
            'framework/sessions' => $ignore,
            'framework/testings' => $ignore,
            'framework/views' => $ignore,
            'logs',
        ];
    }
}
