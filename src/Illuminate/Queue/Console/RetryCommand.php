<?php

namespace Illuminate\Queue\Console;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;

class RetryCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:retry-limited 
                            {id* : The ID of the failed job or "all" to retry all jobs}
                            {--limit=0 : The number of jobs to retry}
                            {--offset=0 : Offset}
                            {--queue= : The names of the queues to work}
                            {--order=asc : Order of jobs to execute}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry a failed queue job';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->getJobIds() as $id) {
            $job = $this->laravel['queue.failer']->find($id);

            if (is_null($job)) {
                $this->error("Unable to find failed job with ID [{$id}].");
            } else {
                $this->retryJob($job);

                $this->info("The failed job [{$id}] has been pushed back onto the queue!");

                $this->laravel['queue.failer']->forget($id);
            }
        }
    }

    /**
     * Get the job IDs to be retried.
     *
     * @return array
     */
    protected function getJobIds()
    {
        $limit = $this->option('limit');
        $offset = $this->option('offset');
        $queue = $this->option('queue');
        $order = $this->option('order');

        $ids = (array) $this->argument('id');

        if (count($ids) === 1 && $ids[0] === 'all') {
            $query = $this->laravel['queue.failer2']->getQuery()->orderBy('id', 'desc');


            if ($limit != 0)
                $query = $query->limit($limit);

            if ($offset != 0)
                $query = $query->offset($offset);

            if ($queue)
                $query= $query->where('queue', $queue);

            $query = $query->orderBy('id', $order);


            $data = $query->get('id');

            $ids = Arr::pluck($data, 'id');
        }

        return $ids;
    }

    /**
     * Retry the queue job.
     *
     * @param  \stdClass  $job
     * @return void
     */
    protected function retryJob($job)
    {
        $this->laravel['queue']->connection($job->connection)->pushRaw(
            $this->resetAttempts($job->payload), $job->queue
        );
    }

    /**
     * Reset the payload attempts.
     *
     * Applicable to Redis jobs which store attempts in their payload.
     *
     * @param  string  $payload
     * @return string
     */
    protected function resetAttempts($payload)
    {
        $payload = json_decode($payload, true);

        if (isset($payload['attempts'])) {
            $payload['attempts'] = 0;
        }

        return json_encode($payload);
    }
}
