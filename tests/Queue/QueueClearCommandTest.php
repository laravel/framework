<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ClearCommand;
use Illuminate\Queue\QueueManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueClearCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testClearingDefaultQueue()
    {
        $queue = m::mock(ClearableQueue::class);
        $queue->shouldReceive('clear')->with('default')->once()->andReturn(2);

        $output = $this->runClearCommand($queue);

        $this->assertStringContainsString('Cleared 2 jobs from the [default] queue', $output);
    }

    public function testClearingMultipleQueues()
    {
        $queue = m::mock(ClearableQueue::class);
        $queue->shouldReceive('clear')->with('high')->once()->andReturn(3);
        $queue->shouldReceive('clear')->with('low')->once()->andReturn(0);
        $queue->shouldReceive('clear')->with('emails')->once()->andReturn(1);

        $output = $this->runClearCommand($queue, ['--queue' => 'high,low,emails']);

        $this->assertStringContainsString('Cleared 4 jobs from the [high, low, emails] queues', $output);
    }

    public function testClearingMultipleQueuesWithWhitespace()
    {
        $queue = m::mock(ClearableQueue::class);
        $queue->shouldReceive('clear')->with('high')->once()->andReturn(3);
        $queue->shouldReceive('clear')->with('low')->once()->andReturn(0);

        $output = $this->runClearCommand($queue, ['--queue' => 'high, low']);

        $this->assertStringContainsString('Cleared 3 jobs from the [high, low] queues', $output);
    }

    public function testClearingMultipleQueuesWithEmptyValues()
    {
        $queue = m::mock(ClearableQueue::class);
        $queue->shouldReceive('clear')->with('high')->once()->andReturn(3);
        $queue->shouldReceive('clear')->with('low')->once()->andReturn(0);

        $output = $this->runClearCommand($queue, ['--queue' => 'high,,low']);

        $this->assertStringContainsString('Cleared 3 jobs from the [high, low] queues', $output);
    }

    public function testClearingMultipleQueuesWithDuplicates()
    {
        $queue = m::mock(ClearableQueue::class);
        $queue->shouldReceive('clear')->with('high')->once()->andReturn(3);
        $queue->shouldReceive('clear')->with('low')->once()->andReturn(0);

        $output = $this->runClearCommand($queue, ['--queue' => 'high,low,high']);

        $this->assertStringContainsString('Cleared 3 jobs from the [high, low] queues', $output);
    }

    protected function runClearCommand($queue, array $arguments = []): string
    {
        $container = new Application;
        $container['env'] = 'testing';

        $config = m::mock(Repository::class, \ArrayAccess::class);
        $config->shouldReceive('offsetGet')->with('queue.default')->andReturn('redis');
        $config->shouldReceive('get')->with('queue.connections.redis.queue', 'default')->andReturn('default');

        $container['config'] = $config;

        $queueManager = m::mock(QueueManager::class);
        $queueManager->shouldReceive('connection')->with('redis')->andReturn($queue);

        $container['queue'] = $queueManager;

        $command = new ClearCommand;
        $command->setLaravel($container);

        $output = new BufferedOutput();
        $command->run(new ArrayInput($arguments), $output);

        return $output->fetch();
    }
}
