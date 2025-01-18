<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class QueuedUniqueCommand extends QueuedCommand implements ShouldBeUnique
{
    /**
     * The unique id for this job.
     *
     * @var string
     */
    protected $uniqueId;

    /**
     * The amount of seconds to keep the command unique.
     *
     * @var int
     */
    protected $uniqueFor;

    /**
     * The Cache repository where the lock should be acquired.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $via;

    /**
     * Sets the unique id for this command.
     *
     * @param  string  $id
     * @return void
     */
    public function setUniqueId($id)
    {
        $this->uniqueId = $id;
    }

    /**
     * Sets the Cache repository where the lock should be acquired.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $via
     * @return void
     */
    public function setUniqueVia($via)
    {
        $this->via = $via;
    }

    /**
     * The amount of seconds to keep the command unique.
     *
     * @param  int  $amount
     * @return void
     */
    public function setUniqueFor($amount)
    {
        $this->uniqueFor = $amount;
    }

    /**
     * Get the unique ID for the job.
     *
     * @return  string
     */
    public function uniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @return  string
     */
    public function uniqueFor()
    {
        return $this->uniqueId;
    }

    /**
     * Get the cache driver for the unique job lock.
     *
     * @return  \Illuminate\Contracts\Cache\Repository
     */
    public function uniqueVia()
    {
        return $this->via;
    }
}
