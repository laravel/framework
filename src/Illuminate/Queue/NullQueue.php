<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;

class NullQueue extends Queue implements QueueContract
{
    /**
     * {@inheritdoc}
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', $queue = null)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
        //
    }
}
