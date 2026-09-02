<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Console\Concerns\ParsesQueue;
use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Events\QueueResumed;
use Illuminate\Queue\Events\QueuesPaused;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class QueuePauseResumeTest extends TestCase
{
    protected $manager;
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new Repository(new ArrayStore);

        $this->manager = $this->createManager($this->cache);
    }

    protected function createManager($cache)
    {
        // Mock the cache facade to return our cache repository
        $cacheMock = Mockery::mock();
        $cacheMock->shouldReceive('store')->andReturn($cache);

        $app = [
            'config' => [
                'queue.default' => 'redis',
                'queue.connections.redis' => ['driver' => 'redis'],
                'queue.connections.database' => ['driver' => 'database'],
            ],
            'cache' => $cacheMock,
            'events' => new Dispatcher(),
        ];

        return new QueueManager($app);
    }

    public function testPauseQueueWithConnection()
    {
        $this->manager->pause('default', 'redis');

        $this->assertTrue($this->manager->isPaused('default', 'redis'));
    }

    public function testConnectionDefaultsToTheDefaultConnection()
    {
        $this->manager->pause('emails');

        $this->assertTrue($this->manager->isPaused('emails', 'redis'));
        $this->assertFalse($this->manager->isPaused('emails', 'database'));
    }

    public function testPauseQueueWithTTL()
    {
        $this->manager->pauseFor('default', 30, 'redis');

        $this->assertTrue($this->manager->isPaused('default', 'redis'));

        Carbon::setTestNow(Carbon::now()->addMinute());
        $this->assertFalse($this->manager->isPaused('default', 'redis'));
    }

    public function testPauseQueueIndefinitely()
    {
        $this->manager->pause('default', 'redis');

        $this->assertTrue($this->manager->isPaused('default', 'redis'));

        Carbon::setTestNow(Carbon::now()->addYear());
        $this->assertTrue($this->manager->isPaused('default', 'redis'));
    }

    public function testResumeQueue()
    {
        $this->manager->pause('default', 'redis');
        $this->assertTrue($this->manager->isPaused('default', 'redis'));

        $this->manager->resume('default', 'redis');
        $this->assertFalse($this->manager->isPaused('default', 'redis'));
    }

    public function testPausingQueueOnOneConnectionDoesNotAffectAnother()
    {
        $this->manager->pause('default', 'redis');

        $this->assertTrue($this->manager->isPaused('default', 'redis'));
        $this->assertFalse($this->manager->isPaused('default', 'database'));
    }

    public function testPausingDifferentQueuesOnSameConnection()
    {
        $this->manager->pause('emails', 'redis');
        $this->manager->pause('notifications', 'redis');

        $this->assertTrue($this->manager->isPaused('emails', 'redis'));
        $this->assertTrue($this->manager->isPaused('notifications', 'redis'));
        $this->assertFalse($this->manager->isPaused('default', 'redis'));
    }

    public function testResumingOnlyAffectsSpecificQueue()
    {
        $this->manager->pause('emails', 'redis');
        $this->manager->pause('notifications', 'redis');

        $this->manager->resume('emails', 'redis');

        $this->assertFalse($this->manager->isPaused('emails', 'redis'));
        $this->assertTrue($this->manager->isPaused('notifications', 'redis'));
    }

    public function testPauseDispatchesQueuePausedEvent()
    {
        $dispatchedEvent = null;

        $dispatcher = $this->manager->getApplication()['events'];

        $dispatcher->listen(QueuePaused::class, function ($event) use (&$dispatchedEvent) {
            $dispatchedEvent = $event;
        });

        $this->manager->pause('default', 'redis');

        $this->assertInstanceOf(QueuePaused::class, $dispatchedEvent);
        $this->assertSame('redis', $dispatchedEvent->connectionName);
        $this->assertSame('default', $dispatchedEvent->queue);
        $this->assertNull($dispatchedEvent->ttl);
    }

    public function testPauseForDispatchesQueuePausedEventWithTTL()
    {
        $dispatchedEvent = null;

        $dispatcher = $this->manager->getApplication()['events'];

        $dispatcher->listen(QueuePaused::class, function ($event) use (&$dispatchedEvent) {
            $dispatchedEvent = $event;
        });

        $this->manager->pauseFor('emails', 60, 'redis');

        $this->assertInstanceOf(QueuePaused::class, $dispatchedEvent);
        $this->assertSame('redis', $dispatchedEvent->connectionName);
        $this->assertSame('emails', $dispatchedEvent->queue);
        $this->assertSame(60, $dispatchedEvent->ttl);
    }

    public function testResumeDispatchesQueueResumedEvent()
    {
        $dispatchedEvent = null;

        $dispatcher = $this->manager->getApplication()['events'];

        $dispatcher->listen(QueueResumed::class, function ($event) use (&$dispatchedEvent) {
            $dispatchedEvent = $event;
        });

        $this->manager->resume('notifications', 'database');

        $this->assertInstanceOf(QueueResumed::class, $dispatchedEvent);
        $this->assertSame('database', $dispatchedEvent->connectionName);
        $this->assertSame('notifications', $dispatchedEvent->queue);
    }

    public function testGetPausedQueues()
    {
        $this->assertSame([], $this->manager->getPausedQueues(['default', 'emails'], 'redis'));

        $this->manager->pause('emails', 'redis');
        $this->manager->pause('notifications', 'redis');

        $this->assertSame(
            ['emails', 'notifications'],
            $this->manager->getPausedQueues(['default', 'emails', 'notifications'], 'redis')
        );
    }

    public function testPauseAllPausesEveryQueueAndResumeAllResumesThem()
    {
        $this->manager->pauseAll();

        $this->assertTrue($this->manager->isPaused('redis', 'default'));
        $this->assertTrue($this->manager->isPaused('database', 'emails'));
        $this->assertSame(
            ['default', 'emails'],
            $this->manager->getPausedQueues(['default', 'emails'], 'redis')
        );

        $this->manager->resumeAll();

        $this->assertFalse($this->manager->isPaused('redis', 'default'));
        $this->assertSame([], $this->manager->getPausedQueues(['default', 'emails'], 'redis'));
    }

    public function testPauseChecksDoNotBatchTheGlobalKeyWithQueueKeys()
    {
        $store = new class extends ArrayStore
        {
            public function many(array $keys)
            {
                if (count($keys) > 1 && in_array('illuminate:queues:paused', $keys)) {
                    throw new RuntimeException("CROSSSLOT Keys in request don't hash to the same slot");
                }

                return parent::many($keys);
            }
        };

        $manager = $this->createManager(new Repository($store));

        $this->assertFalse($manager->isPaused('redis', 'default'));
        $this->assertSame([], $manager->getPausedQueues(['default'], 'redis'));

        $manager->pauseAll();

        $this->assertTrue($manager->isPaused('redis', 'default'));
        $this->assertSame(['default'], $manager->getPausedQueues(['default'], 'redis'));
    }

    public function testPauseAllDispatchesQueuesPausedEvent()
    {
        $dispatchedEvent = null;

        $dispatcher = $this->manager->getApplication()['events'];

        $dispatcher->listen(QueuesPaused::class, function ($event) use (&$dispatchedEvent) {
            $dispatchedEvent = $event;
        });

        $this->manager->pauseAll();

        $this->assertInstanceOf(QueuesPaused::class, $dispatchedEvent);
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
