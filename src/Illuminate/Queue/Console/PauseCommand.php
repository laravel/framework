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
    protected $signature = 'queue:pause {queue : The name of the queue to pause}
                                       {--ttl=86400 : The TTL for the pause in seconds}';

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
        $queue = $this->argument('queue');
        $ttl = (int) $this->option('ttl');

        $this->manager->pause($queue, $ttl);

        $this->components->info("Queue [{$queue}] has been paused.");

        return 0;
    }
}
