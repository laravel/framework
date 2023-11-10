<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class TrustProxiesTest extends TestCase
{
    /**
     * A list of all proxy headers.
     *
     * @var int
     */
    protected $headerAll = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PREFIX | Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Test that Symfony does indeed NOT trust X-Forwarded-*
     * headers when not given trusted proxies.
     *
     * This re-tests Symfony's Request class, but hopefully provides
     * some clarify to developers looking at the tests.
     */
    public function test_request_does_not_trust()
    {
        $req = $this->createProxiedRequest();

        $this->assertSame('192.168.10.10', $req->getClientIp(), 'Assert untrusted proxy x-forwarded-for header not used');
        $this->assertSame('http', $req->getScheme(), 'Assert untrusted proxy x-forwarded-proto header not used');
        $this->assertSame('localhost', $req->getHost(), 'Assert untrusted proxy x-forwarded-host header not used');
        $this->assertEquals(8888, $req->getPort(), 'Assert untrusted proxy x-forwarded-port header not used');
        $this->assertSame('', $req->getBaseUrl(), 'Assert untrusted proxy x-forwarded-prefix header not used');
    }

    /**
     * Test that Symfony DOES indeed trust X-Forwarded-*
     * headers when given trusted proxies.
     *
     * Again, this re-tests Symfony's Request class.
     */
    public function test_does_trust_trusted_proxy()
    {
        $req = $this->createProxiedRequest();
        $req->setTrustedProxies(['192.168.10.10'], $this->headerAll);

        $this->assertSame('173.174.200.38', $req->getClientIp(), 'Assert trusted proxy x-forwarded-for header used');
        $this->assertSame('https', $req->getScheme(), 'Assert trusted proxy x-forwarded-proto header used');
        $this->assertSame('serversforhackers.com', $req->getHost(), 'Assert trusted proxy x-forwarded-host header used');
        $this->assertEquals(443, $req->getPort(), 'Assert trusted proxy x-forwarded-port header used');
        $this->assertSame('/prefix', $req->getBaseUrl(), 'Assert trusted proxy x-forwarded-prefix header used');
    }

    /**
     * Test the next most typical usage of TrustedProxies:
     * Trusted X-Forwarded-For header, wildcard for TrustedProxies.
     */
    public function test_trusted_proxy_sets_trusted_proxies_with_wildcard()
    {
        $trustedProxy = $this->createTrustedProxy($this->headerAll, '*');
        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('173.174.200.38', $request->getClientIp(), 'Assert trusted proxy x-forwarded-for header used with wildcard proxy setting');
        });
    }

    /**
     * Test the next most typical usage of TrustedProxies:
     * Trusted X-Forwarded-For header, wildcard for TrustedProxies.
     */
    public function test_trusted_proxy_sets_trusted_proxies_with_double_wildcard_for_backwards_compat()
    {
        $trustedProxy = $this->createTrustedProxy($this->headerAll, '**');
        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('173.174.200.38', $request->getClientIp(), 'Assert trusted proxy x-forwarded-for header used with wildcard proxy setting');
        });
    }

    /**
     * Test the most typical usage of TrustProxies:
     * Trusted X-Forwarded-For header.
     */
    public function test_trusted_proxy_sets_trusted_proxies()
    {
        $trustedProxy = $this->createTrustedProxy($this->headerAll, ['192.168.10.10']);
        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('173.174.200.38', $request->getClientIp(), 'Assert trusted proxy x-forwarded-for header used');
        });
    }

    /**
     * Test X-Forwarded-For header with multiple IP addresses.
     */
    public function test_get_client_ips()
    {
        $trustedProxy = $this->createTrustedProxy($this->headerAll, ['192.168.10.10']);

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.2, 192.0.2.199',
            '192.0.2.2, 192.0.2.199, 99.99.99.99',
            '192.0.2.2,192.0.2.199',
        ];

        foreach ($forwardedFor as $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $trustedProxy->handle($request, function ($request) use ($forwardedForHeader) {
                $ips = $request->getClientIps();
                $this->assertSame('192.0.2.2', end($ips), 'Assert sets the '.$forwardedForHeader);
            });
        }
    }

    /**
     * Test X-Forwarded-For header with multiple IP addresses, with some of those being trusted.
     */
    public function test_get_client_ip_with_multiple_ip_addresses_some_of_which_are_trusted()
    {
        $trustedProxy = $this->createTrustedProxy($this->headerAll, ['192.168.10.10', '192.0.2.199']);

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.2, 192.0.2.199',
            '99.99.99.99, 192.0.2.2, 192.0.2.199',
            '192.0.2.2,192.0.2.199',
        ];

        foreach ($forwardedFor as $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $trustedProxy->handle($request, function ($request) use ($forwardedForHeader) {
                $this->assertSame('192.0.2.2', $request->getClientIp(), 'Assert sets the '.$forwardedForHeader);
            });
        }
    }

    /**
     * Test X-Forwarded-For header with multiple IP addresses, with * wildcard trusting of all proxies.
     */
    public function test_get_client_ip_with_multiple_ip_addresses_all_proxies_are_trusted()
    {
        $trustedProxy = $this->createTrustedProxy($this->headerAll, '*');

        $forwardedFor = [
            '192.0.2.2',
            '192.0.2.199, 192.0.2.2',
            '192.0.2.199,192.0.2.2',
            '99.99.99.99,192.0.2.199,192.0.2.2',
        ];

        foreach ($forwardedFor as $forwardedForHeader) {
            $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_FOR' => $forwardedForHeader]);

            $trustedProxy->handle($request, function ($request) use ($forwardedForHeader) {
                $this->assertSame('192.0.2.2', $request->getClientIp(), 'Assert sets the '.$forwardedForHeader);
            });
        }
    }

    /**
     * Test distrusting a header.
     */
    public function test_can_distrust_headers()
    {
        $trustedProxy = $this->createTrustedProxy(Request::HEADER_FORWARDED, ['192.168.10.10']);

        $request = $this->createProxiedRequest([
            'HTTP_FORWARDED' => 'for=173.174.200.40:443; proto=https; host=serversforhackers.com',
            'HTTP_X_FORWARDED_FOR' => '173.174.200.38',
            'HTTP_X_FORWARDED_HOST' => 'svrs4hkrs.com',
            'HTTP_X_FORWARDED_PORT' => '80',
            'HTTP_X_FORWARDED_PROTO' => 'http',
        ]);

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('173.174.200.40', $request->getClientIp(),
                'Assert trusted proxy used forwarded header for IP');
            $this->assertSame('https', $request->getScheme(),
                'Assert trusted proxy used forwarded header for scheme');
            $this->assertSame('serversforhackers.com', $request->getHost(),
                'Assert trusted proxy used forwarded header for host');
            $this->assertEquals(443, $request->getPort(), 'Assert trusted proxy used forwarded header for port');
        });
    }

    /**
     * Test that only the X-Forwarded-For header is trusted.
     */
    public function test_x_forwarded_for_header_only_trusted()
    {
        $trustedProxy = $this->createTrustedProxy(Request::HEADER_X_FORWARDED_FOR, '*');

        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('173.174.200.38', $request->getClientIp(),
                'Assert trusted proxy used forwarded header for IP');
            $this->assertSame('http', $request->getScheme(),
                'Assert trusted proxy did not use forwarded header for scheme');
            $this->assertSame('localhost', $request->getHost(),
                'Assert trusted proxy did not use forwarded header for host');
            $this->assertEquals(8888, $request->getPort(), 'Assert trusted proxy did not use forwarded header for port');
            $this->assertSame('', $request->getBaseUrl(), 'Assert trusted proxy did not use forwarded header for prefix');
        });
    }

    /**
     * Test that only the X-Forwarded-Host header is trusted.
     */
    public function test_x_forwarded_host_header_only_trusted()
    {
        $trustedProxy = $this->createTrustedProxy(Request::HEADER_X_FORWARDED_HOST, '*');

        $request = $this->createProxiedRequest(['HTTP_X_FORWARDED_HOST' => 'serversforhackers.com:8888']);

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('192.168.10.10', $request->getClientIp(),
                'Assert trusted proxy did not use forwarded header for IP');
            $this->assertSame('http', $request->getScheme(),
                'Assert trusted proxy did not use forwarded header for scheme');
            $this->assertSame('serversforhackers.com', $request->getHost(),
                'Assert trusted proxy used forwarded header for host');
            $this->assertEquals(8888, $request->getPort(), 'Assert trusted proxy did not use forwarded header for port');
            $this->assertSame('', $request->getBaseUrl(), 'Assert trusted proxy did not use forwarded header for prefix');
        });
    }

    /**
     * Test that only the X-Forwarded-Port header is trusted.
     */
    public function test_x_forwarded_port_header_only_trusted()
    {
        $trustedProxy = $this->createTrustedProxy(Request::HEADER_X_FORWARDED_PORT, '*');

        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('192.168.10.10', $request->getClientIp(),
                'Assert trusted proxy did not use forwarded header for IP');
            $this->assertSame('http', $request->getScheme(),
                'Assert trusted proxy did not use forwarded header for scheme');
            $this->assertSame('localhost', $request->getHost(),
                'Assert trusted proxy did not use forwarded header for host');
            $this->assertEquals(443, $request->getPort(), 'Assert trusted proxy used forwarded header for port');
            $this->assertSame('', $request->getBaseUrl(), 'Assert trusted proxy did not use forwarded header for prefix');
        });
    }

    /**
     * Test that only the X-Forwarded-Prefix header is trusted.
     */
    public function test_x_forwarded_prefix_header_only_trusted()
    {
        $trustedProxy = $this->createTrustedProxy(Request::HEADER_X_FORWARDED_PREFIX, '*');

        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('192.168.10.10', $request->getClientIp(),
                'Assert trusted proxy did not use forwarded header for IP');
            $this->assertSame('http', $request->getScheme(),
                'Assert trusted proxy did not use forwarded header for scheme');
            $this->assertSame('localhost', $request->getHost(),
                'Assert trusted proxy did not use forwarded header for host');
            $this->assertEquals(8888, $request->getPort(), 'Assert trusted proxy did not use forwarded header for port');
            $this->assertSame('/prefix', $request->getBaseUrl(), 'Assert trusted proxy used forwarded header for prefix');
        });
    }

    /**
     * Test that only the X-Forwarded-Proto header is trusted.
     */
    public function test_x_forwarded_proto_header_only_trusted()
    {
        $trustedProxy = $this->createTrustedProxy(Request::HEADER_X_FORWARDED_PROTO, '*');

        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('192.168.10.10', $request->getClientIp(),
                'Assert trusted proxy did not use forwarded header for IP');
            $this->assertSame('https', $request->getScheme(),
                'Assert trusted proxy used forwarded header for scheme');
            $this->assertSame('localhost', $request->getHost(),
                'Assert trusted proxy did not use forwarded header for host');
            $this->assertEquals(8888, $request->getPort(), 'Assert trusted proxy did not use forwarded header for port');
            $this->assertSame('', $request->getBaseUrl(), 'Assert trusted proxy did not use forwarded header for prefix');
        });
    }

    /**
     * Test a combination of individual X-Forwarded-* headers are trusted.
     */
    public function test_x_forwarded_multiple_individual_headers_trusted()
    {
        $trustedProxy = $this->createTrustedProxy(
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
            '*'
        );

        $request = $this->createProxiedRequest();

        $trustedProxy->handle($request, function ($request) {
            $this->assertSame('173.174.200.38', $request->getClientIp(),
                'Assert trusted proxy used forwarded header for IP');
            $this->assertSame('https', $request->getScheme(),
                'Assert trusted proxy used forwarded header for scheme');
            $this->assertSame('serversforhackers.com', $request->getHost(),
                'Assert trusted proxy used forwarded header for host');
            $this->assertEquals(443, $request->getPort(), 'Assert trusted proxy used forwarded header for port');
            $this->assertSame('', $request->getBaseUrl(), 'Assert trusted proxy did not use forwarded header for prefix');
        });
    }

    /**
     * Test to ensure it's reading text-based configurations and converting it correctly.
     */
    public function test_is_reading_text_based_configurations()
    {
        $request = $this->createProxiedRequest();

        // trust *all* "X-Forwarded-*" headers
        $trustedProxy = $this->createTrustedProxy('HEADER_X_FORWARDED_ALL', '192.168.1.1, 192.168.1.2');
        $trustedProxy->handle($request, function (Request $request) {
            $this->assertEquals($request->getTrustedHeaderSet(), $this->headerAll,
                'Assert trusted proxy used all "X-Forwarded-*" header');

            $this->assertEquals($request->getTrustedProxies(), ['192.168.1.1', '192.168.1.2'],
                'Assert trusted proxy using proxies as string separated by comma.');
        });

        // or, if your proxy instead uses the "Forwarded" header
        $trustedProxy = $this->createTrustedProxy('HEADER_FORWARDED', '192.168.1.1, 192.168.1.2');
        $trustedProxy->handle($request, function (Request $request) {
            $this->assertEquals($request->getTrustedHeaderSet(), Request::HEADER_FORWARDED,
                'Assert trusted proxy used forwarded header');

            $this->assertEquals($request->getTrustedProxies(), ['192.168.1.1', '192.168.1.2'],
                'Assert trusted proxy using proxies as string separated by comma.');
        });

        // or, if you're using AWS ELB
        $trustedProxy = $this->createTrustedProxy('HEADER_X_FORWARDED_AWS_ELB', '192.168.1.1, 192.168.1.2');
        $trustedProxy->handle($request, function (Request $request) {
            $this->assertEquals($request->getTrustedHeaderSet(), Request::HEADER_X_FORWARDED_AWS_ELB,
                'Assert trusted proxy used AWS ELB header');

            $this->assertEquals($request->getTrustedProxies(), ['192.168.1.1', '192.168.1.2'],
                'Assert trusted proxy using proxies as string separated by comma.');
        });
    }

    /**
     * Fake an HTTP request by generating a Symfony Request object.
     *
     * @param  array  $serverOverrides
     * @return \Illuminate\Http\Request
     */
    protected function createProxiedRequest($serverOverrides = [])
    {
        // Add some X-Forwarded headers and over-ride
        // defaults, simulating a request made over a proxy
        $serverOverrides = array_replace([
            'HTTP_X_FORWARDED_FOR' => '173.174.200.38',         // X-Forwarded-For    -- getClientIp()
            'HTTP_X_FORWARDED_HOST' => 'serversforhackers.com', // X-Forwarded-Host   -- getHosts()
            'HTTP_X_FORWARDED_PORT' => '443',                   // X-Forwarded-Port   -- getPort()
            'HTTP_X_FORWARDED_PREFIX' => '/prefix',             // X-Forwarded-Prefix -- getBaseUrl()
            'HTTP_X_FORWARDED_PROTO' => 'https',                // X-Forwarded-Proto  -- getScheme() / isSecure()
            'SERVER_PORT' => 8888,
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '192.168.10.10',
        ], $serverOverrides);

        // Create a fake request made over "http", one that we'd get over a proxy
        // which is likely something like this:
        $request = Request::create('http://localhost:8888/tag/proxy', 'GET', [], [], [], $serverOverrides, null);
        // Need to make sure these haven't already been set
        $request->setTrustedProxies([], $this->headerAll);

        return $request;
    }

    /**
     * Create an anonymous middleware class.
     *
     * @param  null|string|int  $trustedHeaders
     * @param  null|array|string  $trustedProxies
     * @return \Illuminate\Http\Middleware\TrustProxies
     */
    protected function createTrustedProxy($trustedHeaders, $trustedProxies)
    {
        return new class($trustedHeaders, $trustedProxies) extends TrustProxies
        {
            public function __construct($trustedHeaders, $trustedProxies)
            {
                $this->headers = $trustedHeaders;
                $this->proxies = $trustedProxies;
            }
        };
    }
}
