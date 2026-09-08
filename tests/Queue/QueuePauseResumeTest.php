<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RedisStore;
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
        $this->manager->pause('redis', 'default');

        $this->assertTrue($this->manager->isPaused('redis', 'default'));
    }

    public function testPauseQueueWithTTL()
    {
        $this->manager->pauseFor('redis', 'default', 30);

        $this->assertTrue($this->manager->isPaused('redis', 'default'));

        Carbon::setTestNow(Carbon::now()->addMinute());
        $this->assertFalse($this->manager->isPaused('redis', 'default'));
    }

    public function testPauseQueueIndefinitely()
    {
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

    public function testPauseDispatchesQueuePausedEvent()
    {
        $dispatchedEvent = null;

        $dispatcher = $this->manager->getApplication()['events'];

        $dispatcher->listen(QueuePaused::class, function ($event) use (&$dispatchedEvent) {
            $dispatchedEvent = $event;
        });

        $this->manager->pause('redis', 'default');

        $this->assertInstanceOf(QueuePaused::class, $dispatchedEvent);
        $this->assertSame('redis', $dispatchedEvent->connection);
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

        $this->manager->pauseFor('redis', 'emails', 60);

        $this->assertInstanceOf(QueuePaused::class, $dispatchedEvent);
        $this->assertSame('redis', $dispatchedEvent->connection);
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

        $this->manager->resume('database', 'notifications');

        $this->assertInstanceOf(QueueResumed::class, $dispatchedEvent);
        $this->assertSame('database', $dispatchedEvent->connection);
        $this->assertSame('notifications', $dispatchedEvent->queue);
    }

    public function testGetPausedQueues()
    {
        $this->assertSame([], $this->manager->getPausedQueues('redis', ['default', 'emails']));

        $this->manager->pause('redis', 'emails');
        $this->manager->pause('redis', 'notifications');

        $this->assertSame(
            ['emails', 'notifications'],
            $this->manager->getPausedQueues('redis', ['default', 'emails', 'notifications'])
        );
    }

    public function testPauseAllPausesEveryQueueAndResumeAllResumesThem()
    {
        $this->manager->pauseAll();

        $this->assertTrue($this->manager->isPaused('redis', 'default'));
        $this->assertTrue($this->manager->isPaused('database', 'emails'));
        $this->assertSame(
            ['default', 'emails'],
            $this->manager->getPausedQueues('redis', ['default', 'emails'])
        );

        $this->manager->resumeAll();

        $this->assertFalse($this->manager->isPaused('redis', 'default'));
        $this->assertSame([], $this->manager->getPausedQueues('redis', ['default', 'emails']));
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
        $this->assertSame([], $manager->getPausedQueues('redis', ['default']));

        $manager->pauseAll();

        $this->assertTrue($manager->isPaused('redis', 'default'));
        $this->assertSame(['default'], $manager->getPausedQueues('redis', ['default']));
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

    public function testPauseAndResumeUseHashTagsOnRedisCluster()
    {
        $storage = [];
        $manager = $this->createManager(new Repository($this->createRedisStore(true, $storage)));

        $manager->pause('redis', 'default');
        $this->assertTrue($manager->isPaused('redis', 'default'));
        $this->assertSame(['default'], $manager->getPausedQueues('redis', ['default', 'emails']));

        $manager->resume('redis', 'default');
        $this->assertFalse($manager->isPaused('redis', 'default'));
        $this->assertSame([], $manager->getPausedQueues('redis', ['default', 'emails']));
    }

    public function testPauseForUsesHashTagsOnRedisCluster()
    {
        $manager = $this->createManager(new Repository($this->createRedisStore(true)));

        $manager->pauseFor('redis', 'default', 60);

        $this->assertTrue($manager->isPaused('redis', 'default'));
    }

    public function testPauseAndResumeDoNotUseHashTagsOnNonClusterRedis()
    {
        $storage = [];
        $manager = $this->createManager(new Repository($this->createRedisStore(false, $storage)));

        $manager->pause('redis', 'default');
        $this->assertTrue($manager->isPaused('redis', 'default'));
        $this->assertSame(['default'], $manager->getPausedQueues('redis', ['default', 'emails']));

        $manager->resume('redis', 'default');
        $this->assertFalse($manager->isPaused('redis', 'default'));
        $this->assertSame([], $manager->getPausedQueues('redis', ['default', 'emails']));
    }

    public function testPauseForDoesNotUseHashTagsOnNonClusterRedis()
    {
        $manager = $this->createManager(new Repository($this->createRedisStore(false)));

        $manager->pauseFor('redis', 'default', 60);

        $this->assertTrue($manager->isPaused('redis', 'default'));
    }

    protected function createRedisStore(bool $cluster, &$storage = [])
    {
        return new class($cluster, $storage) extends RedisStore
        {
            protected $cluster;
            protected $storage;

            public function __construct(bool $cluster, &$storage)
            {
                $this->cluster = $cluster;
                $this->storage = &$storage;
            }

            public function connection()
            {
                $cluster = $this->cluster;

                return new class($cluster)
                {
                    public function __construct(protected bool $cluster)
                    {
                    }

                    public function isCluster()
                    {
                        return $this->cluster;
                    }
                };
            }

            public function forever($key, $value)
            {
                $this->storage[$key] = $value;
            }

            public function put($key, $value, $seconds)
            {
                $this->storage[$key] = $value;
            }

            public function forget($key)
            {
                unset($this->storage[$key]);
            }

            public function get($key)
            {
                return $this->storage[$key] ?? null;
            }

            public function many(array $keys)
            {
                if ($this->cluster && count($keys) > 1) {
                    foreach ($keys as $key) {
                        if (! str_contains($key, '{paused}')) {
                            throw new RuntimeException("CROSSSLOT Keys in request don't hash to the same slot");
                        }
                    }
                }

                return array_combine($keys, array_map(fn ($key) => $this->storage[$key] ?? null, $keys));
            }
        };
    }
}
