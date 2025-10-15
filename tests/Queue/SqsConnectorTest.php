<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\SqsQueue;
use PHPUnit\Framework\TestCase;

class SqsConnectorTest extends TestCase
{
    public function testConnectDerivesEndpointFromPrefixUrl()
    {
        $prefix = 'http://elasticmq.local:9324/000000000000';
        $expectedEndpoint = 'http://elasticmq.local:9324';

        $connector = new SqsConnector();

        $queue = $connector->connect([
            'driver' => 'sqs',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'prefix' => $prefix,
            'queue' => 'emails',
            'region' => 'ap-southeast-2',
        ]);

        $this->assertInstanceOf(SqsQueue::class, $queue);

        /** @var SqsQueue $queue */
        $endpointUri = $queue->getSqs()->getEndpoint();
        $this->assertSame($expectedEndpoint, (string) $endpointUri);
    }
}
