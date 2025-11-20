<?php

namespace Illuminate\Tests\Queue\Console;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Queue\QueueManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PauseListCommandTest extends TestCase
{
    protected $manager;
    protected $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(new ArrayStore);

        $cacheMock = m::mock();
        $cacheMock->shouldReceive('store')->andReturn($this->cache);

        $app = [
            'config' => [
                'queue.default' => 'redis',
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

    public function testGetPausedQueuesReturnsEmptyArrayWhenNoPausedQueues()
    {
        $pausedQueues = $this->manager->getPausedQueues();

        $this->assertIsArray($pausedQueues);
        $this->assertEmpty($pausedQueues);
    }

    public function testGetPausedQueuesReturnsAllPausedQueuesWithConnectionFormat()
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

    public function testPauseListIsUpdatedAfterResume()
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

    public function testPauseListShowsConnectionAndQueueSeparately()
    {
        $this->manager->pause('redis', 'default');
        $this->manager->pause('database', 'emails');

        $pausedQueues = $this->manager->getPausedQueues();

        // Verify format is connection:queue
        foreach ($pausedQueues as $queue) {
            $this->assertStringContainsString(':', $queue);
            $parts = explode(':', $queue, 2);
            $this->assertCount(2, $parts);
            $this->assertNotEmpty($parts[0]); // connection
            $this->assertNotEmpty($parts[1]); // queue
        }
    }

    public function testPauseListDoesNotContainDuplicates()
    {
        $this->manager->pause('redis', 'default');
        $this->manager->pause('redis', 'default'); // Pause same queue again

        $pausedQueues = $this->manager->getPausedQueues();

        $this->assertCount(1, $pausedQueues);
        $this->assertContains('redis:default', $pausedQueues);
    }
}
