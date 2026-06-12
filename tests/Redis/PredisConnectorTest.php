<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Foundation\Application;
use Illuminate\Redis\RedisManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PredisConnectorTest extends TestCase
{
    public function testRetryConfigurationWithScalarValues()
    {
        $manager = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_retries' => 5,
                'backoff_algorithm' => 'exponential',
                'backoff_base' => 250000,
                'backoff_cap' => 2000000,
            ],
        ]);

        $client = $manager->connection()->client();
        $parameters = $client->getConnection()->getParameters();

        $retry = $parameters->retry;
        $this->assertEquals(5, $retry->getRetries());
        $this->assertEquals(250000, $retry->getStrategy()->getBase());
        $this->assertEquals(2000000, $retry->getStrategy()->getCap());
    }

    public function testRetryConfigurationWithExponentialJitter()
    {
        $manager = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_retries' => 3,
                'backoff_algorithm' => 'exponential_jitter',
                'backoff_base' => 100000,
                'backoff_cap' => 500000,
            ],
        ]);

        $client = $manager->connection()->client();
        $parameters = $client->getConnection()->getParameters();

        $retry = $parameters->retry;
        $this->assertEquals(3, $retry->getRetries());
        $this->assertEquals(100000, $retry->getStrategy()->getBase());
        $this->assertEquals(500000, $retry->getStrategy()->getCap());
    }

    public function testRetryConfigurationWithEqualBackoff()
    {
        $manager = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_retries' => 10,
                'backoff_algorithm' => 'equal',
                'backoff_base' => 500000,
            ],
        ]);

        $client = $manager->connection()->client();
        $parameters = $client->getConnection()->getParameters();

        $retry = $parameters->retry;
        $this->assertEquals(10, $retry->getRetries());
    }

    public function testRetryConfigurationWithNoneBackoff()
    {
        $manager = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_retries' => 3,
                'backoff_algorithm' => 'none',
            ],
        ]);

        $client = $manager->connection()->client();
        $parameters = $client->getConnection()->getParameters();

        $retry = $parameters->retry;
        $this->assertEquals(3, $retry->getRetries());
    }

    public function testRetryConfigurationDefaultsWhenOnlyRetriesSpecified()
    {
        $manager = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_retries' => 5,
            ],
        ]);

        $client = $manager->connection()->client();
        $parameters = $client->getConnection()->getParameters();

        $retry = $parameters->retry;
        $this->assertEquals(5, $retry->getRetries());
    }

    public function testNoRetryConfigurationWhenNotProvided()
    {
        $manager = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
        ]);

        $client = $manager->connection()->client();
        $parameters = $client->getConnection()->getParameters();

        // Predis defaults to Retry with NoBackoff and 0 retries
        $retry = $parameters->retry;
        $this->assertEquals(0, $retry->getRetries());
    }

    public function testInvalidBackoffAlgorithmThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Algorithm [invalid] is not a valid Predis backoff algorithm');

        $manager = new RedisManager(new Application, 'predis', [
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_retries' => 3,
                'backoff_algorithm' => 'invalid',
            ],
        ]);

        $manager->connection();
    }
}
