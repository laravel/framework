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
     * The command that has been unserialized
     * This is a cache for the deleted and failed callback.
     * Set the first time the command method is called
     *
     * @var mixed
     */
    private $jobCommand;

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
     * Unserialize the command
     *
     * @param $data
     * @return Job
     */
    protected function command($data)
    {
        if ($this->jobCommand) {
            return $this->jobCommand;
        }

        return $this->jobCommand = unserialize($data['command']);
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
            $job, $this->command($data)
        );

        $this->dispatcher->dispatchNow(
            $command, $handler = $this->resolveHandler($job, $command)
        );

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Resolve the handler for the given command.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  mixed  $command
     * @return mixed
     */
    protected function resolveHandler($job, $command)
    {
        $handler = $this->dispatcher->getCommandHandler($command) ?: null;

        if ($handler) {
            $this->setJobInstanceIfNecessary($job, $handler);
        }

        return $handler;
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
        if (in_array(InteractsWithQueue::class, class_uses_recursive(get_class($instance)))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * The exception that caused the failure will be passed.
     *
     * @param  array      $data
     * @param  \Exception $e
     * @return void
     */
    public function failed(array $data, $e)
    {
        $command = $this->command($data);

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }

    /**
     * Call the deleted method on the job instance.
     *
     * Run EVERY time when the job is deleted from queue.
     *
     * This isn't called when the job is released in the queue, only when it's leaving it.
     * Also called after a failure before the failed method.
     *
     * @param  array $data
     * @return void
     */
    public function deleted(array $data)
    {
        $command = $this->command($data);

        if (method_exists($command, 'deleted')) {
            $command->deleted();
        }
    }
}
