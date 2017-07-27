<?php

namespace Illuminate\Bus;

class ChainLink
{
    /**
     * The IDs of the current jobs in the chain.
     *
     * @var string[]
     */
    public $current = [];

    /**
     * The IDs of the next jobs in the chain.
     *
     * @var string[]
     */
    public $next = [];

    /**
     * The ID of this particular link in the chain.
     *
     * @var string
     */
    public $jobId;

    /**
     * The ID of this chain.
     *
     * @var string
     */
    public $chainId;

    /**
     * Create a new link in the chain.
     *
     * @param string  $jobId
     * @param string  $chainId
     */
    public function __construct($jobId, $chainId)
    {
        $this->chainId = $chainId;
        $this->jobId = $jobId;
    }

    /**
     * Set the current jobs' IDs.
     *
     * @param  string[]  $current
     * @return $this
     */
    public function current(array $current)
    {
        $this->current = $current;

        return $this;
    }

    /**
     * Set the next jobs' IDs.
     *
     * @param  string[]  $current
     * @return $this
     */
    public function next(array $next)
    {
        $this->next = $next;

        return $this;
    }
}
