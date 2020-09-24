<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Queue\DeletableQueue;
use ReflectionClass;

class DeletePendingCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:delete {id : The ID of the pending job}
                            {--connection= : The name of the queue connection to clear}
                            {--queue= : The name of the queue to clear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a pending job from the queue';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $connection = $this->option('connection')
                        ?: $this->laravel['config']['queue.default'];

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queueName = $this->getQueue($connection);

        $queue = ($this->laravel['queue'])->connection($connection);

        if ($queue instanceof DeletableQueue) {
            if($queue->deletePending($queueName, $this->argument('id'))) {
                $this->info('Pending job deleted successfully!');
            } else {
                $this->error('No pending job matches the given ID.');
            }
        } else {
            $this->error('Clearing queues is not supported on ['.(new ReflectionClass($queue))->getShortName().']');
        }

        return 0;
    }

    /**
     * Get the queue name to clear.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->option('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }
}
