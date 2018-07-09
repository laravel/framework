<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\RedisQueue;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

class RedisQueueIntegrationTest extends TestCase
{
    use InteractsWithRedis, InteractsWithTime;

    /**
     * @var RedisQueue
     */
    private $queue;

    public function setUp()
    {
        Carbon::setTestNow(Carbon::now());
        parent::setUp();
        $this->setUpRedis();
    }

    public function tearDown()
    {
        Carbon::setTestNow(null);
        parent::tearDown();
        $this->tearDownRedis();
        m::close();
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testExpiredJobsArePopped($driver)
    {
        $this->setQueue($driver);

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

        $this->assertEquals(1, $this->redis[$driver]->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(3, $this->redis[$driver]->connection()->zcard('queues:default:reserved'));
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testPopProperlyPopsJobOffOfRedis($driver)
    {
        $this->setQueue($driver);

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = $this->currentTime();
        /** @var RedisJob $redisJob */
        $redisJob = $this->queue->pop();
        $after = $this->currentTime();

        $this->assertEquals($job, unserialize(json_decode($redisJob->getRawBody())->data->command));
        $this->assertEquals(1, $redisJob->attempts());
        $this->assertEquals($job, unserialize(json_decode($redisJob->getReservedJob())->data->command));
        $this->assertEquals(1, json_decode($redisJob->getReservedJob())->attempts);
        $this->assertEquals($redisJob->getJobId(), json_decode($redisJob->getReservedJob())->id);

        // Check reserved queue
        $this->assertEquals(1, $this->redis[$driver]->connection()->zcard('queues:default:reserved'));
        $result = $this->redis[$driver]->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['withscores' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before + 60);
        $this->assertGreaterThanOrEqual($score, $after + 60);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testPopProperlyPopsDelayedJobOffOfRedis($driver)
    {
        $this->setQueue($driver);
        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->later(-10, $job);

        // Pop and check it is popped correctly
        $before = $this->currentTime();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = $this->currentTime();

        // Check reserved queue
        $this->assertEquals(1, $this->redis[$driver]->connection()->zcard('queues:default:reserved'));
        $result = $this->redis[$driver]->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['withscores' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before + 60);
        $this->assertGreaterThanOrEqual($score, $after + 60);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testPopPopsDelayedJobOffOfRedisWhenExpireNull($driver)
    {
        $this->queue = new RedisQueue($this->redis[$driver], 'default', null, null);
        $this->queue->setContainer(m::mock(Container::class));

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->later(-10, $job);

        // Pop and check it is popped correctly
        $before = $this->currentTime();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = $this->currentTime();

        // Check reserved queue
        $this->assertEquals(1, $this->redis[$driver]->connection()->zcard('queues:default:reserved'));
        $result = $this->redis[$driver]->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['withscores' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before);
        $this->assertGreaterThanOrEqual($score, $after);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testNotExpireJobsWhenExpireNull($driver)
    {
        $this->queue = new RedisQueue($this->redis[$driver], 'default', null, null);
        $this->queue->setContainer(m::mock(Container::class));

        // Make an expired reserved job
        $failed = new RedisQueueIntegrationTestJob(-20);
        $this->queue->push($failed);
        $beforeFailPop = $this->currentTime();
        $this->queue->pop();
        $afterFailPop = $this->currentTime();

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = $this->currentTime();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = $this->currentTime();

        // Check reserved queue
        $this->assertEquals(2, $this->redis[$driver]->connection()->zcard('queues:default:reserved'));
        $result = $this->redis[$driver]->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['withscores' => true]);

        foreach ($result as $payload => $score) {
            $command = unserialize(json_decode($payload)->data->command);
            $this->assertInstanceOf(RedisQueueIntegrationTestJob::class, $command);
            $this->assertContains($command->i, [10, -20]);
            if ($command->i == 10) {
                $this->assertLessThanOrEqual($score, $before);
                $this->assertGreaterThanOrEqual($score, $after);
            } else {
                $this->assertLessThanOrEqual($score, $beforeFailPop);
                $this->assertGreaterThanOrEqual($score, $afterFailPop);
            }
        }
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testExpireJobsWhenExpireSet($driver)
    {
        $this->queue = new RedisQueue($this->redis[$driver], 'default', null, 30);
        $this->queue->setContainer(m::mock(Container::class));

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = $this->currentTime();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = $this->currentTime();

        // Check reserved queue
        $this->assertEquals(1, $this->redis[$driver]->connection()->zcard('queues:default:reserved'));
        $result = $this->redis[$driver]->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['withscores' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before + 30);
        $this->assertGreaterThanOrEqual($score, $after + 30);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testRelease($driver)
    {
        $this->setQueue($driver);

        //push a job into queue
        $job = new RedisQueueIntegrationTestJob(30);
        $this->queue->push($job);

        //pop and release the job
        /** @var \Illuminate\Queue\Jobs\RedisJob $redisJob */
        $redisJob = $this->queue->pop();
        $before = $this->currentTime();
        $redisJob->release(1000);
        $after = $this->currentTime();

        //check the content of delayed queue
        $this->assertEquals(1, $this->redis[$driver]->connection()->zcard('queues:default:delayed'));

        $results = $this->redis[$driver]->connection()->zrangebyscore('queues:default:delayed', -INF, INF, ['withscores' => true]);

        $payload = array_keys($results)[0];

        $score = $results[$payload];

        $this->assertGreaterThanOrEqual($before + 1000, $score);
        $this->assertLessThanOrEqual($after + 1000, $score);

        $decoded = json_decode($payload);

        $this->assertEquals(1, $decoded->attempts);
        $this->assertEquals($job, unserialize($decoded->data->command));

        //check if the queue has no ready item yet
        $this->assertNull($this->queue->pop());
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testReleaseInThePast($driver)
    {
        $this->setQueue($driver);
        $job = new RedisQueueIntegrationTestJob(30);
        $this->queue->push($job);

        /** @var RedisJob $redisJob */
        $redisJob = $this->queue->pop();
        $redisJob->release(-3);

        $this->assertInstanceOf(RedisJob::class, $this->queue->pop());
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testDelete($driver)
    {
        $this->setQueue($driver);

        $job = new RedisQueueIntegrationTestJob(30);
        $this->queue->push($job);

        /** @var \Illuminate\Queue\Jobs\RedisJob $redisJob */
        $redisJob = $this->queue->pop();

        $redisJob->delete();

        $this->assertEquals(0, $this->redis[$driver]->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(0, $this->redis[$driver]->connection()->zcard('queues:default:reserved'));
        $this->assertEquals(0, $this->redis[$driver]->connection()->llen('queues:default'));

        $this->assertNull($this->queue->pop());
    }

    /**
     * @dataProvider redisDriverProvider
     *
     * @param string $driver
     */
    public function testSize($driver)
    {
        $this->setQueue($driver);
        $this->assertEquals(0, $this->queue->size());
        $this->queue->push(new RedisQueueIntegrationTestJob(1));
        $this->assertEquals(1, $this->queue->size());
        $this->queue->later(60, new RedisQueueIntegrationTestJob(2));
        $this->assertEquals(2, $this->queue->size());
        $this->queue->push(new RedisQueueIntegrationTestJob(3));
        $this->assertEquals(3, $this->queue->size());
        $job = $this->queue->pop();
        $this->assertEquals(3, $this->queue->size());
        $job->delete();
        $this->assertEquals(2, $this->queue->size());
    }

    /**
     * @param string $driver
     */
    private function setQueue($driver)
    {
        $this->queue = new RedisQueue($this->redis[$driver]);
        $this->queue->setContainer(m::mock(Container::class));
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
