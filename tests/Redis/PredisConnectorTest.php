<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connectors\PredisConnector;
use PHPUnit\Framework\TestCase;

class PredisConnectorTest extends TestCase
{
    public function testFormatHostLeavesConfigUnchangedWhenHostIsMissing()
    {
        $connector = new TestablePredisConnector;

        $config = ['scheme' => 'tls'];

        $this->assertSame($config, $connector->testFormatHost($config));
    }

    public function testFormatHostLeavesConfigUnchangedWhenHostHasNoScheme()
    {
        $connector = new TestablePredisConnector;

        $config = ['host' => '127.0.0.1', 'scheme' => 'tls'];

        $this->assertSame($config, $connector->testFormatHost($config));
    }

    public function testFormatHostUsesHostSchemeWhenSchemeNotConfigured()
    {
        $connector = new TestablePredisConnector;

        $this->assertSame([
            'host' => '127.0.0.1',
            'scheme' => 'tls',
        ], $connector->testFormatHost([
            'host' => 'tls://127.0.0.1',
        ]));
    }

    public function testFormatHostKeepsExplicitSchemeWhenMatchingHostScheme()
    {
        $connector = new TestablePredisConnector;

        $this->assertSame([
            'host' => '127.0.0.1',
            'scheme' => 'tls',
        ], $connector->testFormatHost([
            'host' => 'tls://127.0.0.1',
            'scheme' => 'tls',
        ]));
    }

    public function testFormatHostAcceptsCaseInsensitiveMatchingScheme()
    {
        $connector = new TestablePredisConnector;

        $this->assertSame([
            'host' => '127.0.0.1',
            'scheme' => 'TLS',
        ], $connector->testFormatHost([
            'host' => 'tls://127.0.0.1',
            'scheme' => 'TLS',
        ]));
    }

    public function testFormatHostThrowsOnConflictingScheme()
    {
        $connector = new TestablePredisConnector;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The scheme configured in the Redis host option must match the scheme option.');

        $connector->testFormatHost([
            'host' => 'tcp://127.0.0.1',
            'scheme' => 'tls',
        ]);
    }
}

class TestablePredisConnector extends PredisConnector
{
    public function testFormatHost(array $config): array
    {
        return $this->formatHost($config);
    }
}
