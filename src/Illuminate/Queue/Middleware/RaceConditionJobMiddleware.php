<?php

namespace Illuminate\Queue\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Queue\HandlesRaceCondition;
use Illuminate\Queue\JobLocker;
use Throwable;

class RaceConditionJobMiddleware
{
    /**
     * Cache instance to handle the Job
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $repository;

    /**
     * Create a new LocksSlotJobMiddleware instance.
     *
     * @param \Illuminate\Contracts\Cache\Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handles the current command
     *
     * @param  $command
     * @param  \Closure $next
     * @return mixed
     * @throws \Throwable
     */
    public function handle($command, Closure $next)
    {
        if (!in_array(HandlesRaceCondition::class, class_uses_recursive($command))) {
            return $next($command);
        }

        $locker = $this->instanceLocker($command);

        // Execute the command and catch any exception thrown by it. Before continuing,
        // we will release the slot since the command didn't end properly. Just after
        // that we will proceed with the exception so other jobs can use this slot.
        try {
            $locker->reserveNextAvailableSlot();
            $result = $next($command);
        } catch (Throwable $throwable) {
            dump($throwable);
            $locker->releaseSlot();
            throw $throwable;
        }

        $job = $command->getJob();

        if ($job->isDeletedOrReleased() || $job->hasFailed()) {
            $locker->releaseSlot();
        } else {
            $locker->releaseAndUpdateSlot();
        }

        return $result;
    }

    /**
     * Creates a new Locker instance to use
     *
     * @param  mixed $command
     * @return \Illuminate\Queue\JobLocker
     */
    protected function instanceLocker($command)
    {
        return new JobLocker(
            $command,
            $this->useCache($command),
            $this->usePrefix($command),
            $this->useReservationTtl($command)
        );
    }

    /**
     * Returns the Cache to use with the Job
     *
     * @param  mixed $command
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function useCache($command)
    {
        return method_exists($command, 'cache') ? $command->cache() : $this->repository;
    }

    /**
     * Return the Job maximum time to live before timing out
     *
     * @param  mixed $command
     * @return null|int
     */
    protected function useReservationTtl($command)
    {
        return $command->slotTtl
            ?? $command->timeout
            ?? (method_exists($command, 'retryUntil') ? $command->retryUntil() : 60);
    }

    /**
     * Return the prefix to use with the Locker
     *
     * @param $instance
     * @return string
     */
    protected function usePrefix($instance)
    {
        return $instance->prefix ?? 'locker';
    }
}
