<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'queue:peek')]
class PeekCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:peek
                            {connection? : The name of the connection}
                            {--queue= : The name of the queue to inspect}
                            {--state=pending : The state of the jobs to inspect (pending, delayed, reserved)}
                            {--limit=25 : The maximum number of jobs to display}
                            {--json : Output the jobs as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect the jobs on a given queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $state = $this->option('state');

        if (! in_array($state, ['pending', 'delayed', 'reserved'])) {
            $this->components->error('The state must be one of: pending, delayed, reserved.');

            return self::FAILURE;
        }

        $connection = $this->argument('connection')
            ?: $this->laravel['config']['queue.default'];

        $queue = $this->laravel['queue']->connection($connection);

        if (! method_exists($queue, $state.'Jobs')) {
            $this->components->error("The [{$connection}] connection does not support inspecting jobs.");

            return self::FAILURE;
        }

        $queueName = $this->getQueue($connection);

        $jobs = match ($state) {
            'pending' => $queue->pendingJobs($queueName),
            'delayed' => $queue->delayedJobs($queueName),
            'reserved' => $queue->reservedJobs($queueName),
        };

        $jobs = $jobs->take((int) $this->option('limit'))->values();

        if ($this->option('json')) {
            $this->displayJobsAsJson($jobs);

            return self::SUCCESS;
        }

        if ($jobs->isEmpty()) {
            $this->components->info('No '.$state.' jobs found on the ['.$queueName.'] queue.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->displayJobs($jobs);
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Get the queue name to inspect.
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

    /**
     * Display the jobs in the console.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>  $jobs
     * @return void
     */
    protected function displayJobs(Collection $jobs)
    {
        $jobs->each(
            fn ($job) => $this->components->twoColumnDetail(
                sprintf('<fg=gray>%s</> %s', $job->uuid, $job->name),
                sprintf('<fg=gray>%s</> %s', $job->attempts.' '.Str::plural('attempt', $job->attempts), $job->createdAt?->diffForHumans() ?? '')
            )
        );
    }

    /**
     * Display the jobs as JSON.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>  $jobs
     * @return void
     */
    protected function displayJobsAsJson(Collection $jobs)
    {
        $this->output->writeln($jobs->map(fn ($job) => [
            'uuid' => $job->uuid,
            'queue' => $job->queue,
            'name' => $job->name,
            'attempts' => $job->attempts,
            'created_at' => $job->createdAt?->toIso8601String(),
        ])->toJson());
    }
}
