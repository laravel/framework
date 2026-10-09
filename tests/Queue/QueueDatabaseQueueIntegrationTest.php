<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\CountCrashesAsExceptions;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\Jobs\InspectedJob;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QueueDatabaseQueueIntegrationTest extends TestCase
{
    /**
     * @var \Illuminate\Queue\DatabaseQueue
     */
    protected $queue;

    /**
     * @var string The jobs table name.
     */
    protected $table;

    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();

        $db->setAsGlobal();

        $this->table = 'jobs';

        $this->queue = new DatabaseQueue($this->connection(), $this->table);

        $this->container = new Container;

        $this->container->instance('events', new Dispatcher($this->container));

        $this->queue->setContainer($this->container);

        $this->createSchema();
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
     * @return \Illuminate\Database\Schema\Builder
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
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Str::createUuidsNormally();
        $this->schema()->drop('jobs');
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
                'available_at' => Carbon::now()->subSecond()->getTimestamp(),
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
            'available_at' => Carbon::now()->subSecond()->getTimestamp(),
            'created_at' => Carbon::now()->getTimestamp(),
        ];

        $this->connection()->table('jobs')->insert($job);

        $popped_job = $this->queue->pop($job['queue']);

        $database_record = $this->connection()->table('jobs')->find($job['id']);

        $this->assertEquals(1, $database_record->attempts, 'Job attempts not updated in the database!');
        $this->assertEquals(1, $popped_job->attempts(), 'The "attempts" attribute of the Job object was not updated by pop!');
    }

    /**
     * Test that the queue can be cleared.
     */
    public function testThatQueueCanBeCleared()
    {
        $this->connection()
            ->table('jobs')
            ->insert([[
                'id' => 1,
                'queue' => $mock_queue_name = 'mock_queue_name',
                'payload' => 'mock_payload',
                'attempts' => 0,
                'reserved_at' => Carbon::now()->addDay()->getTimestamp(),
                'available_at' => Carbon::now()->subDay()->getTimestamp(),
                'created_at' => Carbon::now()->getTimestamp(),
            ], [
                'id' => 2,
                'queue' => $mock_queue_name,
                'payload' => 'mock_payload 2',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => Carbon::now()->subSecond()->getTimestamp(),
                'created_at' => Carbon::now()->getTimestamp(),
            ]]);

        $this->assertEquals(2, $this->queue->clear($mock_queue_name));
        $this->assertEquals(0, $this->queue->size());
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
                'available_at' => Carbon::now()->addMinute()->getTimestamp(),
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

    public function testCustomPayloadIsExposedOnInspectedJob()
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['context' => ['tenant' => 'acme']];
        });

        $this->queue->push('MyJob', []);

        $job = $this->queue->pendingJobs()->first();

        $this->assertSame(['tenant' => 'acme'], $job->payload['context']);

        Queue::createPayloadUsing(null);
    }

    public function testJobPayloadIsAvailableOnEvents()
    {
        $jobQueueingEvent = null;
        $jobQueuedEvent = null;
        Str::createUuidsUsingSequence([
            'expected-job-uuid',
        ]);
        $this->container['events']->listen(function (JobQueueing $e) use (&$jobQueueingEvent) {
            $jobQueueingEvent = $e;
        });
        $this->container['events']->listen(function (JobQueued $e) use (&$jobQueuedEvent) {
            $jobQueuedEvent = $e;
        });

        $this->queue->push('MyJob', [
            'laravel' => 'Framework',
        ]);

        $this->assertIsArray($jobQueueingEvent->payload());
        $this->assertSame('expected-job-uuid', $jobQueueingEvent->payload()['uuid']);

        $this->assertIsArray($jobQueuedEvent->payload());
        $this->assertSame('expected-job-uuid', $jobQueuedEvent->payload()['uuid']);
    }

    #[DataProvider('pushJobsDataProvider')]
    public function testPushProperlyPushesJobOntoDatabase($uuid, $job, $displayNameStartsWith, $jobStartsWith)
    {
        Str::createUuidsUsing(fn () => $uuid);

        try {
            $this->queue->push($job, ['data']);
        } finally {
            Str::createUuidsNormally();
        }

        $row = $this->rows()->sole();
        $payload = json_decode($row->payload, true);

        $this->assertSame($uuid, $payload['uuid']);
        $this->assertStringContainsString($displayNameStartsWith, $payload['displayName']);
        $this->assertStringContainsString($jobStartsWith, $payload['job']);

        $this->assertSame('default', $row->queue);
        $this->assertEquals(0, $row->attempts);
        $this->assertNull($row->reserved_at);
        $this->assertIsNumeric($row->available_at);
    }

    public static function pushJobsDataProvider()
    {
        $uuid = Str::uuid()->toString();

        return [
            [$uuid, new MyTestJob, 'MyTestJob', 'CallQueuedHandler'],
            [$uuid, fn () => 0, 'Closure', 'CallQueuedHandler'],
            [$uuid, 'foo', 'foo', 'foo'],
        ];
    }

    public function testDelayedPushProperlyPushesJobOntoDatabase()
    {
        Carbon::setTestNow($time = Carbon::now());

        $this->queue->later(10, 'foo', ['data']);

        $row = $this->rows()->sole();
        $payload = json_decode($row->payload, true);

        $this->assertSame('default', $row->queue);
        $this->assertSame('foo', $payload['job']);
        $this->assertSame(['data'], $payload['data']);
        $this->assertSame(10, $payload['delay']);
        $this->assertEquals(0, $row->attempts);
        $this->assertNull($row->reserved_at);
        $this->assertEquals($time->getTimestamp() + 10, $row->available_at);
    }

    public function testPushIncludesBatchIdInPayloadForBatchableJob()
    {
        $this->queue->push((new MyBatchableJob)->withBatchId('test-batch-id'), ['data']);

        $payload = json_decode($this->rows()->sole()->payload, true);

        $this->assertSame('test-batch-id', $payload['data']['batchId']);
    }

    public function testPushUsesPropertiesDeclaredOnChildClassOverInheritedAttributes()
    {
        $this->queue->push(new ChildJobWithPropertiesOverridingParentAttributes, ['data']);

        $payload = json_decode($this->rows()->sole()->payload, true);

        $this->assertSame(1700, $payload['timeout']);
        $this->assertSame(7, $payload['maxTries']);
        $this->assertSame('13', $payload['backoff']);
        $this->assertSame(11, $payload['maxExceptions']);
        $this->assertFalse($payload['failOnTimeout']);
        $this->assertFalse($payload['countCrashesAsExceptions']);
    }

    public function testPushStillUsesAttributesDeclaredOnSameClassOverDefaultProperties()
    {
        $this->queue->push(new JobWithAttributesAndDefaultProperties, ['data']);

        $payload = json_decode($this->rows()->sole()->payload, true);

        $this->assertSame(40, $payload['timeout']);
        $this->assertSame(2, $payload['maxTries']);
        $this->assertSame('9', $payload['backoff']);
        $this->assertSame(3, $payload['maxExceptions']);
        $this->assertTrue($payload['failOnTimeout']);
        $this->assertTrue($payload['countCrashesAsExceptions']);
    }

    public function testBulkBatchPushesOntoDatabase()
    {
        Str::createUuidsUsingSequence(['uuid-1', 'uuid-2']);
        Carbon::setTestNow($time = Carbon::now());

        $this->connection()->setEventDispatcher(new Dispatcher);
        $inserts = 0;
        $this->connection()->listen(function (QueryExecuted $query) use (&$inserts) {
            $inserts += str_starts_with($query->sql, 'insert') ? 1 : 0;
        });

        try {
            $this->queue->bulk(['foo', 'bar'], ['data'], 'queue');
        } finally {
            Str::createUuidsNormally();
        }

        $rows = $this->rows()->values();

        $this->assertSame(1, $inserts);
        $this->assertCount(2, $rows);

        foreach ([['foo', 'uuid-1'], ['bar', 'uuid-2']] as $index => [$name, $uuid]) {
            $payload = json_decode($rows[$index]->payload, true);

            $this->assertSame('queue', $rows[$index]->queue);
            $this->assertSame($uuid, $payload['uuid']);
            $this->assertSame($name, $payload['job']);
            $this->assertSame(['data'], $payload['data']);
            $this->assertEquals(0, $rows[$index]->attempts);
            $this->assertNull($rows[$index]->reserved_at);
            $this->assertEquals($time->getTimestamp(), $rows[$index]->available_at);
            $this->assertEquals($time->getTimestamp(), $rows[$index]->created_at);
        }
    }

    public function testDelayAttributeIsRespectedWhenBulkPushing()
    {
        Carbon::setTestNow($time = Carbon::now());

        $this->queue->bulk([new JobWithDelayAttribute, new MyTestJob], ['data'], 'queue');

        $rows = $this->rows()->values();

        $this->assertEquals($time->getTimestamp() + 15, $rows[0]->available_at);
        $this->assertEquals($time->getTimestamp(), $rows[1]->available_at);
    }

    public function testBulkDefersAfterCommitJobsUntilTheTransactionCommits()
    {
        $this->connection()->setTransactionManager($manager = new DatabaseTransactionsManager);
        $this->container->instance('db.transactions', $manager);

        $insertedDuringTransaction = null;

        $this->connection()->transaction(function () use (&$insertedDuringTransaction) {
            $this->queue->bulk([new AfterCommitJob]);

            $insertedDuringTransaction = $this->rows()->count();
        });

        $this->assertSame(0, $insertedDuringTransaction);
        $this->assertCount(1, $this->rows());
    }

    public function testPendingJobs()
    {
        $this->seedJobsInEveryState();

        $this->assertSame(['default-pending'], $this->names($this->queue->pendingJobs()));
        $this->assertSame(['emails-pending'], $this->names($this->queue->pendingJobs('emails')));

        $job = $this->queue->pendingJobs()->sole();
        $this->assertInstanceOf(InspectedJob::class, $job);
        $this->assertSame('default-pending-uuid', $job->uuid);
        $this->assertSame(0, $job->attempts);
        $this->assertSame('default', $job->queue);
        $this->assertSame(1000000, $job->createdAt->getTimestamp());
    }

    public function testDelayedJobs()
    {
        $this->seedJobsInEveryState();

        $this->assertSame(['default-delayed'], $this->names($this->queue->delayedJobs()));
        $this->assertSame(['emails-delayed'], $this->names($this->queue->delayedJobs('emails')));

        $job = $this->queue->delayedJobs()->sole();
        $this->assertInstanceOf(InspectedJob::class, $job);
        $this->assertSame('default-delayed-uuid', $job->uuid);
        $this->assertSame(0, $job->attempts);
        $this->assertSame('default', $job->queue);
        $this->assertSame(1000000, $job->createdAt->getTimestamp());
    }

    public function testReservedJobs()
    {
        $this->seedJobsInEveryState();

        $this->assertSame(['default-reserved'], $this->names($this->queue->reservedJobs()));
        $this->assertSame(['emails-reserved'], $this->names($this->queue->reservedJobs('emails')));

        $job = $this->queue->reservedJobs()->sole();
        $this->assertInstanceOf(InspectedJob::class, $job);
        $this->assertSame('default-reserved-uuid', $job->uuid);
        $this->assertSame(1, $job->attempts);
        $this->assertSame('default', $job->queue);
        $this->assertSame(1000000, $job->createdAt->getTimestamp());
    }

    public function testAllPendingJobs()
    {
        $this->seedJobsInEveryState();

        $jobs = $this->queue->allPendingJobs();

        $this->assertSame(['default-pending', 'emails-pending'], $this->names($jobs));
        $this->assertSame(['default', 'emails'], $jobs->map->queue->all());
    }

    public function testAllDelayedJobs()
    {
        $this->seedJobsInEveryState();

        $jobs = $this->queue->allDelayedJobs();

        $this->assertSame(['default-delayed', 'emails-delayed'], $this->names($jobs));
        $this->assertSame(['default', 'emails'], $jobs->map->queue->all());
    }

    public function testAllReservedJobs()
    {
        $this->seedJobsInEveryState();

        $jobs = $this->queue->allReservedJobs();

        $this->assertSame(['default-reserved', 'emails-reserved'], $this->names($jobs));
        $this->assertSame(['default', 'emails'], $jobs->map->queue->all());
        $this->assertSame([1, 2], $jobs->map->attempts->all());
    }

    public function testTotalSize()
    {
        $this->seedJobsInEveryState();

        $this->assertSame(6, $this->queue->totalSize());
    }

    public function testTotalPendingSize()
    {
        $this->seedJobsInEveryState();

        $this->assertSame(2, $this->queue->totalPendingSize());
    }

    public function testTotalDelayedSize()
    {
        $this->seedJobsInEveryState();

        $this->assertSame(2, $this->queue->totalDelayedSize());
    }

    public function testTotalReservedSize()
    {
        $this->seedJobsInEveryState();

        $this->assertSame(2, $this->queue->totalReservedSize());
    }

    /**
     * Seed a pending (due right now), delayed (due a second from now) and reserved job on two queues.
     */
    protected function seedJobsInEveryState(): void
    {
        Carbon::setTestNow($now = Carbon::now());

        foreach (['default' => 1, 'emails' => 2] as $queue => $attempts) {
            $this->seedJob($queue, "$queue-pending", availableAt: $now->getTimestamp());
            $this->seedJob($queue, "$queue-delayed", availableAt: $now->getTimestamp() + 1);
            $this->seedJob($queue, "$queue-reserved", availableAt: $now->getTimestamp() - 10, reservedAt: $now->getTimestamp(), attempts: $attempts);
        }
    }

    protected function seedJob(string $queue, string $name, int $availableAt, ?int $reservedAt = null, int $attempts = 0): void
    {
        $this->connection()->table($this->table)->insert([
            'queue' => $queue,
            'payload' => json_encode(['uuid' => "$name-uuid", 'displayName' => $name, 'job' => 'foo', 'data' => [], 'createdAt' => 1000000]),
            'attempts' => $attempts,
            'reserved_at' => $reservedAt,
            'available_at' => $availableAt,
            'created_at' => 1000000,
        ]);
    }

    protected function rows()
    {
        return $this->connection()->table($this->table)->orderBy('id')->get();
    }

    protected function names($jobs): array
    {
        return $jobs->map->name->all();
    }
}

class MyTestJob
{
    public function handle()
    {
        // ...
    }
}

class MyBatchableJob
{
    use Batchable;
}

#[Delay(15)]
class JobWithDelayAttribute
{
}

class AfterCommitJob implements ShouldQueue
{
    public $afterCommit = true;
}

#[Backoff(9)]
#[CountCrashesAsExceptions]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[Timeout(40)]
#[Tries(2)]
abstract class ParentJobWithAttributes implements ShouldQueue
{
}

class ChildJobWithPropertiesOverridingParentAttributes extends ParentJobWithAttributes
{
    public $backoff = 13;

    public $countCrashesAsExceptions = false;

    public $failOnTimeout = false;

    public $maxExceptions = 11;

    public $timeout = 1700;

    public $tries = 7;
}

#[Backoff(9)]
#[CountCrashesAsExceptions]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[Timeout(40)]
#[Tries(2)]
class JobWithAttributesAndDefaultProperties implements ShouldQueue
{
    public $backoff = 13;

    public $countCrashesAsExceptions = false;

    public $failOnTimeout = false;

    public $maxExceptions = 11;

    public $timeout = 1700;

    public $tries = 7;
}
