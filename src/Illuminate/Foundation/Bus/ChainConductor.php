<?php

namespace Illuminate\Foundation\Bus;

use Ramsey\Uuid\Uuid;
use Illuminate\Bus\ChainLink;
use Illuminate\Support\Collection;
use Illuminate\Database\DatabaseManager;

class ChainConductor
{
    /**
     * The database manager instance.
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * Create a chain conductor instance.
     *
     * @param \Illuminate\Database\DatabaseManager  $db
     */
    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new chain with the given chain of jobs.
     *
     * The chain should have a nested collection for each group of parallel jobs.
     *
     * @param  \Illuminate\Support\Collection  $chain
     * @return void
     */
    public function createChain(Collection $chain)
    {
        $chain = $chain->map->keyBy(function () {
            return Uuid::uuid4()->toString();
        });

        $this->populateChainLinks($chain);

        $this->saveChain($chain);
    }

    /**
     * Handle a job that has successfully completed execution.
     *
     * @param  object  $job
     * @return void
     */
    public function jobCompleted($job)
    {
        if (empty($job->chain)) {
            return;
        }

        $this->query()->delete($job->chain->jobId);

        if ($this->shouldDispatchNextJobs($job)) {
            $this->dispatchNextJobs($job);
        }
    }

    /**
     * Handle a job that has failed to successfully execute.
     *
     * @param  object  $job
     * @return void
     */
    public function jobFailed($job)
    {
        if (! empty($job->chain)) {
            $this->query()->where('chain_id', $job->chain->chainId)->delete();
        }
    }

    /**
     * Populate the "chain" property on the jobs in the chain.
     *
     * @param  \Illuminate\Support\Collection  $chain
     * @return void
     */
    protected function populateChainLinks(Collection $chain)
    {
        $chainId = Uuid::uuid4()->toString();

        // This creates the actual chain. Each job's "chain" property
        // is set to an instance of ChainLink, with information on
        // the current jobs and the immediately following jobs.
        $chain->sliding(2)->eachSpread(function ($current, $next) use ($chainId) {
            foreach ($current as $id => $job) {
                $job->chain = $this->createChainLink(
                    $id, $chainId, $current, $next
                );
            }
        });

        // The last link in the chain doesn't need all the information
        // that the other links need - but it still has to know its
        // id, as well as the chain id, to delete all when done.
        $chain->last()->each(function ($job, $id) use ($chainId) {
            $job->chain = new ChainLink($id, $chainId);
        });
    }

    /**
     * Create an instance of a chain link.
     *
     * @param  int  $id
     * @param  string  $chainId
     * @param  \Illuminate\Support\Collection  $current
     * @param  \Illuminate\Support\Collection  $next
     * @return \Illuminate\Bus\ChainLink
     */
    protected function createChainLink($id, $chainId, $current, $next)
    {
        return (new ChainLink($id, $chainId))
                ->current($current->keys()->all())
                ->next($next->keys()->all());
    }

    /**
     * Save the given chain of jobs to the DB.
     *
     * @param  \Illuminate\Support\Collection  $chain
     * @return void
     */
    protected function saveChain(Collection $chain)
    {
        $this->query()->insert($chain->collapse()->map(function ($job) {
            return [
                'id'       => $job->chain->jobId,
                'chain_id' => $job->chain->chainId,
                'job'      => serialize($job),
            ];
        })->all());
    }

    /**
     * Determines whether there are any other concurrent jobs that are not done.
     *
     * @param  object  $job
     * @return bool
     */
    protected function hasRemainingConcurrentJobs($job)
    {
        return $this->query()->whereIn('id', $job->chain->current)->exists();
    }

    /**
     * Determines whether we are ready to dispatch the next link in the chain.
     *
     * @param  object  $job
     * @return bool
     */
    protected function shouldDispatchNextJobs($job)
    {
        return ! empty($job->chain->next) && ! $this->hasRemainingConcurrentJobs($job);
    }

    /**
     * Dispatch the jobs in the chain after the given job.
     *
     * @param  object  $job
     * @return void
     */
    protected function dispatchNextJobs($job)
    {
        $this->getNextJobs($job)->each(function ($job) {
            dispatch(unserialize($job));
        });
    }

    /**
     * Get the jobs that are next in the chain.
     *
     * @param  object  $job
     * @return \Illuminate\Support\Collection
     */
    protected function getNextJobs($job)
    {
        $query = $this->query()->whereIn('id', $job->chain->next);

        return $query->where('reserve_key', $this->reserve($query))->pluck('job');
    }

    /**
     * Reserve the jobs in the given query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function reserve($query)
    {
        return tap(Uuid::uuid4()->toString(), function ($key) use ($query) {
            (clone $query)->whereNull('reserve_key')->update(['reserve_key' => $key]);
        });
    }

    /**
     * Get a query instance for the chained jobs table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function query()
    {
        return $this->db->table('chained_jobs');
    }
}
