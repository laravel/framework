<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:pause')]
class PauseCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:pause {queue : The name of the queue to pause (connection:queue format, e.g., redis:default)}
                                       {--ttl= : The TTL for the pause in seconds (omit for indefinite pause)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause processing for a specific queue';

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $manager;

    /**
     * Create a new queue pause command.
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

        $ttl = $this->option('ttl') !== null ? (int) $this->option('ttl') : null;

        $this->manager->pause($connection, $queue, $ttl);

        $this->components->info("Queue [{$connection}:{$queue}] has been paused".($ttl ? " for {$ttl} seconds." : ' indefinitely.'));

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
