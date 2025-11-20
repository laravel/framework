<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Queue\QueueManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueuePauseResumeTest extends TestCase
{
    protected $manager;
    protected $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(new ArrayStore);

        // Mock the cache facade to return our cache repository
        $cacheMock = m::mock();
        $cacheMock->shouldReceive('store')->andReturn($this->cache);

        $app = [
            'config' => [
                'queue.default' => 'redis',
                'queue.connections.redis' => ['driver' => 'redis'],
                'queue.connections.database' => ['driver' => 'database'],
            ],
            'cache' => $cacheMock,
        ];

        $this->manager = new QueueManager($app);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testPauseQueueWithConnection()
    {
        $this->manager->pause('redis', 'default');

        $this->assertTrue($this->manager->isPaused('redis', 'default'));
    }

    public function testPauseQueueWithTTL()
    {
        $this->manager->pause('redis', 'default', 60);

        $this->assertTrue($this->manager->isPaused('redis', 'default'));
    }

    public function testPauseQueueIndefinitely()
    {
        $this->manager->pause('redis', 'default', null);

        $this->assertTrue($this->manager->isPaused('redis', 'default'));
    }

    public function testResumeQueue()
    {
        $this->manager->pause('redis', 'default');
        $this->assertTrue($this->manager->isPaused('redis', 'default'));

        $this->manager->resume('redis', 'default');
        $this->assertFalse($this->manager->isPaused('redis', 'default'));
    }

    public function testPausingQueueOnOneConnectionDoesNotAffectAnother()
    {
        $this->manager->pause('redis', 'default');

        $this->assertTrue($this->manager->isPaused('redis', 'default'));
        $this->assertFalse($this->manager->isPaused('database', 'default'));
    }

    public function testPausingDifferentQueuesOnSameConnection()
    {
        $this->manager->pause('redis', 'emails');
        $this->manager->pause('redis', 'notifications');

        $this->assertTrue($this->manager->isPaused('redis', 'emails'));
        $this->assertTrue($this->manager->isPaused('redis', 'notifications'));
        $this->assertFalse($this->manager->isPaused('redis', 'default'));
    }

    public function testResumingOnlyAffectsSpecificQueue()
    {
        $this->manager->pause('redis', 'emails');
        $this->manager->pause('redis', 'notifications');

        $this->manager->resume('redis', 'emails');

        $this->assertFalse($this->manager->isPaused('redis', 'emails'));
        $this->assertTrue($this->manager->isPaused('redis', 'notifications'));
    }

    public function testGetPausedQueuesReturnsAllPausedQueues()
    {
        $this->manager->pause('redis', 'default');
        $this->manager->pause('database', 'emails');
        $this->manager->pause('redis', 'notifications');

        $pausedQueues = $this->manager->getPausedQueues();

        $this->assertCount(3, $pausedQueues);
        $this->assertContains('redis:default', $pausedQueues);
        $this->assertContains('database:emails', $pausedQueues);
        $this->assertContains('redis:notifications', $pausedQueues);
    }

    public function testGetPausedQueuesAfterResume()
    {
        $this->manager->pause('redis', 'default');
        $this->manager->pause('database', 'emails');

        $this->assertCount(2, $this->manager->getPausedQueues());

        $this->manager->resume('redis', 'default');

        $pausedQueues = $this->manager->getPausedQueues();
        $this->assertCount(1, $pausedQueues);
        $this->assertContains('database:emails', $pausedQueues);
        $this->assertNotContains('redis:default', $pausedQueues);
    }
}
