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

    public function testRetryDelayCallsHandlerWithMethodDefined()
    {
        $job = $this->getJob();
        $handler = $this->getMockBuilder('stdClass')->setMethods(['retryDelay'])->getMock();

        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler);
        $handler->expects($this->once())->method('retryDelay')->with($job, ['data'])->willReturn(10);

        $retryDelay = $job->retryDelay();

        $this->assertEquals(10, $retryDelay);
    }

    public function testRetryDelayDoesNotCallHandlerWithoutMethodDefined()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('stdClass'));
        $handler->shouldNotReceive('retryDelay');

        $retryDelay = $job->retryDelay();

        $this->assertEquals(0, $retryDelay);
    }

    protected function getJob()
    {
        return new \Illuminate\Queue\Jobs\RedisJob(
            m::mock(\Illuminate\Container\Container::class),
            m::mock(\Illuminate\Queue\RedisQueue::class),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]),
            'connection-name',
            'default'
        );
    }
}
