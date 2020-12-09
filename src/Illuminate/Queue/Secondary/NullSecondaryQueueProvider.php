<?php

namespace Illuminate\Queue\Secondary;

class NullSecondaryQueueProvider implements SecondaryQueueProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function push($connection, $queue, $job, $delay, $exception)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function forget($id)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        //
    }
}
