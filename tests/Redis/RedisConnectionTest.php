<?php

namespace Illuminate\Tests\Redis;

use PHPUnit\Framework\TestCase;
use Illuminate\Redis\RedisManager;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

class RedisConnectionTest extends TestCase
{
    use InteractsWithRedis;

    public function setUp()
    {
        parent::setUp();
        $this->setUpRedis();

        if (! isset($this->redis['phpredis'])) {
            $this->markTestSkipped('PhpRedis should be enabled to run the tests');
        }

        if (! isset($this->redis['predis'])) {
            $this->markTestSkipped('Predis should be enabled to run the tests');
        }
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->tearDownRedis();
    }

    /**
     * @test
     */
    public function it_sets_values_with_expiry()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed', 'EX', 5, 'NX');
            $this->assertEquals('mohamed', $redis->get('one'));
            $this->assertNotEquals(-1, $redis->ttl('one'));

            // It doesn't override when NX mode
            $redis->set('one', 'taylor', 'EX', 5, 'NX');
            $this->assertEquals('mohamed', $redis->get('one'));

            // It overrides when XX mode
            $redis->set('one', 'taylor', 'EX', 5, 'XX');
            $this->assertEquals('taylor', $redis->get('one'));

            // It fails if XX mode is on and key doesn't exist
            $redis->set('two', 'taylor', 'PX', 5, 'XX');
            $this->assertNull($redis->get('two'));

            $redis->set('three', 'mohamed', 'PX', 5000);
            $this->assertEquals('mohamed', $redis->get('three'));
            $this->assertNotEquals(-1, $redis->ttl('three'));
            $this->assertNotEquals(-1, $redis->pttl('three'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_deletes_keys()
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

    /**
     * @test
     */
    public function it_checks_for_existence()
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

    /**
     * @test
     */
    public function it_expires_keys()
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

    /**
     * @test
     */
    public function it_renames_keys()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $redis->rename('one', 'two');
            $this->assertNull($redis->get('one'));
            $this->assertEquals('mohamed', $redis->get('two'));

            $redis->set('three', 'adam');
            $redis->renamenx('two', 'three');
            $this->assertEquals('mohamed', $redis->get('two'));
            $this->assertEquals('adam', $redis->get('three'));

            $redis->renamenx('two', 'four');
            $this->assertNull($redis->get('two'));
            $this->assertEquals('mohamed', $redis->get('four'));
            $this->assertEquals('adam', $redis->get('three'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_adds_members_to_sorted_set()
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

    /**
     * @test
     */
    public function it_counts_members_in_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 10]);

            $this->assertEquals(1, $redis->zcount('set', 1, 5));
            $this->assertEquals(2, $redis->zcount('set', '-inf', '+inf'));
            $this->assertEquals(2, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_increments_score_of_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 10]);
            $redis->zincrby('set', 2, 'jeffrey');
            $this->assertEquals(3, $redis->zscore('set', 'jeffrey'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_sets_key_if_not_exists()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('name', 'mohamed');

            $this->assertSame(0, $redis->setnx('name', 'taylor'));
            $this->assertEquals('mohamed', $redis->get('name'));

            $this->assertSame(1, $redis->setnx('boss', 'taylor'));
            $this->assertEquals('taylor', $redis->get('boss'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_sets_hash_field_if_not_exists()
    {
        foreach ($this->connections() as $redis) {
            $redis->hset('person', 'name', 'mohamed');

            $this->assertSame(0, $redis->hsetnx('person', 'name', 'taylor'));
            $this->assertEquals('mohamed', $redis->hget('person', 'name'));

            $this->assertSame(1, $redis->hsetnx('person', 'boss', 'taylor'));
            $this->assertEquals('taylor', $redis->hget('person', 'boss'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_calculates_intersection_of_sorted_sets_and_stores()
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

    /**
     * @test
     */
    public function it_calculates_union_of_sorted_sets_and_stores()
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

    /**
     * @test
     */
    public function it_returns_range_in_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['jeffrey', 'matt'], $redis->zrange('set', 0, 1));
            $this->assertEquals(['jeffrey', 'matt', 'taylor'], $redis->zrange('set', 0, -1));

            $this->assertEquals(['jeffrey' => 1, 'matt' => 5], $redis->zrange('set', 0, 1, 'withscores'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_returns_rev_range_in_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['taylor', 'matt'], $redis->ZREVRANGE('set', 0, 1));
            $this->assertEquals(['taylor', 'matt', 'jeffrey'], $redis->ZREVRANGE('set', 0, -1));

            $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->ZREVRANGE('set', 0, 1, 'withscores'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_returns_range_by_score_in_sorted_set()
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

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_returns_rev_range_by_score_in_sorted_set()
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

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_returns_rank_in_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);

            $this->assertEquals(0, $redis->zrank('set', 'jeffrey'));
            $this->assertEquals(2, $redis->zrank('set', 'taylor'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_returns_score_in_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);

            $this->assertEquals(1, $redis->zscore('set', 'jeffrey'));
            $this->assertEquals(10, $redis->zscore('set', 'taylor'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_removes_members_in_sorted_set()
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

    /**
     * @test
     */
    public function it_removes_members_by_score_in_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);
            $redis->ZREMRANGEBYSCORE('set', 5, '+inf');
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_removes_members_by_rank_in_sorted_set()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);
            $redis->ZREMRANGEBYRANK('set', 1, -1);
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_sets_multiple_hash_fields()
    {
        foreach ($this->connections() as $redis) {
            $redis->hmset('hash', ['name' => 'mohamed', 'hobby' => 'diving']);
            $this->assertEquals(['name' => 'mohamed', 'hobby' => 'diving'], $redis->hgetall('hash'));

            $redis->hmset('hash2', 'name', 'mohamed', 'hobby', 'diving');
            $this->assertEquals(['name' => 'mohamed', 'hobby' => 'diving'], $redis->hgetall('hash2'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_gets_multiple_hash_fields()
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

    /**
     * @test
     */
    public function it_runs_eval()
    {
        foreach ($this->connections() as $redis) {
            $redis->eval('redis.call("set", KEYS[1], ARGV[1])', 1, 'name', 'mohamed');
            $this->assertEquals('mohamed', $redis->get('name'));

            $redis->flushall();
        }
    }

    /**
     * @test
     */
    public function it_runs_pipes()
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

    /**
     * @test
     */
    public function it_runs_transactions()
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

    /**
     * @test
     */
    public function it_runs_raw_command()
    {
        foreach ($this->connections() as $redis) {
            $redis->executeRaw(['SET', 'test:raw:1', '1']);

            $this->assertEquals(
                1, $redis->executeRaw(['GET', 'test:raw:1'])
            );

            $redis->flushall();
        }
    }

    public function connections()
    {
        $connections = [
            $this->redis['predis']->connection(),
            $this->redis['phpredis']->connection(),
        ];

        if (extension_loaded('redis')) {
            $host = getenv('REDIS_HOST') ?: '127.0.0.1';
            $port = getenv('REDIS_PORT') ?: 6379;

            $prefixedPhpredis = new RedisManager('phpredis', [
                'cluster' => false,
                'default' => [
                    'host' => $host,
                    'port' => $port,
                    'database' => 5,
                    'options' => ['prefix' => 'laravel:'],
                    'timeout' => 0.5,
                ],
            ]);

            $connections[] = $prefixedPhpredis->connection();
        }

        return $connections;
    }
}
