<?php

use Mockery as m;

class CacheMemcachedConnectorTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testServersAreAddedCorrectly()
    {
        $memcached = $this->memcachedMockWithAddServer();

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('getMemcached')
            ->will($this->returnValue($memcached));

        $result = $this->connect($connector);

        $this->assertSame($result, $memcached);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExceptionThrownOnBadConnection()
    {
        $memcached = $this->memcachedMockWithAddServer(['255.255.255']);

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('getMemcached')
            ->will($this->returnValue($memcached));

        $this->connect($connector);
    }

    public function testServersAreAddedCorrectlyWithPersistentConnection()
    {
        $persistentConnectionId = 'persistent_connection_id';

        $memcached = $this->memcachedMockWithAddServer();

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('getMemcached')
            ->with($persistentConnectionId)
            ->will($this->returnValue($memcached));

        $result = $this->connect($connector, $persistentConnectionId);

        $this->assertSame($result, $memcached);
    }

    public function testServersAreAddedCorrectlyWithValidOptions()
    {
        if (! class_exists('Memcached')) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $validOptions = ['OPT_NO_BLOCK' => true, 'OPT_AUTO_EJECT_HOSTS' => true];

        $memcached = $this->memcachedMockWithAddServer();
        $memcached->shouldReceive('setOptions')->once()->andReturn(true);

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('getMemcached')
            ->will($this->returnValue($memcached));

        $result = $this->connect($connector, false, $validOptions);

        $this->assertSame($result, $memcached);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExceptionThrownWithInvalidOptions()
    {
        if (! class_exists('Memcached')) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $invalidOptions = ['OPT_NO_BLOC' => true, 'OPT_AUTO_EJECT_HOST' => true];

        $memcached = m::mock('stdClass');

        $connector = $this->connectorMock();
        $connector->expects($this->once())
            ->method('getMemcached')
            ->will($this->returnValue($memcached));

        $this->connect($connector, false, $invalidOptions);
    }

    public function testServersAreAddedCorrectlyWithSaslCredentials()
    {
        if (! class_exists('Memcached')) {
            $this->markTestSkipped('Memcached module not installed');
        }

        $saslCredentials = ['foo', 'bar'];

        $memcached = $this->memcachedMockWithAddServer();
        $memcached->shouldReceive('setOption')->once()->with(Memcached::OPT_BINARY_PROTOCOL, true)->andReturn(true);
        $memcached->shouldReceive('setSaslAuthData')
            ->once()->with($saslCredentials[0], $saslCredentials[1])
            ->andReturn(true);

        $connector = $this->connectorMock();
        $connector->expects($this->once())->method('getMemcached')->will($this->returnValue($memcached));

        $result = $this->connect($connector, false, [], $saslCredentials);

        $this->assertSame($result, $memcached);
    }

    protected function memcachedMockWithAddServer($returnedVersion = [])
    {
        $memcached = m::mock('stdClass');
        $memcached->shouldReceive('addServer')->once()->with($this->getHost(), $this->getPort(), $this->getWeight());
        $memcached->shouldReceive('getVersion')->once()->andReturn($returnedVersion);
        $memcached->shouldReceive('getServerList')->once()->andReturn([]);

        return $memcached;
    }

    protected function connectorMock()
    {
        return $this->getMock('Illuminate\Cache\MemcachedConnector', ['getMemcached']);
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
