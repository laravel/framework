<?php

use Illuminate\Container\Container;
use Illuminate\Queue\RedisQueue;
use Illuminate\Redis\Database;
use Mockery as m;

class RedisQueueIntegrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Database
     */
    private $redis;

    /**
     * @var RedisQueue
     */
    private $queue;

    public function setUp()
    {
        parent::setUp();
        $this->redis = new Database([
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 5,
            ],
        ]);
        $this->redis->connection()->flushdb();

        $this->queue = new RedisQueue($this->redis);
        $this->queue->setContainer(m::mock(Container::class));
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
        $this->redis->connection()->flushdb();
    }

    public function testExpiredJobsArePopped()
    {
        $jobs = [
            new RedisQueueIntegrationTestJob(0),
            new RedisQueueIntegrationTestJob(1),
            new RedisQueueIntegrationTestJob(2),
            new RedisQueueIntegrationTestJob(3),
        ];

        $this->queue->later(1000, $jobs[0]);
        $this->queue->later(-200, $jobs[1]);
        $this->queue->later(-300, $jobs[2]);
        $this->queue->later(-100, $jobs[3]);

        $this->assertEquals($jobs[2], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[1], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[3], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertNull($this->queue->pop());

        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(3, $this->redis->connection()->zcard('queues:default:reserved'));
    }

    public function testPopProperlyPopsJobOffOfRedis()
    {
        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = time();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = time();

        // Check reserved queue
        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertGreaterThanOrEqual($score, $before + 60);
        $this->assertLessThanOrEqual($score, $after + 60);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    public function testNotExpireJobsWhenExpireNull()
    {
        $this->queue->setExpire(null);

        // Make an expired reserved job
        $failed = new RedisQueueIntegrationTestJob(-20);
        $this->queue->push($failed);
        $beforeFailPop = time();
        $this->queue->pop();
        $afterFailPop = time();

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = time();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = time();

        // Check reserved queue
        $this->assertEquals(2, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);

        $reservedJob = array_keys($result)[1];
        $score = $result[$reservedJob];
        $this->assertGreaterThanOrEqual($score, $before);
        $this->assertLessThanOrEqual($score, $after);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));

        $reservedFailedJob = array_keys($result)[0];
        $failedScore = $result[$reservedFailedJob];
        $this->assertGreaterThanOrEqual($failedScore, $beforeFailPop);
        $this->assertLessThanOrEqual($failedScore, $afterFailPop);
        $this->assertEquals($failed, unserialize(json_decode($reservedFailedJob)->data->command));
    }

    public function testExpireJobsWhenExpireSet()
    {
        $this->queue->setExpire(30);
        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = time();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = time();

        // Check reserved queue
        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertGreaterThanOrEqual($score, $before + 30);
        $this->assertLessThanOrEqual($score, $after + 30);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }
}

class RedisQueueIntegrationTestJob
{
    public $i;

    public function __construct($i)
    {
        $this->i = $i;
    }

    public function handle()
    {
    }
}
