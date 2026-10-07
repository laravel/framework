<?php

namespace Illuminate\Tests\Queue\Fixtures;

use Aws\Result;
use Aws\Sqs\SqsClient;

/**
 * Declares the methods that SqsClient otherwise only forwards through __call(), so they can be doubled.
 */
class SqsClientStub extends SqsClient
{
    public function sendMessage(array $args = []): Result
    {
        return new Result;
    }

    public function sendMessageBatch(array $args = []): Result
    {
        return new Result;
    }

    public function deleteMessage(array $args = []): Result
    {
        return new Result;
    }

    public function changeMessageVisibility(array $args = []): Result
    {
        return new Result;
    }

    public function getQueueAttributes(array $args = []): Result
    {
        return new Result;
    }

    public function purgeQueue(array $args = []): Result
    {
        return new Result;
    }

    public function receiveMessage(array $args = []): Result
    {
        return new Result;
    }
}
