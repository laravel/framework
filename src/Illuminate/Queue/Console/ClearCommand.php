<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Queue\ClearableQueue;
use ReflectionClass;

class ClearCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:clear
                       {queues? : The names of the queues to clear} {--force : Force the operation to run when in production}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'queue:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all of the jobs from the specified queues';

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

        $this->parseQueues($this->argument('queues'))
            ->each(function ($item) {
                $queue = ($this->laravel['queue'])->connection($item['connection']);

                if ($queue instanceof ClearableQueue) {
                    $count = $queue->clear($item['queue']);

                    $this->line('<info>Cleared '.$count.' jobs from the ['.$item['queue'].'] queue</info> ');
                } else {
                    $this->line('<error>Clearing queues is not supported on ['.(new ReflectionClass($queue))->getShortName().']</error> ');
                }
            });

        return 0;
    }

    /**
     * Parse the queues into an array of the connections and queues.
     *
     * @param  string  $queues
     * @return \Illuminate\Support\Collection
     */
    protected function parseQueues($queues)
    {
        return collect(explode(',', $queues))->map(function ($queue) {
            [$connection, $queue] = array_pad(explode(':', $queue, 2), 2, null);

            if (! isset($queue)) {
                $queue = $connection ?: $this->getQueue($connection);
                $connection = $this->laravel['config']['queue.default'];
            }

            return [
                'connection' => $connection,
                'queue' => $queue,
            ];
        });
    }

    /**
     * Get the queue name to clear.
     *
     * @param  string  $connection
     *
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->laravel['config']->get("queue.connections.{$connection}.queue", 'default');
    }
}
