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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'queue:clear')]
class ClearCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:clear';

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

        $count = 0;

        $queues = collect(explode(',', $queueName))
            ->map(fn ($queue) => trim($queue))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($queues as $name) {
            $count += $queue->clear($name);
        }

        $this->components->info(
            sprintf('Cleared %s %s from the [%s] %s', $count, Str::plural('job', $count), implode(', ', $queues), (new Stringable('queue'))->plural($queues))
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

    /**
     *  Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of the queue connection to clear'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, 'The names of the queues to clear'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
