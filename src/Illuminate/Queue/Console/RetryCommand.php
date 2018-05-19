<?php

namespace Illuminate\Queue\Console;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;

class RetryCommand extends Command
{
    /**
     * The console command static part of its signature.
     *
     * @var string
     */
    protected $staticSignature = "queue:retry";

     /**
     * The console command dynamic part of its signature.
     *
     * @var string
     */
    protected $dynamicParameters = ['id' => "The ID of the failed job or 'all' to retry all jobs.",
                                    'queue' => 'retry only the jobs that were in this queue.',
                                    'id_from' => 'retry jobs that have and ID greater than or equals to id_from.',
                                    'id_to' => 'retry jobs that have and ID greater than or equals to id_to.',
                                    'failed_at_from' => 'retry jobs that have failed after this date.',
                                    'failed_at_to' => 'retry jobs that have failed before this date.',
                                    ];

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
        if ($this->filtrationOption()) {
            $ids = Arr::pluck($this->laravel['queue.failer']->filter($this->filtrationOption())->get(), 'id');
        } else {
            $ids = (array) $this->option('id');
            if (count($ids) === 1 && $ids[0] === 'all') {
                $ids = Arr::pluck($this->laravel['queue.failer']->all(), 'id');
            }
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

    /**
     * get the filtration options.
     *
     * @return array
     */
    protected function filtrationOption()
    {
        $options = $this->options();
        $options = array_filter($options, function ($option) {
            return (bool) $option;
        });

        return array_intersect_key($options, array_flip($this->laravel['queue.failer']->getFiltrationOptions()));
    }
}
