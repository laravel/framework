<?php

namespace Illuminate\Queue\Console;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class RetryCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:retry';

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
    public function fire()
    {
        foreach ($this->getJobIds() as $id) {
            $this->retryJob($id);

            $this->info("The failed job [{$id}] has been pushed back onto the queue!");

            $this->laravel['queue.failer']->forget($id);
        }
    }

    /**
     * Get the job IDs to be retried.
     *
     * @return array
     */
    protected function getJobIds()
    {
        $ids = $this->argument('id');

        if (count($ids) === 1 && $ids[0] === 'all') {
            $ids = Arr::pluck($this->laravel['queue.failer']->all(), 'id');
        }

        return $ids;
    }

    /**
     * Retry the queue job with the given ID.
     *
     * @param  string  $id
     * @return void
     */
    protected function retryJob($id)
    {
        if (is_null($failed = $this->laravel['queue.failer']->find($id))) {
            return $this->error("No failed job matches the given ID [{$id}].");
        }

        $failed = (object) $failed;

        $this->laravel['queue']->connection($failed->connection)->pushRaw(
            $this->resetAttempts($failed->payload), $failed->queue
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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['id', InputArgument::IS_ARRAY, 'The ID of the failed job'],
        ];
    }
}
