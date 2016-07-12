<?php

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Queue\DatabaseQueue;
use Carbon\Carbon;
use Illuminate\Container\Container;
use \Symfony\Bridge\PhpUnit\ClockMock;

class QueueDatabaseQueueIntegrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DatabaseQueue The queue instance.
     */
    protected $queue;

    /**
     * @var string The jobs table name.
     */
    protected $table;

    /**
     * @var Container The IOC container.
     */
    protected $container;

    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();

        $db->setAsGlobal();

        $this->table = 'jobs';

        $this->queue = new DatabaseQueue($this->connection(), $this->table);

        $this->container = $this->createMock(Container::class);

        $this->queue->setContainer($this->container);

        $this->createSchema();

        ClockMock::register(DatabaseQueue::class);

        ClockMock::withClockMock(true);
    }

    /**
     * Gets a mock queue instance.
     *
     * @param callable $callback
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    public function getMockQueue($callback = null)
    {
        $builder = $this->getMockBuilder(DatabaseQueue::class)->setConstructorArgs([$this->connection(), $this->table]);

        if (is_callable($callback)) {
            $callback($builder);
        }

        $queue = $builder->getMock();

        $queue->setContainer($this->container);

        return $queue;
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue');
            $table->longText('payload');
            $table->tinyInteger('attempts')->unsigned();
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
            $table->index(['queue', 'reserved_at']);
        });
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('jobs');

        ClockMock::withClockMock(false);
    }

    /**
     * Test that jobs that are not reserved and have an available_at value less then now, are popped.
     */
    public function testAvailableAndUnReservedJobsArePopped()
    {
        $this->connection()
            ->table('jobs')
            ->insert([
                'id' => 1,
                'queue' => $mock_queue_name = 'mock_queue_name',
                'payload' => 'mock_payload',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => Carbon::now()->subSeconds(1)->getTimestamp(),
                'created_at' => Carbon::now()->getTimestamp(),
            ]);

        $popped_job = $this->queue->pop($mock_queue_name);

        $this->assertNotNull($popped_job);
    }

    /**
     * Test that when jobs are popped, the attempts attribute is incremented.
     */
    public function testPoppedJobsIncrementAttempts()
    {
        $job = [
            'id' => 1,
            'queue' => 'mock_queue_name',
            'payload' => 'mock_payload',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => Carbon::now()->subSeconds(1)->getTimestamp(),
            'created_at' => Carbon::now()->getTimestamp(),
        ];

        $this->connection()->table('jobs')->insert($job);

        $popped_job = $this->queue->pop($job['queue']);

        $database_record = $this->connection()->table('jobs')->find($job['id']);

        $this->assertEquals(1, $database_record->attempts, 'Job attempts not updated in the database!');
        $this->assertEquals(1, $popped_job->attempts(), 'The "attempts" attribute of the Job object was not updated by pop!');
    }

    /**
     * Test that jobs that are not reserved and have an available_at value in the future, are not popped.
     */
    public function testUnavailableJobsAreNotPopped()
    {
        $this->connection()
            ->table('jobs')
            ->insert([
                'id' => 1,
                'queue' => $mock_queue_name = 'mock_queue_name',
                'payload' => 'mock_payload',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => Carbon::now()->addSeconds(60)->getTimestamp(),
                'created_at' => Carbon::now()->getTimestamp(),
            ]);

        $popped_job = $this->queue->pop($mock_queue_name);

        $this->assertNull($popped_job);
    }

    /**
     * Test that jobs that are reserved and have expired are popped.
     */
    public function testThatReservedAndExpiredJobsArePopped()
    {
        $this->connection()
            ->table('jobs')
            ->insert([
                'id' => 1,
                'queue' => $mock_queue_name = 'mock_queue_name',
                'payload' => 'mock_payload',
                'attempts' => 0,
                'reserved_at' => Carbon::now()->subDay()->getTimestamp(),
                'available_at' => Carbon::now()->addDay()->getTimestamp(),
                'created_at' => Carbon::now()->getTimestamp(),
            ]);

        $popped_job = $this->queue->pop($mock_queue_name);

        $this->assertNotNull($popped_job);
    }

    /**
     * Test that jobs that are reserved and not expired and available are not popped.
     */
    public function testThatReservedJobsAreNotPopped()
    {
        $this->connection()
            ->table('jobs')
            ->insert([
                'id' => 1,
                'queue' => $mock_queue_name = 'mock_queue_name',
                'payload' => 'mock_payload',
                'attempts' => 0,
                'reserved_at' => Carbon::now()->addDay()->getTimestamp(),
                'available_at' => Carbon::now()->subDay()->getTimestamp(),
                'created_at' => Carbon::now()->getTimestamp(),
            ]);

        $popped_job = $this->queue->pop($mock_queue_name);

        $this->assertNull($popped_job);
    }

    /**
     * Test that pop returns null when there are no jobs in a queue.
     */
    public function testPopWhenQueueIsEmpty()
    {
        $mock_queue_name = 'mock_queue_name';

        $popped_job = $this->queue->pop($mock_queue_name);

        $this->assertNull($popped_job);
    }

    /**
     * Test that when a worker is attempts to reserve an already reserved job, no update is performed.
     */
    public function testReservedJobDoesNotGetReservedAgain()
    {
        $job = [
            'id' => 1,
            'queue' => 'mock_queue_name',
            'payload' => 'mock_payload',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => Carbon::now()->subDay()->getTimestamp(),
            'created_at' => Carbon::now()->getTimestamp(),
        ];


        $reserved_job = [
            'id' => $job['id'],
            'queue' => $job['queue'],
            'payload' => $job['payload'],
            'attempts' => $job['attempts'],
            'reserved_at' =>  Carbon::now()->addDay()->getTimestamp(),
            'available_at' => $job['available_at'],
            'created_at' => $job['created_at'],
        ];

        $mock_queue = $this->getMockQueue(function (PHPUnit_Framework_MockObject_MockBuilder $builder) {
            $builder->setMethods(['getNextAvailableJob']);
        });

        $mock_queue
            ->expects($this->exactly(2))
            ->method('getNextAvailableJob')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls((object) $job);

        $this->connection()->table('jobs')->insert($reserved_job);

        $popped_job = $mock_queue->pop($job['queue']);

        $this->assertNull($popped_job);
    }

    /**
     * Tests that an exception is thrown if the pop method fails to reserve a job an excessive number of times.
     *
     * @group time-sensitive
     * @expectedException Illuminate\Queue\ExcessiveBlockException
     */
    public function testExceptionIsThrownWhenPopAttemptsBecomeExcessive()
    {
        $mock_queue = $this->getMockQueue(function (PHPUnit_Framework_MockObject_MockBuilder $builder) {
            $builder->setMethods(['getNextAvailableJob', 'markJobAsReserved']);
        });

        $mock_queue
            ->expects($this->atLeastOnce())
            ->method('getNextAvailableJob')
            ->withAnyParameters()
            ->willReturn('mock_job');

        $mock_queue
            ->expects($this->atLeastOnce())
            ->method('markJobAsReserved')
            ->withAnyParameters()
            ->willReturn(false);

        $mock_queue->pop('mock_queue');
    }
}
