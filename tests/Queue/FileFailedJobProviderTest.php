<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Illuminate\Queue\Failed\FileFailedJobProvider;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class FileFailedJobProviderTest extends TestCase
{
    protected $path;

    protected $provider;

    protected function setUp(): void
    {
        $this->path = @tempnam('tmp', 'file_failed_job_provider_test');
        $this->provider = new FileFailedJobProvider($this->path);
    }

    public function testCanLogFailedJobs()
    {
        [$uuid, $exception] = $this->logFailedJob();

        $failedJobs = $this->provider->all();

        $this->assertEquals([
            (object) [
                'id' => $uuid,
                'connection' => 'connection',
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuid]),
                'exception' => (string) mb_convert_encoding($exception, 'UTF-8'),
                'failed_at' => $failedJobs[0]->failed_at,
                'failed_at_timestamp' => $failedJobs[0]->failed_at_timestamp,
            ],
        ], $failedJobs);
    }

    public function testCanRetrieveAllFailedJobs()
    {
        [$uuidOne, $exceptionOne] = $this->logFailedJob();
        [$uuidTwo, $exceptionTwo] = $this->logFailedJob();

        $failedJobs = $this->provider->all();

        $this->assertEquals([
            (object) [
                'id' => $uuidTwo,
                'connection' => 'connection',
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuidTwo]),
                'exception' => (string) mb_convert_encoding($exceptionTwo, 'UTF-8'),
                'failed_at' => $failedJobs[1]->failed_at,
                'failed_at_timestamp' => $failedJobs[1]->failed_at_timestamp,
            ],
            (object) [
                'id' => $uuidOne,
                'connection' => 'connection',
                'queue' => 'queue',
                'payload' => json_encode(['uuid' => $uuidOne]),
                'exception' => (string) mb_convert_encoding($exceptionOne, 'UTF-8'),
                'failed_at' => $failedJobs[0]->failed_at,
                'failed_at_timestamp' => $failedJobs[0]->failed_at_timestamp,
            ],
        ], $failedJobs);
    }

    public function testCanFindFailedJobs()
    {
        [$uuid, $exception] = $this->logFailedJob();

        $failedJob = $this->provider->find($uuid);

        $this->assertEquals((object) [
            'id' => $uuid,
            'connection' => 'connection',
            'queue' => 'queue',
            'payload' => json_encode(['uuid' => (string) $uuid]),
            'exception' => (string) mb_convert_encoding($exception, 'UTF-8'),
            'failed_at' => $failedJob->failed_at,
            'failed_at_timestamp' => $failedJob->failed_at_timestamp,
        ], $failedJob);
    }

    public function testNullIsReturnedIfJobNotFound()
    {
        $uuid = Str::uuid();

        $failedJob = $this->provider->find($uuid);

        $this->assertNull($failedJob);
    }

    public function testCanForgetFailedJobs()
    {
        [$uuid] = $this->logFailedJob();

        $this->provider->forget($uuid);

        $failedJob = $this->provider->find($uuid);

        $this->assertNull($failedJob);
    }

    public function testCanFlushFailedJobs()
    {
        $this->logFailedJob();
        $this->logFailedJob();

        $this->provider->flush();

        $failedJobs = $this->provider->all();

        $this->assertEmpty($failedJobs);
    }

    public function testCanPruneFailedJobs()
    {
        $this->logFailedJob();
        $this->logFailedJob();

        $this->provider->prune(now()->addDay(1));
        $failedJobs = $this->provider->all();
        $this->assertEmpty($failedJobs);

        $this->logFailedJob();
        $this->logFailedJob();

        $this->provider->prune(now()->subDay(1));
        $failedJobs = $this->provider->all();
        $this->assertCount(2, $failedJobs);
    }

    public function testCanPruneFailedJobsWithRelativeHours()
    {
        $this->logFailedJob();
        $this->logFailedJob();

        $this->provider->prune(now()->addHour(1));
        $failedJobs = $this->provider->all();
        $this->assertEmpty($failedJobs);

        $this->logFailedJob();
        $this->logFailedJob();

        $this->provider->prune(now()->subHour(1));
        $failedJobs = $this->provider->all();
        $this->assertCount(2, $failedJobs);
    }

    public function testEmptyFailedJobsByDefault()
    {
        $failedJobs = $this->provider->all();

        $this->assertEmpty($failedJobs);
    }

    public function testJobsCanBeCounted()
    {
        $this->assertSame(0, $this->provider->count());

        $this->logFailedJob('database', 'default');
        $this->assertSame(1, $this->provider->count());

        $this->logFailedJob('database', 'default');
        $this->logFailedJob('another-connection', 'another-queue');
        $this->assertSame(3, $this->provider->count());
    }

    public function testJobsCanBeCountedByConnection()
    {
        $this->logFailedJob('connection-1', 'default');
        $this->logFailedJob('connection-2', 'default');
        $this->assertSame(1, $this->provider->count('connection-1'));
        $this->assertSame(1, $this->provider->count('connection-2'));

        $this->logFailedJob('connection-1', 'default');
        $this->assertSame(2, $this->provider->count('connection-1'));
        $this->assertSame(1, $this->provider->count('connection-2'));
    }

    public function testJobsCanBeCountedByQueue()
    {
        $this->logFailedJob('database', 'queue-1');
        $this->logFailedJob('database', 'queue-2');
        $this->assertSame(1, $this->provider->count(queue: 'queue-1'));
        $this->assertSame(1, $this->provider->count(queue: 'queue-2'));

        $this->logFailedJob('database', 'queue-1');
        $this->assertSame(2, $this->provider->count(queue: 'queue-1'));
        $this->assertSame(1, $this->provider->count(queue: 'queue-2'));
    }

    public function testJobsCanBeCountedByQueueAndConnection()
    {
        $this->logFailedJob('connection-1', 'queue-99');
        $this->logFailedJob('connection-1', 'queue-99');
        $this->logFailedJob('connection-2', 'queue-99');
        $this->logFailedJob('connection-1', 'queue-1');
        $this->logFailedJob('connection-2', 'queue-1');
        $this->logFailedJob('connection-2', 'queue-1');
        $this->assertSame(2, $this->provider->count('connection-1', 'queue-99'));
        $this->assertSame(1, $this->provider->count('connection-2', 'queue-99'));
        $this->assertSame(1, $this->provider->count('connection-1', 'queue-1'));
        $this->assertSame(2, $this->provider->count('connection-2', 'queue-1'));
    }

    public function logFailedJob($connection = 'connection', $queue = 'queue')
    {
        $uuid = Str::uuid();

        $exception = new Exception("Something went wrong at job [{$uuid}].");

        $this->provider->log($connection, $queue, json_encode(['uuid' => (string) $uuid]), $exception);

        return [(string) $uuid, $exception];
    }
}
