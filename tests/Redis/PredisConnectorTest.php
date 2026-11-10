<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Connectors\PredisConnector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
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

        $this->expectExceptionObject(new InvalidArgumentException('The scheme configured in the Redis host option must match the scheme option.'));

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
                ExponentialBackoff::class => [
                    250_000, 2_000_000,
                ],
            ],
            'max_retries' => 3,
        ]);

        $this->assertInstanceOf(Retry::class, $config['retry']);
        $this->assertSame(3, $config['retry']->getRetries());

        $this->assertInstanceOf(ExponentialBackoff::class, $config['retry']->getStrategy());
        $this->assertSame(250000, $config['retry']->getStrategy()->getBase());
        $this->assertSame(2000000, $config['retry']->getStrategy()->getCap());
    }

    public function testFormatRetryCreatesRetryFromScalarOptionsWithNamedArray()
    {
        $this->requiresPredisRetrySupport();

        $connector = new TestablePredisConnector;

        $config = $connector->testFormatRetry([
            'host' => '127.0.0.1',
            'retry' => [
                ExponentialBackoff::class => [
                    'withJitter' => true,
                    'base' => 250_000,
                    'cap' => 2_000_000,
                ],
            ],
            'max_retries' => 3,
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
                EqualBackoff::class => [250_000],
            ],
            'max_retries' => 3,
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
                NoBackoff::class => [],
            ],
            'max_retries' => 3,
        ]);

        $this->assertInstanceOf(NoBackoff::class, $config['retry']->getStrategy());
        $this->assertSame(0, $config['retry']->getStrategy()->compute(0));
    }

    public function testConnectToClusterFormatsRetryParameters()
    {
        $this->requiresPredisRetrySupport();

        $connector = new TestablePredisConnector;

        $connection = $connector->connectToCluster([
            ['host' => '127.0.0.1', 'port' => 6379],
        ], [
            'cluster' => 'redis',
            'parameters' => [
                'retry' => [
                    NoBackoff::class => [],
                ],
                'max_retries' => 3,
            ],
        ], []);

        $property = new \ReflectionProperty($connection->client()->getConnection(), 'connectionParameters');
        $parameters = $property->getValue($connection->client()->getConnection());

        $this->assertInstanceOf(Retry::class, $parameters->retry);
        $this->assertSame(3, $parameters->retry->getRetries());
        $this->assertInstanceOf(NoBackoff::class, $parameters->retry->getStrategy());
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

        $this->expectExceptionObject(new InvalidArgumentException('Strategy [bogus] is not a valid Predis backoff strategy.'));

        $connector->testFormatRetry([
            'retry' => [
                'bogus' => [],
            ],
            'max_retries' => 3,
        ]);
    }

    public function testConnectionAutomaticallyRetriesReadOnlyCommandAfterLosingTheConnection()
    {
        $client = new FailingPredisClient($this->connectionException(), failures: 1, result: 'bar');

        $connection = new PredisConnection($client);

        $this->assertSame('bar', $connection->command('get', ['foo']));
        $this->assertSame([['get', ['foo']], ['get', ['foo']]], $client->calls);
    }

    public function testConnectionAutomaticallyRetriesIdempotentWriteAfterLosingTheConnection()
    {
        $client = new FailingPredisClient($this->connectionException(), failures: 1, result: true);

        $connection = new PredisConnection($client);

        $this->assertTrue($connection->command('set', ['foo', 'bar']));
        $this->assertCount(2, $client->calls);
    }

    public function testConnectionDoesNotAutomaticallyRetryNonIdempotentWrite()
    {
        $client = new FailingPredisClient($exception = $this->connectionException(), failures: 1);

        $connection = new PredisConnection($client);

        try {
            $connection->command('incr', ['foo']);
            $this->fail('Expected ConnectionException was not thrown.');
        } catch (ConnectionException $e) {
            $this->assertSame($exception, $e);
        }

        $this->assertCount(1, $client->calls);
    }

    public function testConnectionDoesNotAutomaticallyRetrySetWithOptions()
    {
        $client = new FailingPredisClient($exception = $this->connectionException(), failures: 1);

        $connection = new PredisConnection($client);

        try {
            $connection->command('set', ['foo', 'bar', 'EX', 60]);
            $this->fail('Expected ConnectionException was not thrown.');
        } catch (ConnectionException $e) {
            $this->assertSame($exception, $e);
        }

        $this->assertCount(1, $client->calls);
    }

    public function testConnectionStopsRetryingAfterConfiguredAttempts()
    {
        $client = new FailingPredisClient($exception = $this->connectionException(), failures: 4);

        $connection = new PredisConnection($client, ['command_retries' => 2]);

        try {
            $connection->command('get', ['foo']);
            $this->fail('Expected ConnectionException was not thrown.');
        } catch (ConnectionException $e) {
            $this->assertSame($exception, $e);
        }

        $this->assertCount(3, $client->calls);
    }

    public function testConnectPassesCommandRetriesToTheConnection()
    {
        $connection = (new TestablePredisConnector)->connect(['host' => '127.0.0.1', 'command_retries' => 2], []);

        $this->assertSame(2, (fn () => $this->config['command_retries'])->call($connection));
    }

    protected function connectionException()
    {
        return new ConnectionException($this->createStub(NodeConnectionInterface::class), 'Stream is already at the end');
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

class FailingPredisClient
{
    public array $calls = [];

    public function __construct(
        protected ConnectionException $exception,
        protected int $failures,
        protected mixed $result = null,
    ) {
    }

    public function __call($method, $parameters)
    {
        $this->calls[] = [$method, $parameters];

        if ($this->failures-- > 0) {
            throw $this->exception;
        }

        return $this->result;
    }
}
