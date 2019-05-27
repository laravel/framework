<?php

namespace Illuminate\Queue\Failed;

interface QueryableFailedJobProviderInterface extends FailedJobProviderInterface
{
    /**
     * Retrieve job ids from specific connection and queue.
     *
     * @param string|null $connection
     * @param string|null $queue
     * @param string|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function getJobIds($connection, $queue, $order, $limit, $offset);
}
