<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connectors\PhpRedisConnector;
use PHPUnit\Framework\TestCase;

class PhpRedisConnectorTest extends TestCase
{
    public function testFormatClusterPasswordReturnsArrayWhenUsernameAndPasswordProvided()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'username' => 'myuser',
            'password' => 'mypass',
        ]);

        $this->assertSame(['myuser', 'mypass'], $result);
    }

    public function testFormatClusterPasswordReturnsPlainPasswordWithoutUsername()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'password' => 'mypass',
        ]);

        $this->assertSame('mypass', $result);
    }

    public function testFormatClusterPasswordReturnsNullWhenNoPasswordProvided()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([]);

        $this->assertNull($result);
    }

    public function testFormatClusterPasswordReturnsPlainPasswordWhenUsernameIsEmpty()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'username' => '',
            'password' => 'mypass',
        ]);

        $this->assertSame('mypass', $result);
    }

    public function testFormatClusterPasswordReturnsPlainPasswordWhenPasswordIsNotString()
    {
        $connector = new TestablePhpRedisConnector;

        $result = $connector->testFormatClusterPassword([
            'username' => 'myuser',
            'password' => ['mypass'],
        ]);

        $this->assertSame(['mypass'], $result);
    }

    public function testParseBackoffAlgorithmReturnsIntegerAsIs()
    {
        $connector = new TestablePhpRedisConnector;

        $this->assertSame(42, $connector->testParseBackoffAlgorithm(42));
    }

    public function testParseBackoffAlgorithmParsesValidNames()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Requires phpredis extension.');
        }

        $connector = new TestablePhpRedisConnector;

        $this->assertSame(\Redis::BACKOFF_ALGORITHM_DEFAULT, $connector->testParseBackoffAlgorithm('default'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER, $connector->testParseBackoffAlgorithm('decorrelated_jitter'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_EQUAL_JITTER, $connector->testParseBackoffAlgorithm('equal_jitter'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_EXPONENTIAL, $connector->testParseBackoffAlgorithm('exponential'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_UNIFORM, $connector->testParseBackoffAlgorithm('uniform'));
        $this->assertSame(\Redis::BACKOFF_ALGORITHM_CONSTANT, $connector->testParseBackoffAlgorithm('constant'));
    }

    public function testParseBackoffAlgorithmThrowsForInvalidName()
    {
        $connector = new TestablePhpRedisConnector;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Algorithm [bogus] is not a valid PhpRedis backoff algorithm.');

        $connector->testParseBackoffAlgorithm('bogus');
    }
}

class TestablePhpRedisConnector extends PhpRedisConnector
{
    public function testFormatClusterPassword(array $options)
    {
        return $this->formatClusterPassword($options);
    }

    public function testParseBackoffAlgorithm(mixed $algorithm): int
    {
        return $this->parseBackoffAlgorithm($algorithm);
    }
}
