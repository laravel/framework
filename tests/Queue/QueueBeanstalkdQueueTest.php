<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueBeanstalkdQueueTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushProperlyPushesJobOntoBeanstalkd()
    {
        $queue = new \Illuminate\Queue\BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]), 1024, 0, 60);

        $queue->push('foo', ['data'], 'stack');
        $queue->push('foo', ['data']);
    }

    public function testDelayedPushProperlyPushesJobOntoBeanstalkd()
    {
        $queue = new \Illuminate\Queue\BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'timeout' => null, 'data' => ['data']]), \Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 5, \Pheanstalk\Pheanstalk::DEFAULT_TTR);

        $queue->later(5, 'foo', ['data'], 'stack');
        $queue->later(5, 'foo', ['data']);
    }

    public function testPopProperlyPopsJobOffOfBeanstalkd()
    {
        $queue = new \Illuminate\Queue\BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $queue->setContainer(m::mock('Illuminate\Container\Container'));
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
        $job = m::mock('Pheanstalk\Job');
        $pheanstalk->shouldReceive('reserve')->once()->andReturn($job);

        $result = $queue->pop();

        $this->assertInstanceOf('Illuminate\Queue\Jobs\BeanstalkdJob', $result);
    }

    public function testDeleteProperlyRemoveJobsOffBeanstalkd()
    {
        $queue = new \Illuminate\Queue\BeanstalkdQueue(m::mock('Pheanstalk\Pheanstalk'), 'default', 60);
        $pheanstalk = $queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('delete')->once()->with(m::type('Pheanstalk\Job'));

        $queue->deleteMessage('default', 1);
    }
}
