<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Env;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Redis;

#[RequiresPhpExtension('redis')]
class PhpRedisBackoffTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();

        $client = $this->redis['phpredis']->connection()->client();
        if (! $client instanceof Redis) {
            $this->markTestSkipped('Backoff option is only supported with phpredis in non-cluster mode');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownRedis();
    }

    #[DataProvider('phpRedisBackoffAlgorithmsProvider')]
    public function testPhpRedisBackoffAlgorithmParsing($friendlyAlgorithmName, $expectedAlgorithm)
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        $manager = new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => $friendlyAlgorithmName,
            ],
        ]);

        $this->assertEquals(
            $expectedAlgorithm,
            $manager->connection()->client()->getOption(Redis::OPT_BACKOFF_ALGORITHM)
        );
    }

    #[DataProvider('phpRedisBackoffAlgorithmsProvider')]
    public function testPhpRedisBackoffAlgorithm($friendlyAlgorithm, $expectedAlgorithm)
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        $manager = new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => $expectedAlgorithm,
            ],
        ]);

        $this->assertEquals(
            $expectedAlgorithm,
            $manager->connection()->client()->getOption(Redis::OPT_BACKOFF_ALGORITHM)
        );
    }

    public function testAnInvalidPhpRedisBackoffAlgorithmIsConvertedToDefault()
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        $manager = new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => 7,
            ],
        ]);

        $this->assertEquals(
            Redis::BACKOFF_ALGORITHM_DEFAULT,
            $manager->connection()->client()->getOption(Redis::OPT_BACKOFF_ALGORITHM)
        );
    }

    public function testItFailsWithAnInvalidPhpRedisAlgorithm()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Algorithm [foo] is not a valid PhpRedis backoff algorithm');

        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        (new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => 'foo',
            ],
        ]))->connection();
    }

    public static function phpRedisBackoffAlgorithmsProvider()
    {
        if (! class_exists(Redis::class)) {
            return [];
        }

        return [
            ['default', Redis::BACKOFF_ALGORITHM_DEFAULT],
            ['decorrelated_jitter', Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER],
            ['equal_jitter', Redis::BACKOFF_ALGORITHM_EQUAL_JITTER],
            ['exponential', Redis::BACKOFF_ALGORITHM_EXPONENTIAL],
            ['uniform', Redis::BACKOFF_ALGORITHM_UNIFORM],
            ['constant', Redis::BACKOFF_ALGORITHM_CONSTANT],
        ];
    }
}
