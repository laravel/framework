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

    public function testFormatRetryWithArray()
    {
        if (! class_exists(\Predis\Retry\Retry::class)) {
            $this->markTestSkipped('Predis retry support is only available in Predis >= 3.4.0');
        }

        $connector = new TestablePredisConnector;

        $config = [
            'retry' => [
                'retries' => 3,
                'strategy' => 'exponential',
                'base' => 1000,
                'cap' => 5000,
                'with_jitter' => true,
            ],
        ];

        $formatted = $connector->testFormatRetry($config);

        $this->assertInstanceOf(\Predis\Retry\Retry::class, $formatted['retry']);
        $this->assertSame(3, $formatted['retry']->getRetries());
        $this->assertInstanceOf(\Predis\Retry\Strategy\ExponentialBackoff::class, $formatted['retry']->getStrategy());
        $this->assertSame(1000, $formatted['retry']->getStrategy()->getBase());
        $this->assertSame(5000, $formatted['retry']->getStrategy()->getCap());
    }

    public function testFormatRetryWithEqualStrategy()
    {
        if (! class_exists(\Predis\Retry\Retry::class)) {
            $this->markTestSkipped('Predis retry support is only available in Predis >= 3.4.0');
        }

        $connector = new TestablePredisConnector;

        $config = [
            'retry' => [
                'retries' => 5,
                'strategy' => 'equal',
                'backoff' => 2000,
            ],
        ];

        $formatted = $connector->testFormatRetry($config);

        $this->assertInstanceOf(\Predis\Retry\Retry::class, $formatted['retry']);
        $this->assertSame(5, $formatted['retry']->getRetries());
        $this->assertInstanceOf(\Predis\Retry\Strategy\EqualBackoff::class, $formatted['retry']->getStrategy());
        $this->assertSame(2000, $formatted['retry']->getStrategy()->compute(0));
    }

    public function testFormatRetryWithNoStrategy()
    {
        if (! class_exists(\Predis\Retry\Retry::class)) {
            $this->markTestSkipped('Predis retry support is only available in Predis >= 3.4.0');
        }

        $connector = new TestablePredisConnector;

        $config = [
            'retry' => [
                'retries' => 2,
                'strategy' => 'no',
            ],
        ];

        $formatted = $connector->testFormatRetry($config);

        $this->assertInstanceOf(\Predis\Retry\Retry::class, $formatted['retry']);
        $this->assertSame(2, $formatted['retry']->getRetries());
        $this->assertInstanceOf(\Predis\Retry\Strategy\NoBackoff::class, $formatted['retry']->getStrategy());
    }
}

class TestablePredisConnector extends PredisConnector
{
    public function testFormatHost(array $config): array
    {
        return $this->formatHost($config);
    }

    public function testFormatRetry(array $config): array
    {
        return $this->formatRetry($config);
    }
}
