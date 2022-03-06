<?php

namespace Illuminate\Queue;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pipeline\Pipeline;
use ReflectionClass;
use RuntimeException;

class CallQueuedHandler
{
    /**
     * The bus dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new handler instance.
     *
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $dispatcher
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Dispatcher $dispatcher, Container $container)
    {
        $this->container = $container;
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
        try {
            $command = $this->setJobInstanceIfNecessary(
                $job, $this->getCommand($data)
            );
        } catch (ModelNotFoundException $e) {
            return $this->handleModelNotFound($job, $e);
        }

        if ($command instanceof ShouldBeUniqueUntilProcessing) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        $this->dispatchThroughMiddleware($job, $command);

        if (! $job->isReleased() && ! $command instanceof ShouldBeUniqueUntilProcessing) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        if (! $job->hasFailed() && ! $job->isReleased()) {
            $this->ensureNextJobInChainIsDispatched($command);
            $this->ensureSuccessfulBatchJobIsRecorded($command);
        }

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Get the command from the given payload.
     *
     * @param  array  $data
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function getCommand(array $data)
    {
        if (str_starts_with($data['command'], 'O:')) {
            return unserialize($data['command']);
        }

        if ($this->container->bound(Encrypter::class)) {
            return unserialize($this->container[Encrypter::class]->decrypt($data['command']));
        }

        throw new RuntimeException('Unable to extract job payload.');
    }

    /**
     * Dispatch the given job / command through its specified middleware.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  mixed  $command
     * @return mixed
     */
    protected function dispatchThroughMiddleware(Job $job, $command)
    {
        return (new Pipeline($this->container))->send($command)
                ->through(array_merge(method_exists($command, 'middleware') ? $command->middleware() : [], $command->middleware ?? []))
                ->then(function ($command) use ($job) {
                    return $this->dispatcher->dispatchNow(
                        $command, $this->resolveHandler($job, $command)
                    );
                });
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
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Ensure the next job in the chain is dispatched if applicable.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureNextJobInChainIsDispatched($command)
    {
        if (method_exists($command, 'dispatchNextJobInChain')) {
            $command->dispatchNextJobInChain();
        }
    }

    /**
     * Ensure the batch is notified of the successful job completion.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureSuccessfulBatchJobIsRecorded($command)
    {
        $uses = class_uses_recursive($command);

        if (! in_array(Batchable::class, $uses) ||
            ! in_array(InteractsWithQueue::class, $uses)) {
            return;
        }

        if ($batch = $command->batch()) {
            $batch->recordSuccessfulJob($command->job->uuid());
        }
    }

    /**
     * Ensure the lock for a unique job is released.
     *
     * @param  mixed  $command
     * @return void
     */
    protected function ensureUniqueJobLockIsReleased($command)
    {
        if (! $command instanceof ShouldBeUnique) {
            return;
        }

        $uniqueId = method_exists($command, 'uniqueId')
                    ? $command->uniqueId()
                    : ($command->uniqueId ?? '');

        $cache = method_exists($command, 'uniqueVia')
                    ? $command->uniqueVia()
                    : $this->container->make(Cache::class);

        $cache->lock(
            'laravel_unique_job:'.get_class($command).$uniqueId
        )->forceRelease();
    }

    /**
     * Handle a model not found exception.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Throwable  $e
     * @return void
     */
    protected function handleModelNotFound(Job $job, $e)
    {
        $class = $job->resolveName();

        try {
            $shouldDelete = (new ReflectionClass($class))
                    ->getDefaultProperties()['deleteWhenMissingModels'] ?? false;
        } catch (Exception $e) {
            $shouldDelete = false;
        }

        if ($shouldDelete) {
            return $job->delete();
        }

        return $job->fail($e);
    }

    /**
     * Call the failed method on the job instance.
     *
     * The exception that caused the failure will be passed.
     *
     * @param  array  $data
     * @param  \Throwable|null  $e
     * @param  string  $uuid
     * @return void
     */
    public function failed(array $data, $e, string $uuid)
    {
        $command = $this->getCommand($data);

        if (! $command instanceof ShouldBeUniqueUntilProcessing) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        $this->ensureFailedBatchJobIsRecorded($uuid, $command, $e);
        $this->ensureChainCatchCallbacksAreInvoked($uuid, $command, $e);

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }

    /**
     * Ensure the batch is notified of the failed job.
     *
     * @param  string  $uuid
     * @param  mixed  $command
     * @param  \Throwable  $e
     * @return void
     */
    protected function ensureFailedBatchJobIsRecorded(string $uuid, $command, $e)
    {
        if (! in_array(Batchable::class, class_uses_recursive($command))) {
            return;
        }

        if ($batch = $command->batch()) {
            $batch->recordFailedJob($uuid, $e);
        }
    }

    /**
     * Ensure the chained job catch callbacks are invoked.
     *
     * @param  string  $uuid
     * @param  mixed  $command
     * @param  \Throwable  $e
     * @return void
     */
    protected function ensureChainCatchCallbacksAreInvoked(string $uuid, $command, $e)
    {
        if (method_exists($command, 'invokeChainCatchCallbacks')) {
            $command->invokeChainCatchCallbacks($e);
        }
    }
}
