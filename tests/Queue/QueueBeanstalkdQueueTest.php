<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\BeanstalkdQueue;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Illuminate\Support\Str;
use Mockery as m;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\TestCase;

class QueueBeanstalkdQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoBeanstalkd()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'backoff' => null, 'timeout' => null, 'data' => ['data']]), 1024, 0, 60);

        $queue->push('foo', ['data'], 'stack');
        $queue->push('foo', ['data']);

        Str::createUuidsNormally();
    }

    public function testDelayedPushProperlyPushesJobOntoBeanstalkd()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'backoff' => null, 'timeout' => null, 'data' => ['data']]), Pheanstalk::DEFAULT_PRIORITY, 5, Pheanstalk::DEFAULT_TTR);

        $queue->later(5, 'foo', ['data'], 'stack');
        $queue->later(5, 'foo', ['data']);

        Str::createUuidsNormally();
    }

    public function testPopProperlyPopsJobOffOfBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60);
        $queue->setContainer(m::mock(Container::class));
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
        $job = m::mock(Job::class);
        $pheanstalk->shouldReceive('reserveWithTimeout')->once()->with(0)->andReturn($job);

        $result = $queue->pop();

        $this->assertInstanceOf(BeanstalkdJob::class, $result);
    }

    public function testBlockingPopProperlyPopsJobOffOfBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60, 60);
        $queue->setContainer(m::mock(Container::class));
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
        $job = m::mock(Job::class);
        $pheanstalk->shouldReceive('reserveWithTimeout')->once()->with(60)->andReturn($job);

        $result = $queue->pop();

        $this->assertInstanceOf(BeanstalkdJob::class, $result);
    }

    public function testDeleteProperlyRemoveJobsOffBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('delete')->once()->with(m::type(Job::class));

        $queue->deleteMessage('default', 1);
    }
}
