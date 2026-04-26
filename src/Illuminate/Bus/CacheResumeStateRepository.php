<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\Job;

class CacheResumeStateRepository implements ResumeStateRepository
{
    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store = null;

    /**
     * @param  Factory  $cache  The cache factory instance
     */
    public function __construct(
        protected Factory $cache,
    ) {
    }

    protected function getCache(): Repository
    {
        return $this->cache->store($this->store);
    }

    protected function determineCacheKey(Job $job): string
    {
        // @todo: this needs to receive the command, since that's where the job key will be saved
        return 'workflow:'.$job->getJobId();
    }

    #[\Override]
    public function getResumeState(Job $job): array
    {
        return (array) $this->getCache()->get($this->determineCacheKey($job));
    }

    #[\Override]
    public function saveCheckpoint($job, $checkpoint, $data): void
    {
        // TODO: Implement saveCheckpoint() method.
    }

    #[\Override]
    public function clearResumeState($job): void
    {
        $this->getCache()->forget($this->determineCacheKey($job));
    }
}
