<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\ScheduleManifest;

class ScheduleClearDiscoveryCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:clear-discovery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the cached scheduled task discovery manifest';

    /**
     * Execute the console command.
     *
     * @param  ScheduleManifest  $manifest
     * @return int
     */
    public function handle(ScheduleManifest $manifest): int
    {
        $manifest->clear();

        $this->components->info(
            'Cached scheduled task discovery cleared successfully.'
        );

        return self::SUCCESS;
    }
}
