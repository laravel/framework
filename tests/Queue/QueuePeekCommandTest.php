<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\Jobs\InspectedJob;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery as m;
use Orchestra\Testbench\TestCase;

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

        $this->artisan('queue:peek')
            ->expectsOutputToContain('App\\Jobs\\ProcessPodcast')
            ->expectsOutputToContain('1cde062a-8c6a-4d67-8125-e83f585c18cb')
            ->expectsOutputToContain('1 attempt')
            ->expectsOutputToContain('5 minutes ago')
            ->assertSuccessful();
    }

    public function testItDisplaysWhenEmpty()
    {
        $this->app['config']->set('queue.default', 'redis');
        $this->app['config']->set('queue.connections.redis.queue', 'default');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('pendingJobs')->with('default')->andReturn(new Collection);

        $this->mockQueueConnection($queue);

        $this->artisan('queue:peek')
            ->expectsOutputToContain('No pending jobs found on the [default] queue.')
            ->assertSuccessful();
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

        $this->artisan('queue:peek', ['--json' => true])
            ->expectsOutputToContain('"uuid":"1cde062a-8c6a-4d67-8125-e83f585c18cb"')
            ->expectsOutputToContain('"name":"App\\\\Jobs\\\\ProcessPodcast"')
            ->assertSuccessful();
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

        $this->artisan('queue:peek', ['--state' => 'delayed'])
            ->expectsOutputToContain('App\\Jobs\\ProcessPodcast')
            ->assertSuccessful();
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

        $this->artisan('queue:peek', ['--state' => 'reserved'])
            ->expectsOutputToContain('App\\Jobs\\ProcessPodcast')
            ->expectsOutputToContain('2 attempts')
            ->assertSuccessful();
    }

    public function testItUsesTheQueueOption()
    {
        $this->app['config']->set('queue.default', 'redis');

        $queue = m::mock(RedisQueue::class);
        $queue->shouldReceive('pendingJobs')->with('high')->andReturn(new Collection);

        $this->mockQueueConnection($queue);

        $this->artisan('queue:peek', ['--queue' => 'high'])
            ->expectsOutputToContain('No pending jobs found on the [high] queue.')
            ->assertSuccessful();
    }

    public function testItRejectsAnInvalidState()
    {
        $this->artisan('queue:peek', ['--state' => 'failed'])
            ->expectsOutputToContain('The state must be one of: pending, delayed, reserved.')
            ->assertFailed();
    }

    public function testItOutputsWhenQueueDoesNotSupportInspection()
    {
        $this->app['config']->set('queue.default', 'jack');

        $this->mockQueueConnection(m::mock());

        $this->artisan('queue:peek')
            ->expectsOutputToContain('The [jack] connection does not support inspecting jobs.')
            ->assertFailed();
    }

    protected function mockQueueConnection(mixed $queue): void
    {
        $manager = m::mock(Factory::class);
        $manager->shouldReceive('connection')->andReturn($queue);
        $this->app->instance('queue', $manager);
    }
}
