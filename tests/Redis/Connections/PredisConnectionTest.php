<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PredisConnection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class PredisConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testIsClusterReturnsFalseForStandaloneConnection()
    {
        $connection = new PredisConnection(m::mock(Client::class));

        $this->assertFalse($connection->isCluster());
    }

    public function testIsClusterReturnsFalseEvenWhenClusterAwareFlagSet()
    {
        $connection = new PredisConnection(
            m::mock(Client::class),
            ['cluster_aware' => true],
        );

        $this->assertFalse($connection->isCluster());
    }

    public function testIsClusterAwareReturnsFalseByDefault()
    {
        $connection = new PredisConnection(m::mock(Client::class));

        $this->assertFalse($connection->isClusterAware());
    }

    public function testIsClusterAwareReturnsFalseWhenFlagDisabled()
    {
        $connection = new PredisConnection(
            m::mock(Client::class),
            ['cluster_aware' => false],
        );

        $this->assertFalse($connection->isClusterAware());
    }

    public function testIsClusterAwareReturnsTrueWhenFlagSet()
    {
        $connection = new PredisConnection(
            m::mock(Client::class),
            ['cluster_aware' => true],
        );

        $this->assertTrue($connection->isClusterAware());
    }
}
