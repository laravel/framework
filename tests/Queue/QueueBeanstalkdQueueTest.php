<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Queue\BeanstalkdQueue;
use Illuminate\Queue\Jobs\BeanstalkdJob;

class QueueBeanstalkdQueueTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]), 1024, 0, 60);

        $queue->push('foo', ['data'], 'stack');
        $queue->push('foo', ['data']);
    }

    public function testDelayedPushProperlyPushesJobOntoBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]), Pheanstalk::DEFAULT_PRIORITY, 5, Pheanstalk::DEFAULT_TTR);

        $queue->later(5, 'foo', ['data'], 'stack');
        $queue->later(5, 'foo', ['data']);
    }

    public function testPopProperlyPopsJobOffOfBeanstalkd()
    {
        $queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), 'default', 60);
        $queue->setContainer(m::mock(Container::class));
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
        $job = m::mock(Job::class);
        $pheanstalk->shouldReceive('reserve')->once()->andReturn($job);

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
