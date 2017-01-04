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
        $ids = $this->argument('id');

        if (count($ids) === 1 && $ids[0] === 'all') {
            $ids = Arr::pluck($this->laravel['queue.failer']->all(), 'id');
        }

        foreach ($ids as $id) {
            $this->retryJob($id);
        }
    }

    /**
     * Retry the queue job with the given ID.
     *
     * @param  string  $id
     * @return void
     */
    protected function retryJob($id)
    {
        $failed = $this->laravel['queue.failer']->find($id);

        if (! is_null($failed)) {
            $failed = (object) $failed;

            $failed->payload = $this->resetAttempts($failed->payload);

            $this->laravel['queue']->connection($failed->connection)
                                ->pushRaw($failed->payload, $failed->queue);

            $this->laravel['queue.failer']->forget($failed->id);

            $this->info("The failed job [{$id}] has been pushed back onto the queue!");
        } else {
            $this->error("No failed job matches the given ID [{$id}].");
        }
    }

    /**
     * Reset the payload attempts.
     *
     * @param  string  $payload
     * @return string
     */
    protected function resetAttempts($payload)
    {
        $payload = json_decode($payload, true);

        if (isset($payload['attempts'])) {
            $payload['attempts'] = 1;
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
