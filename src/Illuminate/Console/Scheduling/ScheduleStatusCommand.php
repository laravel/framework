<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:status')]
class ScheduleStatusCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:status 
                {--json : Output the scheduler status as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the status of the scheduler';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Cache $cache)
    {
        $isPaused = $cache->get('illuminate:schedule:paused', false);

        if ($this->option('json')) {
            $this->displayJson($isPaused);
        } else {
            $this->displayForCli($isPaused);
        }

        return $isPaused ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Render the queue status for the CLI.
     */
    protected function displayForCli(bool $isPaused): void
    {
        if ($isPaused) {
            $this->components->warn('Scheduler is currently paused.');
        } else {
            $this->components->info('Scheduler is currently running.');
        }        
    }

    /**
     * Render the queue status as JSON.
     */
    protected function displayJson(bool $isPaused): void
    {
        $this->output->writeln(json_encode([
            'status' => $isPaused ? 'paused' : 'running',
        ]));
    }
}
