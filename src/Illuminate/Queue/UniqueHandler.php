<?php

namespace Illuminate\Queue;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

/**
 * A helper class to manage the unique ID and cache instance for a job
 * base on the data of the job itself.
 */
class UniqueHandler
{
    /**
     * Original job name.
     *
     * @var string
     */
    public $jobName;

    /**
     * The unique ID for the job.
     *
     * @var string|null
     */
    public $uniqueId = null;

    /**
     * Cache connection name for the job.
     *
     * @var string|null
     */
    protected $uniqueVia = null;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new handler instance.
     *
     * @param  object  $job
     */
    public function __construct(object $job)
    {
        $this->jobName = get_class($job);

        if (method_exists($job, 'uniqueId')) {
            $this->uniqueId = $job->uniqueId();
        } elseif (isset($job->uniqueId)) {
            $this->uniqueId = $job->uniqueId;
        }

        if (method_exists($job, 'uniqueVia')) {
            $this->uniqueVia = $job->uniqueVia()->getName();
        }
    }

    /**
     * Creates a new instance if the job should be unique.
     *
     * @param  object  $job
     * @return \Illuminate\Queue\UniqueHandler|null
     */
    public static function forJob(object $job)
    {
        if (
            $job instanceof ShouldBeUnique ||
            $job instanceof ShouldBeUniqueUntilProcessing
        ) {
            return new static($job);
        }

        return null;
    }

    /**
     * Sets the container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return \Illuminate\Queue\UpdateHandler
     */
    public function withContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Returns the cache instance for the job.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function getCacheStore()
    {
        return $this->container->make(CacheFactory::class)
                               ->store($this->uniqueVia);
    }

    /**
     * Releases the lock for the job.
     *
     * @return void
     */
    public function release()
    {
        (new UniqueLock($this->getCacheStore()))->release($this);
    }
}
