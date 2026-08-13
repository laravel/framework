<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisConnection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PhpRedisConnectionTest extends TestCase
{
    public function testTransactionExecutesQueuedCommands()
    {
        $client = new PhpRedisQueueingClientStub;

        $connection = new PhpRedisConnection($client);

        $result = $connection->transaction(fn ($transaction) => $transaction->set('foo', 'bar'));

        $this->assertSame(['result'], $result);
        $this->assertSame(['multi', 'set', 'exec'], $client->calls);
    }

    public function testPipelineExecutesQueuedCommands()
    {
        $client = new PhpRedisQueueingClientStub;

        $connection = new PhpRedisConnection($client);

        $result = $connection->pipeline(fn ($pipeline) => $pipeline->set('foo', 'bar'));

        $this->assertSame(['result'], $result);
        $this->assertSame(['pipeline', 'set', 'exec'], $client->calls);
    }

    public function testTransactionDiscardsQueuedCommandsWhenTheCallbackThrows()
    {
        $client = new PhpRedisQueueingClientStub;

        $connection = new PhpRedisConnection($client);

        try {
            $connection->transaction(fn () => throw new RuntimeException('Whoops'));

            $this->fail('The exception thrown by the callback should not be swallowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Whoops', $e->getMessage());
        }

        $this->assertContains('discard', $client->calls);
        $this->assertNotContains('exec', $client->calls);
    }

    public function testPipelineDiscardsQueuedCommandsWhenTheCallbackThrows()
    {
        $client = new PhpRedisQueueingClientStub;

        $connection = new PhpRedisConnection($client);

        try {
            $connection->pipeline(fn () => throw new RuntimeException('Whoops'));

            $this->fail('The exception thrown by the callback should not be swallowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Whoops', $e->getMessage());
        }

        $this->assertContains('discard', $client->calls);
        $this->assertNotContains('exec', $client->calls);
    }

    public function testTransactionDiscardsQueuedCommandsWhenExecutionFails()
    {
        $client = new PhpRedisQueueingClientStub(failOnExec: true);

        $connection = new PhpRedisConnection($client);

        try {
            $connection->transaction(fn ($transaction) => $transaction->set('foo', 'bar'));

            $this->fail('The exception thrown while executing should not be swallowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Connection lost', $e->getMessage());
        }

        $this->assertContains('discard', $client->calls);
    }

    public function testTransactionDoesNotDiscardWhenNothingFails()
    {
        $client = new PhpRedisQueueingClientStub;

        $connection = new PhpRedisConnection($client);

        $connection->transaction(fn ($transaction) => $transaction->set('foo', 'bar'));

        $this->assertNotContains('discard', $client->calls);
    }

    public function testFailureToDiscardDoesNotReplaceTheOriginalException()
    {
        $client = new PhpRedisQueueingClientStub(failOnDiscard: true);

        $connection = new PhpRedisConnection($client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Whoops');

        $connection->transaction(fn () => throw new RuntimeException('Whoops'));
    }

    public function testTransactionWithoutACallbackReturnsTheClient()
    {
        $client = new PhpRedisQueueingClientStub;

        $connection = new PhpRedisConnection($client);

        $this->assertSame($client, $connection->transaction());
        $this->assertSame(['multi'], $client->calls);
    }

    public function testPipelineWithoutACallbackReturnsTheClient()
    {
        $client = new PhpRedisQueueingClientStub;

        $connection = new PhpRedisConnection($client);

        $this->assertSame($client, $connection->pipeline());
        $this->assertSame(['pipeline'], $client->calls);
    }
}

/**
 * Mimics the way phpredis queues commands: multi() and pipeline() return the client
 * itself, and so does every command queued against it until exec() is called.
 */
class PhpRedisQueueingClientStub
{
    public array $calls = [];

    public function __construct(
        protected bool $failOnExec = false,
        protected bool $failOnDiscard = false,
    ) {
    }

    public function multi()
    {
        $this->calls[] = 'multi';

        return $this;
    }

    public function pipeline()
    {
        $this->calls[] = 'pipeline';

        return $this;
    }

    public function set($key, $value)
    {
        $this->calls[] = 'set';

        return $this;
    }

    public function exec()
    {
        $this->calls[] = 'exec';

        if ($this->failOnExec) {
            throw new RuntimeException('Connection lost');
        }

        return ['result'];
    }

    public function discard()
    {
        $this->calls[] = 'discard';

        if ($this->failOnDiscard) {
            throw new RuntimeException('Redis server went away');
        }

        return true;
    }
}
