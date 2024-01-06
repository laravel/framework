<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase;

class TrustProxiesTest extends TestCase
{
    use ValidatesRequests;

    protected const TEST_REQUEST_HEADERS = [
        'HTTP_X_FORWARDED_FOR' => '173.174.200.38',         // X-Forwarded-For    -- getClientIp()
        'HTTP_X_FORWARDED_HOST' => 'serversforhackers.com', // X-Forwarded-Host   -- getHosts()
        'HTTP_X_FORWARDED_PORT' => '443',                   // X-Forwarded-Port   -- getPort()
        'HTTP_X_FORWARDED_PREFIX' => '/prefix',             // X-Forwarded-Prefix -- getBaseUrl()
        'HTTP_X_FORWARDED_PROTO' => 'https',                // X-Forwarded-Proto  -- getScheme() / isSecure()
        'SERVER_PORT' => 80,
        'HTTP_HOST' => 'localhost',
        'REMOTE_ADDR' => '192.168.10.10',
    ];

    protected function getEnvironmentSetUp($app)
    {
        $kernel = $app->make(Kernel::class);
        $kernel->prependMiddleware(TrustProxies::class);

        $router = $app['router'];

        $this->addWebRoutes($router);

        parent::getEnvironmentSetUp($app);
    }

    public function testDefaultConfigurationDoesNotTrustAnyProxies()
    {
        $crawler = $this->call(
            'POST',
            'web/ping',
            [],
            [],
            [],
            self::TEST_REQUEST_HEADERS
        );

        $this->assertEquals('192.168.10.10', $crawler->baseRequest->getClientIp(), 'client ip not taken from untrusted proxy');
        $this->assertEquals('localhost', $crawler->baseRequest->getHost(), 'host not taken from untrusted proxy');
        $this->assertEquals(80, $crawler->baseRequest->getPort(), 'port not taken from untrusted proxy');
        $this->assertEquals('http', $crawler->baseRequest->getScheme(), 'scheme not taken from untrusted proxy');
        $this->assertEquals('', $crawler->baseRequest->getBaseUrl(), 'base url not taken from untrusted proxy');
    }

    public function testTrustingAllProxies()
    {
        $this->app['config']->set('trustedproxy.proxies', '*');

        $crawler = $this->call(
            'POST',
            'web/ping',
            [],
            [],
            [],
            self::TEST_REQUEST_HEADERS
        );

        $this->assertEquals('173.174.200.38', $crawler->baseRequest->getClientIp(), 'client ip taken from trusted proxy');
        $this->assertEquals('serversforhackers.com', $crawler->baseRequest->getHost(), 'host taken from trusted proxy');
        $this->assertEquals(443, $crawler->baseRequest->getPort(), 'port taken from trusted proxy');
        $this->assertEquals('https', $crawler->baseRequest->getScheme(), 'scheme taken from trusted proxy');
        $this->assertEquals('/prefix', $crawler->baseRequest->getBaseUrl(), 'base url taken from trusted proxy');
    }

    public function testTrustingSpecificProxies()
    {
        $this->app['config']->set('trustedproxy.proxies', '1.1.1.1, 192.168.10.10');

        $crawler = $this->call(
            'POST',
            'web/ping',
            [],
            [],
            [],
            self::TEST_REQUEST_HEADERS
        );

        $this->assertEquals('173.174.200.38', $crawler->baseRequest->getClientIp(), 'client ip taken from trusted proxy');
        $this->assertEquals('serversforhackers.com', $crawler->baseRequest->getHost(), 'host taken from trusted proxy');
        $this->assertEquals(443, $crawler->baseRequest->getPort(), 'port taken from trusted proxy');
        $this->assertEquals('https', $crawler->baseRequest->getScheme(), 'scheme taken from trusted proxy');
        $this->assertEquals('/prefix', $crawler->baseRequest->getBaseUrl(), 'base url taken from trusted proxy');
    }

    public function testTrustingSpecificHeaders()
    {
        $this->app['config']->set('trustedproxy.proxies', '*');
        $this->app['config']->set('trustedproxy.headers', 'HEADER_X_FORWARDED_AWS_ELB');

        $crawler = $this->call(
            'POST',
            'web/ping',
            [],
            [],
            [],
            self::TEST_REQUEST_HEADERS
        );

        $this->assertEquals('173.174.200.38', $crawler->baseRequest->getClientIp(), 'client ip taken from trusted proxy');
        $this->assertNotEquals('serversforhackers.com', $crawler->baseRequest->getHost(), 'with aws elb, the x-forwarded-host is not taken from trusted proxy');
    }

    protected function addWebRoutes(Router $router)
    {
        $router->post('web/ping', [
            'uses' => function () {
                return 'PONG';
            },
        ]);
    }
}
