<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\SyncJob;
use PHPUnit\Framework\TestCase;

class QueueJobTest extends TestCase
{
    public function testPayloadReturnsDecodedArray()
    {
        $job = $this->makeJob(json_encode(['job' => 'foo', 'data' => ['bar']]));

        $this->assertSame(['job' => 'foo', 'data' => ['bar']], $job->payload());
    }

    public function testPayloadReturnsEmptyArrayForCorruptJson()
    {
        $job = $this->makeJob('not-valid-json');

        $this->assertSame([], $job->payload());
    }

    public function testPayloadReturnsEmptyArrayForEmptyBody()
    {
        $job = $this->makeJob('');

        $this->assertSame([], $job->payload());
    }

    public function testGetNameDoesNotCrashOnCorruptPayload()
    {
        $job = $this->makeJob('not-valid-json');

        $this->assertNull($job->payload()['job'] ?? null);
    }

    protected function makeJob(string $payload): SyncJob
    {
        return new SyncJob(new Container, $payload, 'sync', 'default');
    }
}
