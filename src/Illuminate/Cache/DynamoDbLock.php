<?php

namespace Illuminate\Cache;

use Illuminate\Cache\Lock;
use Aws\DynamoDb\DynamoDbClient;

class DynamoDbLock extends Lock
{
    /**
     * The DynamoDB client instance.
     *
     * @var \Illuminate\Cache\DynamoDbStore
     */
    protected $dynamo;

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Cache\DynamoDbStore  $dynamo
     * @param  string  $name
     * @param  int  $seconds
     * @return void
     */
    public function __construct(DynamoDbStore $dynamo, $name, $seconds)
    {
        parent::__construct($name, $seconds);

        $this->dynamo = $dynamo;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        return $this->dynamo->add(
            $this->name, 1, $this->seconds / 60
        );
    }

    /**
     * Release the lock.
     *
     * @return void
     */
    public function release()
    {
        $this->dynamo->forget($this->name);
    }
}
