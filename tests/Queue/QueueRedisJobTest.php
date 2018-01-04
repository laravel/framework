<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueRedisJobTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('stdClass'));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);

        $job->fire();
    }

    public function testDeleteRemovesTheJobFromRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteReserved')->once()
            ->with('default', $job);

        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteAndRelease')->once()
            ->with('default', $job, 1);

        $job->release(1);
    }

    public function testGetSharedDataFromRedisJob()
    {
        $job = $this->getJob();

        $this->assertSame('taylor', $job->shared('name'));
        $this->assertSame('bar', $job->shared('foo', 'bar'));
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $job->shared(null));
        $this->assertSame(['name' => 'taylor'], $job->shared(null)->toArray());
    }

    protected function getJob()
    {
        return new \Illuminate\Queue\Jobs\RedisJob(
            m::mock(\Illuminate\Container\Container::class),
            m::mock(\Illuminate\Queue\RedisQueue::class),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1, 'shared' => ['name' => 'taylor']]),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2, 'shared' => ['name' => 'taylor']]),
            'connection-name',
            'default'
        );
    }
}
