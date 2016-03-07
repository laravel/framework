<?php

use Illuminate\Container\Container;
use Illuminate\Redis\Database;
use Mockery as m;

class RedisQueueIntegrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Database
     */
    private $redis;

    public function setUp()
    {
        parent::setUp();
        $this->redis = new Database([
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 5,
            ]]);
        $this->redis->connection()->flushdb();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->redis->connection()->flushdb();
    }

    public function testExpiredJobsArePopped()
    {
        $queue = new \Illuminate\Queue\RedisQueue($this->redis);
        $queue->setContainer(m::mock(Container::class));

        $jobs = [
            new RedisQueueIntegrationTestJob(0),
            new RedisQueueIntegrationTestJob(1),
            new RedisQueueIntegrationTestJob(2),
            new RedisQueueIntegrationTestJob(3),
        ];

        $queue->later(1000, $jobs[0]);
        $queue->later(-200, $jobs[1]);
        $queue->later(-300, $jobs[2]);
        $queue->later(-100, $jobs[3]);

        $this->assertEquals($jobs[2], unserialize(json_decode($queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[1], unserialize(json_decode($queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[3], unserialize(json_decode($queue->pop()->getRawBody())->data->command));
        $this->assertNull($queue->pop());

        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(3, $this->redis->connection()->zcard('queues:default:reserved'));
        $this->redis->connection()->flushdb();
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
        var_dump($this->i . ' handled');
    }
}
