<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Bus\Dispatcher;

class CallQueuedHandler
{
    /**
     * The bus dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * Create a new handler instance.
     *
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $dispatcher
     * @return void
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function call(Job $job, array $data)
    {
        $command = $this->setJobInstanceIfNecessary(
            $job, unserialize($data['command'])
        );

        $this->dispatcher->dispatchNow($command, function ($handler) use ($job) {
            $this->setJobInstanceIfNecessary($job, $handler);
        });

        if (!$job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        if (in_array('Illuminate\Queue\InteractsWithQueue', class_uses_recursive(get_class($instance)))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * @param  array  $data
     * @return void
     */
    public function failed(array $data)
    {
        $handler = $this->dispatcher->resolveHandler($command = unserialize($data['command']));

        if (method_exists($handler, 'failed')) {
            call_user_func([$handler, 'failed'], $command);
        }
    }
}
