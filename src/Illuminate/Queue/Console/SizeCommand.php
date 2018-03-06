<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\QueueManager;

class SizeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:size
                            {connection? : The name of the queue connection=}
                            {--queue= : The name of the queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get size of a queue';

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $manager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(QueueManager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $connection = $this->argument('connection') ?: $this->laravel['config']['queue.default'];
        $queue = $this->getQueue($connection);

        $this->info(
            $this->manager->connection($connection)->size($queue)
        );
    }

    /**
     * Get the queue name for the worker.
     *
     * @param  string $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->option('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }
}
