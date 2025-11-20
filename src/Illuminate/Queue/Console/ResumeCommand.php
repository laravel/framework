<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:resume')]
class ResumeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:resume {queue : The name of the queue to resume (connection:queue format, e.g., redis:default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resume processing for a paused queue';

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $manager;

    /**
     * Create a new queue resume command.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $manager
     */
    public function __construct(QueueManager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        [$connection, $queue] = $this->parseQueue($this->argument('queue'));

        if (! $this->manager->isPaused($connection, $queue)) {
            $this->components->warn("Queue [{$connection}:{$queue}] is not paused.");

            return 1;
        }

        $this->manager->resume($connection, $queue);

        $this->components->info("Queue [{$connection}:{$queue}] has been resumed.");

        return 0;
    }

    /**
     * Parse the queue argument into connection and queue name.
     *
     * @param  string  $queue
     * @return array
     */
    protected function parseQueue($queue)
    {
        [$connection, $queue] = array_pad(explode(':', $queue, 2), 2, null);

        if (! isset($queue)) {
            $queue = $connection;
            $connection = $this->laravel['config']['queue.default'];
        }

        return [$connection, $queue];
    }
}
