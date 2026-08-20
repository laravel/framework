<?php

namespace Illuminate\Concurrency;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Laravel\SerializableClosure\SerializableClosure;
use ReflectionFunction;
use RuntimeException;
use Throwable;

class InvokeQueuedClosure implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Indicates if the job should fail when its timeout is exceeded.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * The number of seconds the job may run before timing out.
     *
     * @var int|null
     */
    public $timeout;

    public function __construct(
        public string $resultKey,
        public string $cancellationKey,
        public ?string $store,
        public int $ttl,
        public int $deadline,
        public bool $rethrowFailures,
        public SerializableClosure $task,
    ) {
        $this->afterCommit = false;
    }

    /**
     * Execute the job.
     */
    public function handle(ContainerContract $container, CacheFactory $cache): void
    {
        $repository = $cache->store($this->store);

        if ($repository->get($this->cancellationKey) ||
            Carbon::now()->getTimestamp() > $this->deadline) {
            return;
        }

        $failure = null;

        try {
            $result = TaskResult::success($container->call($this->task->getClosure()));
        } catch (Throwable $failure) {
            if (! $this->rethrowFailures) {
                report($failure);
            }

            $result = TaskResult::failure($failure);
        }

        $this->storeResult($repository, $result);

        if ($failure && $this->rethrowFailures) {
            throw new CapturedTaskException($failure);
        }
    }

    /**
     * Handle a failure of the queued task itself.
     */
    public function failed(?Throwable $e): void
    {
        if ($e instanceof CapturedTaskException) {
            return;
        }

        $this->storeResult(
            Container::getInstance()->make(CacheFactory::class)->store($this->store),
            TaskResult::failure($e ?? new RuntimeException('The concurrency task failed without an exception.')),
        );
    }

    /**
     * Store the given result envelope, discarding exception parameters that cannot be serialized.
     */
    protected function storeResult($repository, array $result): void
    {
        try {
            $repository->add($this->resultKey, $result, $this->ttl);
        } catch (Throwable) {
            $repository->add($this->resultKey, array_merge($result, ['parameters' => []]), $this->ttl);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        $reflection = new ReflectionFunction($this->task->getClosure());

        return 'Concurrency task ('.basename($reflection->getFileName()).':'.$reflection->getStartLine().')';
    }
}
