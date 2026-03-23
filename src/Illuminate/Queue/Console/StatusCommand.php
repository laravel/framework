<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Queue\Console\Concerns\ParsesQueue;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:status')]
class StatusCommand extends Command
{
    use ParsesQueue;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:status {queue : The name of the queue to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the status of a specific queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(QueueManager $manager)
    {
        [$connection, $queue] = $this->parseQueue($this->argument('queue'));

        if ($manager->isPaused($connection, $queue)) {
            $this->components->warn("Queue [{$connection}:{$queue}] is currently paused.");

            return self::FAILURE;
        }

        $this->components->info("Queue [{$connection}:{$queue}] is currently running.");

        return self::SUCCESS;
    }
}
