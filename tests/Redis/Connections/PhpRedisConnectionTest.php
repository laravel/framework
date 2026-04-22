<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Mockery as m;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('redis')]
class PhpRedisConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testIsClusterReturnsFalseForStandaloneConnection()
    {
        $connection = new PhpRedisConnection(m::mock(\Redis::class));

        $this->assertFalse($connection->isCluster());
    }

    public function testIsClusterReturnsFalseEvenWhenClusterAwareFlagSet()
    {
        $connection = new PhpRedisConnection(
            m::mock(\Redis::class),
            null,
            ['cluster_aware' => true],
        );

        // isCluster() is strictly about being a real cluster client,
        // which a standalone connection never is regardless of config flags.
        $this->assertFalse($connection->isCluster());
    }

    public function testIsClusterAwareReturnsFalseByDefault()
    {
        $connection = new PhpRedisConnection(m::mock(\Redis::class));

        $this->assertFalse($connection->isClusterAware());
    }

    public function testIsClusterAwareReturnsFalseWhenFlagDisabled()
    {
        $connection = new PhpRedisConnection(
            m::mock(\Redis::class),
            null,
            ['cluster_aware' => false],
        );

        $this->assertFalse($connection->isClusterAware());
    }

    public function testIsClusterAwareReturnsTrueWhenFlagSet()
    {
        $connection = new PhpRedisConnection(
            m::mock(\Redis::class),
            null,
            ['cluster_aware' => true],
        );

        $this->assertTrue($connection->isClusterAware());
    }

    public function testIsClusterAwareTreatsTruthyFlagAsEnabled()
    {
        $connection = new PhpRedisConnection(
            m::mock(\Redis::class),
            null,
            ['cluster_aware' => '1'],
        );

        $this->assertTrue($connection->isClusterAware());
    }
}
