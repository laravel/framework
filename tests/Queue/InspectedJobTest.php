<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Jobs\InspectedJob;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class InspectedJobTest extends TestCase
{
    public function testFromPayloadExposesRawPayload()
    {
        $payload = json_encode([
            'uuid' => 'abc-123',
            'displayName' => 'App\\Jobs\\SendEmail',
            'attempts' => 0,
            'context' => ['tenant' => 'acme'],
        ]);

        $job = InspectedJob::fromPayload($payload);

        $this->assertSame(['tenant' => 'acme'], $job->payload['context']);
    }
}
