<?php

namespace Illuminate\Foundation\Queue;

use ReflectionClass;

trait InteractsWithQueueAndConnection
{
    /**
     * Extract the connection from OnConnection attribute if present.
     *
     * @param  \ReflectionClass  $reflectionClass
     * @return string|\UnitEnum|null
     */
    protected function getConnectionFromOnConnectionAttribute(ReflectionClass $reflectionClass)
    {
        $onConnection = $reflectionClass->getAttributes(OnConnection::class);
        if ($onConnection === []) {
            return null;
        }

        return $onConnection[0]->newInstance()->connection;
    }

    /**
     * Extract the queue from OnQueue attribute if present.
     *
     * @param  \ReflectionClass  $reflectionClass
     * @return string|\UnitEnum|null
     */
    protected function getQueueFromOnQueueAttribute(ReflectionClass $reflectionClass)
    {
        $onQueue = $reflectionClass->getAttributes(OnQueue::class);
        if ($onQueue === []) {
            return null;
        }

        return $onQueue[0]->newInstance()->queue;
    }
}
