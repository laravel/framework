<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\Console\PeekCommand;
use Illuminate\Queue\Jobs\InspectedJob;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueuePeekCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testItDisplaysJobsOnTheQueue()
    {
        $this->app['config']->set('queue.default', 'redis');
        $this->app['config']->set('queue.connections.redis.queue', 'default');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('pendingJobs')->with('default')->andReturn(new Collection([
            new InspectedJob(
                uuid: '1cde062a-8c6a-4d67-8125-e83f585c18cb',
                queue: 'default',
                name: 'App\\Jobs\\ProcessPodcast',
                attempts: 1,
                createdAt: Carbon::now()->subMinutes(5),
            ),
        ]));

        $this->mockQueueConnection($queue);

        $output = $this->runPeekCommand()->fetch();

        $this->assertStringContainsString('App\\Jobs\\ProcessPodcast', $output);
        $this->assertStringContainsString('1cde062a-8c6a-4d67-8125-e83f585c18cb', $output);
        $this->assertStringContainsString('1 attempt', $output);
        $this->assertStringContainsString('5 minutes ago', $output);
    }

    public function testItDisplaysWhenEmpty()
    {
        $this->app['config']->set('queue.default', 'redis');
        $this->app['config']->set('queue.connections.redis.queue', 'default');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('pendingJobs')->with('default')->andReturn(new Collection);

        $this->mockQueueConnection($queue);

        $output = $this->runPeekCommand()->fetch();

        $this->assertStringContainsString('No pending jobs found on the [default] queue.', $output);
    }

    public function testItCanOutputAsJson()
    {
        $this->app['config']->set('queue.default', 'redis');
        $this->app['config']->set('queue.connections.redis.queue', 'default');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('pendingJobs')->with('default')->andReturn(new Collection([
            new InspectedJob(
                uuid: '1cde062a-8c6a-4d67-8125-e83f585c18cb',
                queue: 'default',
                name: 'App\\Jobs\\ProcessPodcast',
                attempts: 1,
                createdAt: Carbon::parse('2026-01-01 12:00:00', 'UTC'),
            ),
        ]));

        $this->mockQueueConnection($queue);

        $output = $this->runPeekCommand(['--json' => true])->fetch();

        $jobs = json_decode($output, true);

        $this->assertSame([
            'uuid' => '1cde062a-8c6a-4d67-8125-e83f585c18cb',
            'queue' => 'default',
            'name' => 'App\\Jobs\\ProcessPodcast',
            'attempts' => 1,
            'created_at' => '2026-01-01T12:00:00+00:00',
        ], $jobs[0]);
    }

    public function testItDisplaysDelayedJobs()
    {
        $this->app['config']->set('queue.default', 'redis');
        $this->app['config']->set('queue.connections.redis.queue', 'default');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('delayedJobs')->with('default')->andReturn(new Collection([
            new InspectedJob(
                uuid: '1cde062a-8c6a-4d67-8125-e83f585c18cb',
                queue: 'default',
                name: 'App\\Jobs\\ProcessPodcast',
                attempts: 0,
                createdAt: Carbon::now()->subMinutes(2),
            ),
        ]));

        $this->mockQueueConnection($queue);

        $output = $this->runPeekCommand(['--state' => 'delayed'])->fetch();

        $this->assertStringContainsString('App\\Jobs\\ProcessPodcast', $output);
    }

    public function testItDisplaysReservedJobs()
    {
        $this->app['config']->set('queue.default', 'redis');
        $this->app['config']->set('queue.connections.redis.queue', 'default');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('reservedJobs')->with('default')->andReturn(new Collection([
            new InspectedJob(
                uuid: '1cde062a-8c6a-4d67-8125-e83f585c18cb',
                queue: 'default',
                name: 'App\\Jobs\\ProcessPodcast',
                attempts: 2,
                createdAt: Carbon::now()->subMinutes(1),
            ),
        ]));

        $this->mockQueueConnection($queue);

        $output = $this->runPeekCommand(['--state' => 'reserved'])->fetch();

        $this->assertStringContainsString('App\\Jobs\\ProcessPodcast', $output);
        $this->assertStringContainsString('2 attempts', $output);
    }

    public function testItUsesTheQueueOption()
    {
        $this->app['config']->set('queue.default', 'redis');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('pendingJobs')->with('high')->andReturn(new Collection);

        $this->mockQueueConnection($queue);

        $output = $this->runPeekCommand(['--queue' => 'high'])->fetch();

        $this->assertStringContainsString('No pending jobs found on the [high] queue.', $output);
    }

    public function testItRejectsAnInvalidState()
    {
        $output = $this->runPeekCommand(['--state' => 'failed'])->fetch();

        $this->assertStringContainsString('The state must be one of: pending, delayed, reserved.', $output);
    }

    public function testItOutputsWhenQueueDoesNotSupportInspection()
    {
        $this->app['config']->set('queue.default', 'jack');

        $this->mockQueueConnection(m::mock());

        $output = $this->runPeekCommand()->fetch();

        $this->assertStringContainsString('The [jack] connection does not support inspecting jobs.', $output);
    }

    protected function mockQueueConnection(mixed $queue): void
    {
        $manager = m::mock(Factory::class);
        $manager->shouldReceive('connection')->andReturn($queue);
        $this->app->instance('queue', $manager);
    }

    protected function runPeekCommand($arguments = [])
    {
        $input = new ArrayInput($arguments);
        $output = new BufferedOutput;

        tap(new PeekCommand)
            ->setLaravel($this->app)
            ->run($input, $output);

        return $output;
    }
}
