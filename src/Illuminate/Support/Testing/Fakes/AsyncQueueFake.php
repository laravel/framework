<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

class AsyncQueueFake extends QueueFake
{
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
        if (file_exists(getcwd().'/bootstrap/app.php')) {
            $db = app('db');
            $queue = app('queue');

            $app = require getcwd().'/bootstrap/app.php'
            $app->make(Kernel::class)->bootstrap();

            Queue::swap($queue);
            DB::swap($db);
            Model::setConnectionResolver($db);

            return $app;
        }

        throw new RuntimeException('Unable to resolve application.');
    }
}
