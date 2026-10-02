<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Queue\Console\Concerns\ParsesQueue;
use Illuminate\Queue\Worker;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:pause')]
class PauseCommand extends Command
{
    use ParsesQueue;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:pause
                            {queue? : The name of the queue to pause}
                            {--all : Pause job processing for all queues on all connections}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause job processing for a specific queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(QueueManager $manager): int
    {
        if (! Worker::$pausable) {
            $this->components->error('Queue pausing is currently disabled.');

            return self::FAILURE;
        }

        if ($this->option('all')) {
            $manager->pauseAll();

            $this->components->info('Job processing on all queues across all connections has been paused.');

            return self::SUCCESS;
        }

        if (! $this->argument('queue')) {
            $this->components->error('A queue name is required unless the --all option is used.');

            return self::FAILURE;
        }

        [$connection, $queue] = $this->parseQueue($this->argument('queue'));

        $manager->pause($connection, $queue);

        $this->components->info("Job processing on queue [{$connection}:{$queue}] has been paused.");

        return self::SUCCESS;
    }
}
