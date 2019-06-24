<?php

namespace Illuminate\Foundation\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheContract;

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
     * @param CacheContract $cache
     * @return int
     */
    public function handle(CacheContract $cache)
    {
        try {
            if (! $cache->has('framework_down')) {
                $this->comment('Application is already up.');

                return true;
            }

            $cache->forget('framework_down');

            $this->info('Application is now live.');
        } catch (Exception $e) {
            $this->error('Failed to disable maintenance mode.');

            $this->error($e->getMessage());

            return 1;
        }
    }
}
