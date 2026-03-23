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
    protected $signature = 'queue:status 
                {queue : The name of the queue to check}
                {--json : Output the queue status as JSON}';

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

        $isPaused = $manager->isPaused($connection, $queue);

        if ($this->option('json')) {
            $this->displayJson($connection, $queue, $isPaused);
        } else {
            $this->displayForCli($connection, $queue, $isPaused);
        }

        return $isPaused ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Render the queue status for the CLI.
     */
    protected function displayForCli(string $connection, string $queue, bool $isPaused): void
    {
        if ($isPaused) {
            $this->components->warn("Queue [{$connection}:{$queue}] is currently paused.");
        } else {
            $this->components->info("Queue [{$connection}:{$queue}] is currently running.");
        }
    }

    /**
     * Render the queue status as JSON.
     */
    protected function displayJson(string $connection, string $queue, bool $isPaused): void
    {
        $this->output->writeln(json_encode([
            'connection' => $connection,
            'queue' => $queue,
            'status' => $isPaused ? 'paused' : 'running',
        ]));
    }
}
