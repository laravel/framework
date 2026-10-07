<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;

/**
 * Declares the methods that DynamoDbClient otherwise only forwards through __call(), so they can be doubled.
 */
class DynamoDbClientStub extends DynamoDbClient
{
    public function deleteItem(array $args = []): Result|array
    {
        return new Result;
    }

    public function getItem(array $args = []): Result|array
    {
        return new Result;
    }

    public function putItem(array $args = []): Result|array
    {
        return new Result;
    }

    public function query(array $args = []): Result|array
    {
        return new Result;
    }
}
