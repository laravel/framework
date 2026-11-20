<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\RedisQueue;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class QueueRedisJobTest extends TestCase
{
    use VerifiesDoubles;

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $handler = new RedisJobTestHandler;
        $job->getContainer()->instance('foo', $handler);

        $job->fire();

        $this->assertSame([[$job, ['data']]], $handler->fired);
    }

    public function testDeleteRemovesTheJobFromRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->expects('deleteReserved')
            ->with('default', $job);

        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->expects('deleteAndRelease')
            ->with('default', $job, 1);

        $job->release(1);
    }

    protected function getJob()
    {
        return new RedisJob(
            new Container,
            Double::for(RedisQueue::class),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]),
            'connection-name',
            'default'
        );
    }
}

class RedisJobTestHandler
{
    public array $fired = [];

    public function fire($job, array $data)
    {
        $this->fired[] = [$job, $data];
    }
}
