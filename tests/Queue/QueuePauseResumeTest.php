<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Queue\Console\Concerns\ParsesQueue;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Carbon;
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
        Carbon::setTestNow();
        $this->manager->pauseFor('redis', 'default', 30);

        $this->assertTrue($this->manager->isPaused('redis', 'default'));

        Carbon::setTestNow(Carbon::now()->addMinute());
        $this->assertFalse($this->manager->isPaused('redis', 'default'));
    }

    public function testPauseQueueIndefinitely()
    {
        Carbon::setTestNow();
        $this->manager->pause('redis', 'default');

        $this->assertTrue($this->manager->isPaused('redis', 'default'));

        Carbon::setTestNow(Carbon::now()->addYear());
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

    public function testParsingQueueString()
    {
        $parser = new class()
        {
            use ParsesQueue;

            private array $laravel = [
                'config' => ['queue.default' => 'redis'],
            ];

            public function parse(string $queue)
            {
                return $this->parseQueue($queue);
            }
        };

        $this->assertSame(['redis', 'default'], $parser->parse(''));
        $this->assertSame(['redis', 'emails'], $parser->parse('emails'));
        $this->assertSame(['database', 'notifications'], $parser->parse('database:notifications'));
        $this->assertSame(['redis', 'foo:bar'], $parser->parse('redis:foo:bar'));
    }
}
