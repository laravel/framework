<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\ScheduleDiscoverer;
use Illuminate\Console\Scheduling\ScheduleManifest;

class ScheduleCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and cache the application scheduled tasks';

    /**
     * Execute the console command.
     *
     * @param  ScheduleDiscoverer  $discoverer
     * @param  ScheduleManifest  $manifest
     * @return int
     */
    public function handle(
        ScheduleDiscoverer $discoverer,
        ScheduleManifest $manifest
    ): int {
        $tasks = $discoverer->discover(
            path: $this->laravel->path(),
            namespace: $this->laravel->getNamespace(),
        );

        $manifest->write($tasks);

        $this->components->info(
            sprintf(
                '%d scheduled %s cached successfully.',
                count($tasks),
                count($tasks) === 1 ? 'task' : 'tasks',
            )
        );

        return self::SUCCESS;
    }
}
