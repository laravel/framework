<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connectors\PredisConnector;
use PHPUnit\Framework\TestCase;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\EqualBackoff;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\Retry\Strategy\NoBackoff;

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

    public function testFormatRetryCreatesRetryFromScalarOptions()
    {
        $this->requiresPredisRetrySupport();

        $connector = new TestablePredisConnector;

        $config = $connector->testFormatRetry([
            'host' => '127.0.0.1',
            'retry' => [
                'max_retries' => 3,
                'backoff_algorithm' => 'exponential',
                'backoff_base' => 250000,
                'backoff_cap' => 2000000,
            ],
        ]);

        $this->assertInstanceOf(Retry::class, $config['retry']);
        $this->assertSame(3, $config['retry']->getRetries());

        $this->assertInstanceOf(ExponentialBackoff::class, $config['retry']->getStrategy());
        $this->assertSame(250000, $config['retry']->getStrategy()->getBase());
        $this->assertSame(2000000, $config['retry']->getStrategy()->getCap());
    }

    public function testFormatRetryDoesNotTreatPhpRedisBackoffOptionsAsPredisRetryConfig()
    {
        $connector = new TestablePredisConnector;

        $config = [
            'host' => '127.0.0.1',
            'max_retries' => 3,
            'backoff_algorithm' => 'decorrelated_jitter',
            'backoff_base' => 100,
            'backoff_cap' => 1000,
        ];

        $this->assertSame($config, $connector->testFormatRetry($config));
    }

    public function testFormatRetryUsesConstantBackoff()
    {
        $this->requiresPredisRetrySupport();

        $connector = new TestablePredisConnector;

        $config = $connector->testFormatRetry([
            'retry' => [
                'max_retries' => 3,
                'backoff_algorithm' => 'constant',
                'backoff_base' => 250000,
            ],
        ]);

        $this->assertInstanceOf(EqualBackoff::class, $config['retry']->getStrategy());
        $this->assertSame(250000, $config['retry']->getStrategy()->compute(0));
    }

    public function testFormatRetryUsesNoBackoff()
    {
        $this->requiresPredisRetrySupport();

        $connector = new TestablePredisConnector;

        $config = $connector->testFormatRetry([
            'retry' => [
                'max_retries' => 3,
                'backoff_algorithm' => 'none',
            ],
        ]);

        $this->assertInstanceOf(NoBackoff::class, $config['retry']->getStrategy());
        $this->assertSame(0, $config['retry']->getStrategy()->compute(0));
    }

    public function testFormatRetryKeepsExplicitRetryInstance()
    {
        $this->requiresPredisRetrySupport();

        $connector = new TestablePredisConnector;
        $retry = new Retry(new NoBackoff, 3);

        $config = $connector->testFormatRetry([
            'retry' => $retry,
            'max_retries' => 5,
            'backoff_algorithm' => 'exponential',
        ]);

        $this->assertSame($retry, $config['retry']);
        $this->assertSame(5, $config['max_retries']);
    }

    public function testFormatRetryThrowsForInvalidBackoffAlgorithm()
    {
        $this->requiresPredisRetrySupport();

        $connector = new TestablePredisConnector;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Algorithm [bogus] is not a valid Predis backoff algorithm.');

        $connector->testFormatRetry([
            'retry' => [
                'max_retries' => 3,
                'backoff_algorithm' => 'bogus',
            ],
        ]);
    }

    protected function requiresPredisRetrySupport()
    {
        if (! class_exists(Retry::class)) {
            $this->markTestSkipped('Predis retry support is not available.');
        }
    }
}

class TestablePredisConnector extends PredisConnector
{
    public function testFormatRetry(array $config): array
    {
        return $this->formatRetry($config);
    }

    public function testFormatHost(array $config): array
    {
        return $this->formatHost($config);
    }
}
