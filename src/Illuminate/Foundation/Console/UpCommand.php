<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;

class UpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bring the application out of maintenance mode';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (! file_exists(storage_path('framework/down'))) {
                $this->comment('Application is already up.');

                return true;
            }
            unlink(storage_path('framework/down'));
            $this->info('Application is now live.');
        } catch (Exception $e) {
            $this->error('Application is failed to up.');
            $this->error($e->getMessage());

            return false;
        }
    }
}
