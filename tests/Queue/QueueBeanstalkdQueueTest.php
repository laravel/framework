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
    /**
     * @var \Illuminate\Queue\BeanstalkdQueue
     */
    private $queue;

    /**
     * @var \Illuminate\Container\Container|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $container;

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

        $this->setQueue('default', 60);
        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data']]), 1024, 0, 60);

        $this->queue->push('foo', ['data'], 'stack');
        $this->queue->push('foo', ['data']);

        $this->container->shouldHaveReceived('bound')->with('events')->times(2);

        Str::createUuidsNormally();
    }

    public function testDelayedPushProperlyPushesJobOntoBeanstalkd()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $this->setQueue('default', 60);
        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('stack')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('put')->twice()->with(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data']]), Pheanstalk::DEFAULT_PRIORITY, 5, Pheanstalk::DEFAULT_TTR);

        $this->queue->later(5, 'foo', ['data'], 'stack');
        $this->queue->later(5, 'foo', ['data']);

        $this->container->shouldHaveReceived('bound')->with('events')->times(2);

        Str::createUuidsNormally();
    }

    public function testPopProperlyPopsJobOffOfBeanstalkd()
    {
        $this->setQueue('default', 60);

        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
        $job = m::mock(Job::class);
        $pheanstalk->shouldReceive('reserveWithTimeout')->once()->with(0)->andReturn($job);

        $result = $this->queue->pop();

        $this->assertInstanceOf(BeanstalkdJob::class, $result);
    }

    public function testBlockingPopProperlyPopsJobOffOfBeanstalkd()
    {
        $this->setQueue('default', 60, 60);

        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->shouldReceive('watchOnly')->once()->with('default')->andReturn($pheanstalk);
        $job = m::mock(Job::class);
        $pheanstalk->shouldReceive('reserveWithTimeout')->once()->with(60)->andReturn($job);

        $result = $this->queue->pop();

        $this->assertInstanceOf(BeanstalkdJob::class, $result);
    }

    public function testDeleteProperlyRemoveJobsOffBeanstalkd()
    {
        $this->setQueue('default', 60);

        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->shouldReceive('useTube')->once()->with('default')->andReturn($pheanstalk);
        $pheanstalk->shouldReceive('delete')->once()->with(m::type(Job::class));

        $this->queue->deleteMessage('default', 1);
    }

    /**
     * @param  string  $default
     * @param  int  $timeToRun
     * @param  int  $blockFor
     */
    private function setQueue($default, $timeToRun, $blockFor = 0)
    {
        $this->queue = new BeanstalkdQueue(m::mock(Pheanstalk::class), $default, $timeToRun, $blockFor);
        $this->container = m::spy(Container::class);
        $this->queue->setContainer($this->container);
    }
}
