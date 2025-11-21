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
    protected $signature = 'queue:pause {queue : The name of the queue to pause}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause job processing for a specific queue';

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $manager;

    /**
     * Create a new queue pause command.
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

        $this->manager->pause($connection, $queue);

        $this->components->info("Job processing on queue [{$connection}:{$queue}] has been paused.");

        return 0;
    }

    /**
     * Parse the queue argument into the connection and queue name.
     *
     * @param  string  $queue
     * @return array
     */
    protected function parseQueue($queue)
    {
        [$connection, $queue] = array_pad(explode(':', $queue, 2), 2, null);

        return isset($queue)
            ? [$connection, $queue]
            : [$this->laravel['config']['queue.default'], $connection];
    }
}
