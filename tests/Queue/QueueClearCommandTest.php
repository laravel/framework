<?php

namespace Illuminate\Tests\Queue;

use JMac\Testing\Double;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ClearCommand;
use Illuminate\Queue\QueueManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueClearCommandTest extends TestCase
{
    public function testClearingDefaultQueue()
    {
        $queue = new FakeClearableQueue(['default' => 2]);

        $output = $this->runClearCommand($queue);

        $this->assertStringContainsString('Cleared 2 jobs from the [default] queue', $output);
        $this->assertSame(['default'], $queue->cleared);
    }

    public function testClearingMultipleQueues()
    {
        $queue = new FakeClearableQueue(['high' => 3, 'low' => 0, 'emails' => 1]);

        $output = $this->runClearCommand($queue, ['--queue' => 'high,low,emails']);

        $this->assertStringContainsString('Cleared 4 jobs from the [high, low, emails] queues', $output);
        $this->assertSame(['high', 'low', 'emails'], $queue->cleared);
    }

    public function testClearingMultipleQueuesWithWhitespace()
    {
        $queue = new FakeClearableQueue(['high' => 3, 'low' => 0]);

        $output = $this->runClearCommand($queue, ['--queue' => 'high, low']);

        $this->assertStringContainsString('Cleared 3 jobs from the [high, low] queues', $output);
        $this->assertSame(['high', 'low'], $queue->cleared);
    }

    public function testClearingMultipleQueuesWithEmptyValues()
    {
        $queue = new FakeClearableQueue(['high' => 3, 'low' => 0]);

        $output = $this->runClearCommand($queue, ['--queue' => 'high,,low']);

        $this->assertStringContainsString('Cleared 3 jobs from the [high, low] queues', $output);
        $this->assertSame(['high', 'low'], $queue->cleared);
    }

    public function testClearingMultipleQueuesWithDuplicates()
    {
        $queue = new FakeClearableQueue(['high' => 3, 'low' => 0]);

        $output = $this->runClearCommand($queue, ['--queue' => 'high,low,high']);

        $this->assertStringContainsString('Cleared 3 jobs from the [high, low] queues', $output);
        $this->assertSame(['high', 'low'], $queue->cleared);
    }

    protected function runClearCommand($queue, array $arguments = []): string
    {
        $container = new Application;
        $container['env'] = 'testing';

        $config = Double::for(Repository::class, \ArrayAccess::class);
        $config->expects('offsetGet')->with('queue.default')->andReturn('redis');
        $config->shouldReceive('get')->with('queue.connections.redis.queue', 'default')->andReturn('default');

        $container['config'] = $config;

        $queueManager = Double::for(QueueManager::class);
        $queueManager->expects('connection')->with('redis')->andReturn($queue);

        $container['queue'] = $queueManager;

        $command = new ClearCommand;
        $command->setLaravel($container);

        $output = new BufferedOutput();
        $command->run(new ArrayInput($arguments), $output);

        return $output->fetch();
    }
}

class FakeClearableQueue implements ClearableQueue
{
    public array $cleared = [];

    public function __construct(protected array $counts)
    {
    }

    public function clear($queue)
    {
        $this->cleared[] = $queue;

        return $this->counts[$queue] ?? 0;
    }
}
