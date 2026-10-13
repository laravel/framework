<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Jobs\InspectedJob;
use PHPUnit\Framework\TestCase;

class InspectedJobTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);

        parent::tearDown();
    }

    public function testFromPayloadCreatedAtUsesDefaultTimezone()
    {
        date_default_timezone_set('Europe/Warsaw');

        $timestamp = 1760370000;

        $job = InspectedJob::fromPayload(json_encode([
            'uuid' => 'abc',
            'displayName' => 'App\Jobs\Demo',
            'createdAt' => $timestamp,
        ]));

        $this->assertSame('Europe/Warsaw', $job->createdAt->getTimezone()->getName());
        $this->assertSame($timestamp, $job->createdAt->getTimestamp());
    }

    public function testFromPayloadCreatedAtIsNullWhenMissing()
    {
        $job = InspectedJob::fromPayload(json_encode(['uuid' => 'abc']));

        $this->assertNull($job->createdAt);
    }
}
