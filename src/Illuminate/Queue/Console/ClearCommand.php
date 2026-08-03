<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:clear')]
class ClearCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:clear
                    {connection? : The name of the queue connection to clear}
                    {--queue= : The names of the queues to clear}
                    {--force : Force the operation to run when in production}';

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
        if (
            $this->isProhibited() ||
            ! $this->confirmToProceed()
        ) {
            return self::FAILURE;
        }

        $connection = $this->argument('connection')
            ?: $this->laravel['config']['queue.default'];

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queueName = $this->getQueue($connection);

        $queue = $this->laravel['queue']->connection($connection);

        if (! $queue instanceof ClearableQueue) {
            $this->components->error('Clearing queues is not supported on ['.(new ReflectionClass($queue))->getShortName().']');

            return self::FAILURE;
        }

        $queues = (new Stringable($queueName))->explode(',')
            ->map(fn ($queue) => trim($queue))
            ->filter()
            ->unique();

        $count = $queues->reduce(fn ($carry, $name) => $carry + $queue->clear($name), 0);

        $this->components->info(
            sprintf('Cleared %s %s from the [%s] %s', $count, Str::plural('job', $count), $queues->implode(', '), Str::plural('queue', $queues->count()))
        );

        return self::SUCCESS;
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
            "queue.connections.{$connection}.queue",
            'default'
        );
    }
}
