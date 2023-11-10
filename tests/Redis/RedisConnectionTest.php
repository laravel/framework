<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\RedisManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Redis;

class RedisConnectionTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();

        m::close();
    }

    public function testItSetsValuesWithExpiry()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed', 'EX', 5, 'NX');
            $this->assertSame('mohamed', $redis->get('one'));
            $this->assertNotEquals(-1, $redis->ttl('one'));

            // It doesn't override when NX mode
            $redis->set('one', 'taylor', 'EX', 5, 'NX');
            $this->assertSame('mohamed', $redis->get('one'));

            // It overrides when XX mode
            $redis->set('one', 'taylor', 'EX', 5, 'XX');
            $this->assertSame('taylor', $redis->get('one'));

            // It fails if XX mode is on and key doesn't exist
            $redis->set('two', 'taylor', 'PX', 5, 'XX');
            $this->assertNull($redis->get('two'));

            $redis->set('three', 'mohamed', 'PX', 5000);
            $this->assertSame('mohamed', $redis->get('three'));
            $this->assertNotEquals(-1, $redis->ttl('three'));
            $this->assertNotEquals(-1, $redis->pttl('three'));

            $redis->flushall();
        }
    }

    public function testItDeletesKeys()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $redis->set('two', 'mohamed');
            $redis->set('three', 'mohamed');

            $redis->del('one');
            $this->assertNull($redis->get('one'));
            $this->assertNotNull($redis->get('two'));
            $this->assertNotNull($redis->get('three'));

            $redis->del('two', 'three');
            $this->assertNull($redis->get('two'));
            $this->assertNull($redis->get('three'));

            $redis->flushall();
        }
    }

    public function testItChecksForExistence()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $redis->set('two', 'mohamed');

            $this->assertEquals(1, $redis->exists('one'));
            $this->assertEquals(0, $redis->exists('nothing'));
            $this->assertEquals(2, $redis->exists('one', 'two'));
            $this->assertEquals(2, $redis->exists('one', 'two', 'nothing'));

            $redis->flushall();
        }
    }

    public function testItExpiresKeys()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $this->assertEquals(-1, $redis->ttl('one'));
            $this->assertEquals(1, $redis->expire('one', 10));
            $this->assertNotEquals(-1, $redis->ttl('one'));

            $this->assertEquals(0, $redis->expire('nothing', 10));

            $redis->set('two', 'mohamed');
            $this->assertEquals(-1, $redis->ttl('two'));
            $this->assertEquals(1, $redis->pexpire('two', 10));
            $this->assertNotEquals(-1, $redis->pttl('two'));

            $this->assertEquals(0, $redis->pexpire('nothing', 10));

            $redis->flushall();
        }
    }

    public function testItRenamesKeys()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $redis->rename('one', 'two');
            $this->assertNull($redis->get('one'));
            $this->assertSame('mohamed', $redis->get('two'));

            $redis->set('three', 'adam');
            $redis->renamenx('two', 'three');
            $this->assertSame('mohamed', $redis->get('two'));
            $this->assertSame('adam', $redis->get('three'));

            $redis->renamenx('two', 'four');
            $this->assertNull($redis->get('two'));
            $this->assertSame('mohamed', $redis->get('four'));
            $this->assertSame('adam', $redis->get('three'));

            $redis->flushall();
        }
    }

    public function testItAddsMembersToSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', 1, 'mohamed');
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->zadd('set', 2, 'taylor', 3, 'adam');
            $this->assertEquals(3, $redis->zcard('set'));

            $redis->zadd('set', ['jeffrey' => 4, 'matt' => 5]);
            $this->assertEquals(5, $redis->zcard('set'));

            $redis->zadd('set', 'NX', 1, 'beric');
            $this->assertEquals(6, $redis->zcard('set'));

            $redis->zadd('set', 'NX', ['joffrey' => 1]);
            $this->assertEquals(7, $redis->zcard('set'));

            $redis->zadd('set', 'XX', ['ned' => 1]);
            $this->assertEquals(7, $redis->zcard('set'));

            $this->assertEquals(1, $redis->zadd('set', ['sansa' => 10]));
            $this->assertEquals(0, $redis->zadd('set', 'XX', 'CH', ['arya' => 11]));

            $redis->zadd('set', ['mohamed' => 100]);
            $this->assertEquals(100, $redis->zscore('set', 'mohamed'));

            $redis->flushall();
        }
    }

    public function testItCountsMembersInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 10]);

            $this->assertEquals(1, $redis->zcount('set', 1, 5));
            $this->assertEquals(2, $redis->zcount('set', '-inf', '+inf'));
            $this->assertEquals(2, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItIncrementsScoreOfSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 10]);
            $redis->zincrby('set', 2, 'jeffrey');
            $this->assertEquals(3, $redis->zscore('set', 'jeffrey'));

            $redis->flushall();
        }
    }

    public function testItSetsKeyIfNotExists()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('name', 'mohamed');

            $this->assertSame(0, $redis->setnx('name', 'taylor'));
            $this->assertSame('mohamed', $redis->get('name'));

            $this->assertSame(1, $redis->setnx('boss', 'taylor'));
            $this->assertSame('taylor', $redis->get('boss'));

            $redis->flushall();
        }
    }

    public function testItSetsHashFieldIfNotExists()
    {
        foreach ($this->connections() as $redis) {
            $redis->hset('person', 'name', 'mohamed');

            $this->assertSame(0, $redis->hsetnx('person', 'name', 'taylor'));
            $this->assertSame('mohamed', $redis->hget('person', 'name'));

            $this->assertSame(1, $redis->hsetnx('person', 'boss', 'taylor'));
            $this->assertSame('taylor', $redis->hget('person', 'boss'));

            $redis->flushall();
        }
    }

    public function testItCalculatesIntersectionOfSortedSetsAndStores()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set1', ['jeffrey' => 1, 'matt' => 2, 'taylor' => 3]);
            $redis->zadd('set2', ['jeffrey' => 2, 'matt' => 3]);

            $redis->zinterstore('output', ['set1', 'set2']);
            $this->assertEquals(2, $redis->zcard('output'));
            $this->assertEquals(3, $redis->zscore('output', 'jeffrey'));
            $this->assertEquals(5, $redis->zscore('output', 'matt'));

            $redis->zinterstore('output2', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'sum',
            ]);
            $this->assertEquals(7, $redis->zscore('output2', 'jeffrey'));
            $this->assertEquals(12, $redis->zscore('output2', 'matt'));

            $redis->zinterstore('output3', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'min',
            ]);
            $this->assertEquals(3, $redis->zscore('output3', 'jeffrey'));
            $this->assertEquals(6, $redis->zscore('output3', 'matt'));

            $redis->flushall();
        }
    }

    public function testItCalculatesUnionOfSortedSetsAndStores()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set1', ['jeffrey' => 1, 'matt' => 2, 'taylor' => 3]);
            $redis->zadd('set2', ['jeffrey' => 2, 'matt' => 3]);

            $redis->zunionstore('output', ['set1', 'set2']);
            $this->assertEquals(3, $redis->zcard('output'));
            $this->assertEquals(3, $redis->zscore('output', 'jeffrey'));
            $this->assertEquals(5, $redis->zscore('output', 'matt'));
            $this->assertEquals(3, $redis->zscore('output', 'taylor'));

            $redis->zunionstore('output2', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'sum',
            ]);
            $this->assertEquals(7, $redis->zscore('output2', 'jeffrey'));
            $this->assertEquals(12, $redis->zscore('output2', 'matt'));
            $this->assertEquals(9, $redis->zscore('output2', 'taylor'));

            $redis->zunionstore('output3', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'min',
            ]);
            $this->assertEquals(3, $redis->zscore('output3', 'jeffrey'));
            $this->assertEquals(6, $redis->zscore('output3', 'matt'));
            $this->assertEquals(9, $redis->zscore('output3', 'taylor'));

            $redis->flushall();
        }
    }

    public function testItReturnsRangeInSortedSet()
    {
        foreach ($this->connections() as $connector => $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['jeffrey', 'matt'], $redis->zrange('set', 0, 1));
            $this->assertEquals(['jeffrey', 'matt', 'taylor'], $redis->zrange('set', 0, -1));

            if ($connector === 'predis') {
                $this->assertEquals(['jeffrey' => 1, 'matt' => 5], $redis->zrange('set', 0, 1, 'withscores'));
            } else {
                $this->assertEquals(['jeffrey' => 1, 'matt' => 5], $redis->zrange('set', 0, 1, true));
            }

            $redis->flushall();
        }
    }

    public function testItReturnsRevRangeInSortedSet()
    {
        foreach ($this->connections() as $connector => $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['taylor', 'matt'], $redis->ZREVRANGE('set', 0, 1));
            $this->assertEquals(['taylor', 'matt', 'jeffrey'], $redis->ZREVRANGE('set', 0, -1));

            if ($connector === 'predis') {
                $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->ZREVRANGE('set', 0, 1, 'withscores'));
            } else {
                $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->ZREVRANGE('set', 0, 1, true));
            }

            $redis->flushall();
        }
    }

    public function testItReturnsRangeByScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['jeffrey'], $redis->zrangebyscore('set', 0, 3));
            $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->zrangebyscore('set', 0, 11, [
                'withscores' => true,
                'limit' => [
                    'offset' => 1,
                    'count' => 2,
                ],
            ]));
            $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->zrangebyscore('set', 0, 11, [
                'withscores' => true,
                'limit' => [1, 2],
            ]));

            $redis->flushall();
        }
    }

    public function testItReturnsRevRangeByScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['taylor'], $redis->ZREVRANGEBYSCORE('set', 10, 6));
            $this->assertEquals(['matt' => 5, 'jeffrey' => 1], $redis->ZREVRANGEBYSCORE('set', 10, 0, [
                'withscores' => true,
                'limit' => [
                    'offset' => 1,
                    'count' => 2,
                ],
            ]));
            $this->assertEquals(['matt' => 5, 'jeffrey' => 1], $redis->ZREVRANGEBYSCORE('set', 10, 0, [
                'withscores' => true,
                'limit' => [1, 2],
            ]));

            $redis->flushall();
        }
    }

    public function testItReturnsRankInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);

            $this->assertEquals(0, $redis->zrank('set', 'jeffrey'));
            $this->assertEquals(2, $redis->zrank('set', 'taylor'));

            $redis->flushall();
        }
    }

    public function testItReturnsScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);

            $this->assertEquals(1, $redis->zscore('set', 'jeffrey'));
            $this->assertEquals(10, $redis->zscore('set', 'taylor'));

            $redis->flushall();
        }
    }

    public function testItRemovesMembersInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);

            $redis->zrem('set', 'jeffrey');
            $this->assertEquals(3, $redis->zcard('set'));

            $redis->zrem('set', 'matt', 'adam');
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItRemovesMembersByScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);
            $redis->ZREMRANGEBYSCORE('set', 5, '+inf');
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItRemovesMembersByRankInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);
            $redis->ZREMRANGEBYRANK('set', 1, -1);
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItSetsMultipleHashFields()
    {
        foreach ($this->connections() as $redis) {
            $redis->hmset('hash', ['name' => 'mohamed', 'hobby' => 'diving']);
            $this->assertEquals(['name' => 'mohamed', 'hobby' => 'diving'], $redis->hgetall('hash'));

            $redis->hmset('hash2', 'name', 'mohamed', 'hobby', 'diving');
            $this->assertEquals(['name' => 'mohamed', 'hobby' => 'diving'], $redis->hgetall('hash2'));

            $redis->flushall();
        }
    }

    public function testItGetsMultipleHashFields()
    {
        foreach ($this->connections() as $redis) {
            $redis->hmset('hash', ['name' => 'mohamed', 'hobby' => 'diving']);

            $this->assertEquals(['mohamed', 'diving'],
                $redis->hmget('hash', 'name', 'hobby')
            );

            $this->assertEquals(['mohamed', 'diving'],
                $redis->hmget('hash', ['name', 'hobby'])
            );

            $redis->flushall();
        }
    }

    public function testItGetsMultipleKeys()
    {
        $valueSet = ['name' => 'mohamed', 'hobby' => 'diving'];

        foreach ($this->connections() as $redis) {
            $redis->mset($valueSet);

            $this->assertEquals(
                array_values($valueSet),
                $redis->mget(array_keys($valueSet))
            );

            $redis->flushall();
        }
    }

    public function testItFlushes()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('name', 'Till');
            $this->assertSame(1, $redis->exists('name'));

            $redis->flushdb();
            $this->assertSame(0, $redis->exists('name'));
        }
    }

    public function testItFlushesAsynchronous()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('name', 'Till');
            $this->assertSame(1, $redis->exists('name'));

            $redis->flushdb('ASYNC');
            $this->assertSame(0, $redis->exists('name'));
        }
    }

    public function testItRunsEval()
    {
        foreach ($this->connections() as $redis) {
            if ($redis instanceof PhpRedisConnection) {
                // User must decide what needs to be serialized and compressed.
                $redis->eval('redis.call("set", KEYS[1], ARGV[1])', 1, 'name', ...$redis->pack(['mohamed']));
            } else {
                $redis->eval('redis.call("set", KEYS[1], ARGV[1])', 1, 'name', 'mohamed');
            }

            $this->assertSame('mohamed', $redis->get('name'));

            $redis->flushall();
        }
    }

    public function testItRunsPipes()
    {
        foreach ($this->connections() as $redis) {
            $result = $redis->pipeline(function ($pipe) {
                $pipe->set('test:pipeline:1', 1);
                $pipe->get('test:pipeline:1');
                $pipe->set('test:pipeline:2', 2);
                $pipe->get('test:pipeline:2');
            });

            $this->assertCount(4, $result);
            $this->assertEquals(1, $result[1]);
            $this->assertEquals(2, $result[3]);

            $redis->flushall();
        }
    }

    public function testItRunsTransactions()
    {
        foreach ($this->connections() as $redis) {
            $result = $redis->transaction(function ($pipe) {
                $pipe->set('test:transaction:1', 1);
                $pipe->get('test:transaction:1');
                $pipe->set('test:transaction:2', 2);
                $pipe->get('test:transaction:2');
            });

            $this->assertCount(4, $result);
            $this->assertEquals(1, $result[1]);
            $this->assertEquals(2, $result[3]);

            $redis->flushall();
        }
    }

    public function testItRunsRawCommand()
    {
        foreach ($this->connections() as $redis) {
            $redis->executeRaw(['SET', 'test:raw:1', '1']);

            $this->assertEquals(
                1, $redis->executeRaw(['GET', 'test:raw:1'])
            );

            $redis->flushall();
        }
    }

    public function testItDispatchesQueryEvent()
    {
        foreach ($this->connections() as $redis) {
            $redis->setEventDispatcher($events = m::mock(Dispatcher::class));

            $events->shouldReceive('dispatch')->once()->with(m::on(function ($event) {
                $this->assertSame('get', $event->command);
                $this->assertEquals(['foobar'], $event->parameters);
                $this->assertSame('default', $event->connectionName);
                $this->assertInstanceOf(Connection::class, $event->connection);

                return true;
            }));

            $redis->get('foobar');

            $redis->unsetEventDispatcher();
        }
    }

    public function testItPersistsConnection()
    {
        if (PHP_ZTS) {
            $this->markTestSkipped('PhpRedis does not support persistent connections with PHP_ZTS enabled.');
        }

        $this->assertSame(
            'laravel',
            $this->connections()['persistent']->getPersistentID()
        );
    }

    public function testItScansForKeys()
    {
        foreach ($this->connections() as $redis) {
            $initialKeys = ['test:scan:1', 'test:scan:2'];

            foreach ($initialKeys as $k => $key) {
                $redis->set($key, 'test');
                $initialKeys[$k] = $this->getPrefix($redis->client()).$key;
            }

            $iterator = null;

            do {
                [$cursor, $returnedKeys] = $redis->scan($iterator);

                if (! is_array($returnedKeys)) {
                    $returnedKeys = [$returnedKeys];
                }

                foreach ($returnedKeys as $returnedKey) {
                    $this->assertContains($returnedKey, $initialKeys);
                }
            } while ($iterator > 0);

            $redis->flushAll();
        }
    }

    public function testItZscansForKeys()
    {
        foreach ($this->connections() as $redis) {
            $members = [100 => 'test:zscan:1', 200 => 'test:zscan:2'];

            foreach ($members as $score => $member) {
                $redis->zadd('set', $score, $member);
            }

            $iterator = null;
            $result = [];

            do {
                [$iterator, $returnedMembers] = $redis->zscan('set', $iterator);

                if (! is_array($returnedMembers)) {
                    $returnedMembers = [$returnedMembers];
                }

                foreach ($returnedMembers as $member => $score) {
                    $this->assertArrayHasKey((int) $score, $members);
                    $this->assertContains($member, $members);
                }

                $result += $returnedMembers;
            } while ($iterator > 0);

            $this->assertCount(2, $result);

            $iterator = null;
            [$iterator, $returned] = $redis->zscan('set', $iterator, ['match' => 'test:unmatch:*']);
            $this->assertEmpty($returned);

            $iterator = null;
            [$iterator, $returned] = $redis->zscan('set', $iterator, ['count' => 5]);
            $this->assertCount(2, $returned);

            $redis->flushAll();
        }
    }

    public function testItHscansForKeys()
    {
        foreach ($this->connections() as $redis) {
            $fields = ['name' => 'mohamed', 'hobby' => 'diving'];

            foreach ($fields as $field => $value) {
                $redis->hset('hash', $field, $value);
            }

            $iterator = null;
            $result = [];

            do {
                [$iterator, $returnedFields] = $redis->hscan('hash', $iterator);

                foreach ($returnedFields as $field => $value) {
                    $this->assertArrayHasKey($field, $fields);
                    $this->assertContains($value, $fields);
                }

                $result += $returnedFields;
            } while ($iterator > 0);

            $this->assertCount(2, $result);

            $iterator = null;
            [$iterator, $returned] = $redis->hscan('hash', $iterator, ['match' => 'test:unmatch:*']);
            $this->assertEmpty($returned);

            $iterator = null;
            [$iterator, $returned] = $redis->hscan('hash', $iterator, ['count' => 5]);
            $this->assertCount(2, $returned);

            $redis->flushAll();
        }
    }

    public function testItSscansForKeys()
    {
        foreach ($this->connections() as $redis) {
            $members = ['test:sscan:1', 'test:sscan:2'];

            foreach ($members as $member) {
                $redis->sadd('set', $member);
            }

            $iterator = null;
            $result = [];

            do {
                [$iterator, $returnedMembers] = $redis->sscan('set', $iterator);

                foreach ($returnedMembers as $member) {
                    $this->assertContains($member, $members);
                    array_push($result, $member);
                }
            } while ($iterator > 0);

            $this->assertCount(2, $result);

            $iterator = null;
            [$iterator, $returned] = $redis->sscan('set', $iterator, ['match' => 'test:unmatch:*']);
            $this->assertEmpty($returned);

            $iterator = null;
            [$iterator, $returned] = $redis->sscan('set', $iterator, ['count' => 5]);
            $this->assertCount(2, $returned);

            $redis->flushAll();
        }
    }

    public function testItSPopsForKeys()
    {
        foreach ($this->connections() as $redis) {
            $members = ['test:spop:1', 'test:spop:2', 'test:spop:3', 'test:spop:4'];

            foreach ($members as $member) {
                $redis->sadd('set', $member);
            }

            $result = $redis->spop('set');
            $this->assertIsNotArray($result);
            $this->assertContains($result, $members);

            $result = $redis->spop('set', 1);

            $this->assertIsArray($result);
            $this->assertCount(1, $result);

            $result = $redis->spop('set', 2);

            $this->assertIsArray($result);
            $this->assertCount(2, $result);

            $redis->flushAll();
        }
    }

    public function testPhpRedisScanOption()
    {
        foreach ($this->connections() as $redis) {
            if ($redis->client() instanceof Client) {
                continue;
            }

            $iterator = null;

            do {
                $returned = $redis->scan($iterator);

                if ($redis->client()->getOption(Redis::OPT_SCAN) === Redis::SCAN_RETRY) {
                    $this->assertEmpty($returned);
                }
            } while ($iterator > 0);
        }
    }

    private function getPrefix($client)
    {
        if ($client instanceof Redis) {
            return $client->getOption(Redis::OPT_PREFIX);
        }

        return $client->getOptions()->prefix;
    }

    public function testMacroable()
    {
        Connection::macro('foo', function () {
            return 'foo';
        });

        foreach ($this->connections() as $redis) {
            $this->assertSame(
                'foo',
                $redis->foo()
            );
        }
    }

    public function connections()
    {
        $connections = [
            'predis' => $this->redis['predis']->connection(),
            'phpredis' => $this->redis['phpredis']->connection(),
        ];

        $host = env('REDIS_HOST', '127.0.0.1');
        $port = env('REDIS_PORT', 6379);

        $connections[] = (new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'default' => [
                'url' => "redis://user@$host:$port",
                'host' => 'overwrittenByUrl',
                'port' => 'overwrittenByUrl',
                'database' => 5,
                'options' => ['prefix' => 'laravel:'],
                'timeout' => 0.5,
            ],
        ]))->connection();

        $connections['persistent'] = (new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'default' => [
                'host' => $host,
                'port' => $port,
                'database' => 6,
                'options' => ['prefix' => 'laravel:'],
                'timeout' => 0.5,
                'persistent' => true,
                'persistent_id' => 'laravel',
            ],
        ]))->connection();

        $connections[] = (new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'default' => [
                'host' => $host,
                'port' => $port,
                'database' => 7,
                'options' => ['serializer' => Redis::SERIALIZER_JSON],
                'timeout' => 0.5,
            ],
        ]))->connection();

        $connections[] = (new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'default' => [
                'host' => $host,
                'port' => $port,
                'database' => 8,
                'options' => ['scan' => Redis::SCAN_RETRY],
                'timeout' => 0.5,
            ],
        ]))->connection();

        if (defined('Redis::COMPRESSION_LZF')) {
            $connections['compression_lzf'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 9,
                    'options' => [
                        'compression' => Redis::COMPRESSION_LZF,
                        'name' => 'compression_lzf',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();
        }

        if (defined('Redis::COMPRESSION_ZSTD')) {
            $connections['compression_zstd'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 10,
                    'options' => [
                        'compression' => Redis::COMPRESSION_ZSTD,
                        'name' => 'compression_zstd',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();

            $connections['compression_zstd_default'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 11,
                    'options' => [
                        'compression' => Redis::COMPRESSION_ZSTD,
                        'compression_level' => Redis::COMPRESSION_ZSTD_DEFAULT,
                        'name' => 'compression_zstd_default',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();

            $connections['compression_zstd_min'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 12,
                    'options' => [
                        'compression' => Redis::COMPRESSION_ZSTD,
                        'compression_level' => Redis::COMPRESSION_ZSTD_MIN,
                        'name' => 'compression_zstd_min',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();

            $connections['compression_zstd_max'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 13,
                    'options' => [
                        'compression' => Redis::COMPRESSION_ZSTD,
                        'compression_level' => Redis::COMPRESSION_ZSTD_MAX,
                        'name' => 'compression_zstd_max',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();
        }

        if (defined('Redis::COMPRESSION_LZ4')) {
            $connections['compression_lz4'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 14,
                    'options' => [
                        'compression' => Redis::COMPRESSION_LZ4,
                        'name' => 'compression_lz4',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();

            $connections['compression_lz4_default'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 15,
                    'options' => [
                        'compression' => Redis::COMPRESSION_LZ4,
                        'compression_level' => 0,
                        'name' => 'compression_lz4_default',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();

            $connections['compression_lz4_min'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 16,
                    'options' => [
                        'compression' => Redis::COMPRESSION_LZ4,
                        'compression_level' => 1,
                        'name' => 'compression_lz4_min',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();

            $connections['compression_lz4_max'] = (new RedisManager(new Application, 'phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 17,
                    'options' => [
                        'compression' => Redis::COMPRESSION_LZ4,
                        'compression_level' => 12,
                        'name' => 'compression_lz4_max',
                    ],
                    'timeout' => 0.5,
                ],
            ]))->connection();
        }

        return $connections;
    }
}
