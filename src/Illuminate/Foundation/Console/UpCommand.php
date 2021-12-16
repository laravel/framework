<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\MaintenanceMode;

class UpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'up';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bring the application out of maintenance mode';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Foundation\MaintenanceMode $maintenanceMode
     *
     * @return int
     */
    public function handle(MaintenanceMode $maintenanceMode)
    {
        try {
            if ($maintenanceMode->isUp()) {
                $this->comment('Application is already up.');

                return 0;
            }

            $maintenanceMode->up();

            if (is_file(storage_path('framework/maintenance.php'))) {
                unlink(storage_path('framework/maintenance.php'));
            }

            $this->laravel->get('events')->dispatch(MaintenanceModeDisabled::class);

            $this->info('Application is now live.');
        } catch (Exception $e) {
            $this->error('Failed to disable maintenance mode.');

            $this->error($e->getMessage());

            return 1;
        }
    }
}
