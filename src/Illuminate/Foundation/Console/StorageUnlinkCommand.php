<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'storage:unlink')]
class StorageUnlinkCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:unlink
                {name? : The name of the link}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete existing symbolic links configured for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->links() as $name => $linkConfig) {
            $link = $linkConfig['link'] ?? null;
            if (! $link) {
                $this->components->error("The $name link is not configured properly.");

                continue;
            }

            if (! file_exists($link) || ! is_link($link)) {
                continue;
            }

            $this->laravel->make('files')->delete($link);

            $this->components->info("The [$link] link has been deleted.");
        }
    }

    /**
     * Get the symbolic links that are configured for the application.
     *
     * @return array
     */
    protected function links()
    {
        if ($name = $this->argument('name')) {
            $link = $this->laravel['config']['filesystems.links.'.$name] ?? null;
            if (! $link) {
                $this->components->error("No link have been configured for the [$name] name.");

                return [];
            }

            return [$name => $link];
        }

        return $this->laravel['config']['filesystems.links'] ?? [
            'public' => [
                'link' => public_path('storage'),
                'target' => storage_path('app/public'),
            ],
        ];
    }
}
