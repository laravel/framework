<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\MemcachedConnector;
use Memcached;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class CacheMemcachedConnectorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testServersAreAddedCorrectly()
    {
        $memcached = $this->memcachedMockWithAddServer();

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('createMemcachedInstance')
            ->willReturn($memcached);

        $result = $this->connect($connector);

        $this->assertSame($result, $memcached);
    }

    public function testServersAreAddedCorrectlyWithPersistentConnection()
    {
        $persistentConnectionId = 'persistent_connection_id';

        $memcached = $this->memcachedMockWithAddServer();

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('createMemcachedInstance')
            ->with($persistentConnectionId)
            ->willReturn($memcached);

        $result = $this->connect($connector, $persistentConnectionId);

        $this->assertSame($result, $memcached);
    }

    /**
     * @requires extension memcached
     */
    public function testServersAreAddedCorrectlyWithValidOptions()
    {
        $validOptions = [
            Memcached::OPT_NO_BLOCK => true,
            Memcached::OPT_CONNECT_TIMEOUT => 2000,
        ];

        $memcached = $this->memcachedMockWithAddServer();
        $memcached->shouldReceive('setOptions')->once()->andReturn(true);

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('createMemcachedInstance')
            ->willReturn($memcached);

        $result = $this->connect($connector, false, $validOptions);

        $this->assertSame($result, $memcached);
    }

    /**
     * @requires extension memcached
     */
    public function testServersAreAddedCorrectlyWithSaslCredentials()
    {
        $saslCredentials = ['foo', 'bar'];

        $memcached = $this->memcachedMockWithAddServer();
        $memcached->shouldReceive('setOption')->once()->with(Memcached::OPT_BINARY_PROTOCOL, true)->andReturn(true);
        $memcached->shouldReceive('setSaslAuthData')
            ->once()->with($saslCredentials[0], $saslCredentials[1])
            ->andReturn(true);

        $connector = $this->connectorMock();
        $connector->expects($this->once())->method('createMemcachedInstance')->willReturn($memcached);

        $result = $this->connect($connector, false, [], $saslCredentials);

        $this->assertSame($result, $memcached);
    }

    protected function memcachedMockWithAddServer($returnedVersion = [])
    {
        $memcached = m::mock(stdClass::class);
        $memcached->shouldReceive('addServer')->once()->with($this->getHost(), $this->getPort(), $this->getWeight());
        $memcached->shouldReceive('getServerList')->once()->andReturn([]);

        return $memcached;
    }

    protected function connectorMock()
    {
        return $this->getMockBuilder(MemcachedConnector::class)->onlyMethods(['createMemcachedInstance'])->getMock();
    }

    protected function connect(
        $connector,
        $persistentConnectionId = false,
        array $customOptions = [],
        array $saslCredentials = []
    ) {
        return $connector->connect(
            $this->getServers(),
            $persistentConnectionId,
            $customOptions,
            $saslCredentials
        );
    }

    protected function getServers()
    {
        return [['host' => $this->getHost(), 'port' => $this->getPort(), 'weight' => $this->getWeight()]];
    }

    protected function getHost()
    {
        return 'localhost';
    }

    protected function getPort()
    {
        return 11211;
    }

    protected function getWeight()
    {
        return 100;
    }
}
