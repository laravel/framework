<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class CachedSchedule
{
    /**
     * Create a cached schedule loader.
     *
     * @param  ScheduleDiscoverer  $discoverer
     * @param  ScheduleManifest  $manifest
     * @param  ScheduleRegistrar  $registrar
     */
    public function __construct(
        protected ScheduleDiscoverer $discoverer,
        protected ScheduleManifest $manifest,
        protected ScheduleRegistrar $registrar,
    ) {
        //
    }

    /**
     * Register cached or discovered tasks.
     *
     * @param  Schedule  $schedule
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     *
     * @throws FileNotFoundException
     */
    public function register(
        Schedule $schedule,
        string $path,
        string $namespace
    ): void {
        $tasks = $this->manifest->exists()
            ? $this->manifest->load()
            : $this->discoverer->discover($path, $namespace);

        $this->registrar->register(
            $schedule,
            $tasks,
        );
    }
}
