<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Support\Testing\Fakes\BusFake;

class Bus extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $jobsToFake
     * @return \Illuminate\Support\Testing\Fakes\BusFake
     */
    public static function fake($jobsToFake = [])
    {
        static::swap($fake = new BusFake(static::getFacadeRoot(), $jobsToFake));

        return $fake;
    }

    /**
     * Dispatch the given chain of jobs.
     *
     * @param  array|mixed  $jobs
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatchChain($jobs)
    {
        $jobs = is_array($jobs) ? $jobs : func_get_args();

        return (new PendingChain(array_shift($jobs), $jobs))
                    ->dispatch();
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BusDispatcherContract::class;
    }
}
