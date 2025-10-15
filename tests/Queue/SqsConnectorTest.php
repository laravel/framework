<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\SqsQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SqsConnectorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testConnectDerivesEndpointFromPrefixUrl()
    {
        $prefix = 'http://elasticmq.local:9324/000000000000';
        $expectedEndpoint = 'http://elasticmq.local:9324';

        $overloaded = m::mock('overload:Aws\\Sqs\\SqsClient');
        $overloaded->shouldReceive('__construct')->once()->with(m::on(function ($cfg) use ($expectedEndpoint) {
            return isset($cfg['endpoint']) && $cfg['endpoint'] === $expectedEndpoint
                && isset($cfg['region']) && is_string($cfg['region'])
                && isset($cfg['version']) && $cfg['version'] === 'latest';
        }));

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
    }
}
