<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Concerns\CreatesApplication;
use RuntimeException;

class AsyncQueueFake extends QueueFake
{
    use CreatesApplication;

    /**
     * Process all of the jobs on the queue using a fresh application instance.
     *
     * @return self
     */
    public function dispatch()
    {
        foreach ($this->jobs as $job => $instances) {
            foreach ($instances as $instance) {
                $this->refreshApplication();

                [$job, $data, $queue] = array_values($instance);

                is_object($job) && isset($job->connection)
                    ? $this->queue->connection($job->connection)->push($job, $data, $queue)
                    : $this->queue->push($job, $data, $queue);
            }
        }

        return $this;
    }

    /**
     * Refresh the application.
     *
     * @return \Illuminate\Foundation\Application
     *
     * @throws RuntimeException
     */
    protected function refreshApplication()
    {
        $db = app('db');
        $queue = app('queue');

        $app = $this->createApplication();

        Queue::swap($queue);
        DB::swap($db);
        Model::setConnectionResolver($db);

        return $app;
    }
}
