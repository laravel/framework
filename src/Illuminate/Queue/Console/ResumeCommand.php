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
    protected $signature = 'queue:resume {queue : The name of the queue to resume}';

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
        $queue = $this->argument('queue');

        if (! $this->manager->isPaused($queue)) {
            $this->components->warn("Queue [{$queue}] is not paused.");

            return 1;
        }

        $this->manager->resume($queue);

        $this->components->info("Queue [{$queue}] has been resumed.");

        return 0;
    }
}
