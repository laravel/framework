<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RoutingServiceProviderTest extends TestCase
{
    public function testConfiguredApplicationUrlIsUsedInsteadOfRequestHost()
    {
        $this->app['config']->set('app.url', 'https://example.com:8443/base/');
        $this->app->forgetInstance('url');
        $this->app->instance('request', Request::create('http://attacker.example:8080/'));
        $this->app['router']->get('profile', fn () => '')->name('profile');
        $this->app['router']->domain('other.example')->get('domain', fn () => '')->name('domain');

        $url = $this->app->make(UrlGeneratorContract::class);

        $this->app->make(TrustProxies::class)->handle($this->app['request'], function () use ($url) {
            $this->assertSame('https://example.com:8443/base/profile', $url->to('profile'));
            $this->assertSame('http://example.com:8443/base/profile', $url->to('profile', [], false));
            $this->assertSame('https://example.com:8443/base/profile', $url->route('profile'));
            $this->assertSame('/profile', $url->route('profile', [], false));
            $this->assertSame('https://other.example:8443/domain', $url->route('domain'));
            $this->assertSame('https://example.com:8443/base/image.png', $url->asset('image.png'));

            $signed = $url->signedRoute('profile');
            $this->assertStringStartsWith('https://example.com:8443/base/profile?', $signed);
            $this->assertTrue($url->hasValidSignature(Request::create($signed)));
            $this->assertFalse($url->hasValidSignature(Request::create(str_replace('example.com', 'attacker.example', $signed))));
        });

        $this->assertSame('https://example.com:8443/base/profile', $url->route('profile'));
    }

    #[TestWith(['https://new.example', 'https://new.example/profile'])]
    #[TestWith(['invalid', 'http://request.example/profile'])]
    public function testApplicationUrlChangesAfterGeneratorResolutionAreRespected($appUrl, $expected)
    {
        $this->app['config']->set('app.url', 'https://old.example');
        $this->app->forgetInstance('url');
        $request = Request::create('http://request.example/');
        $this->app->instance('request', $request);
        $url = $this->app->make(UrlGeneratorContract::class);
        $this->app['config']->set('app.url', $appUrl);

        $this->app->make(TrustProxies::class)->handle($request, function () use ($url, $expected) {
            $this->assertSame($expected, $url->to('profile'));
        });
    }

    public function testReboundConfigurationIsUsedAfterGeneratorResolution()
    {
        $this->app['config']->set('app.url', 'https://old.example');
        $this->app->forgetInstance('url');
        $request = Request::create('http://request.example/');
        $this->app->instance('request', $request);
        $url = $this->app->make(UrlGeneratorContract::class);
        $config = clone $this->app['config'];
        $config->set('app.url', 'https://new.example');
        $this->app->instance('config', $config);

        $this->app->make(TrustProxies::class)->handle($request, function () use ($url) {
            $this->assertSame('https://new.example/profile', $url->to('profile'));
        });
    }

    #[TestWith([[], 'https://example.com:8443/base/profile'])]
    #[TestWith([['.*'], 'https://example.com:8443/base/profile'])]
    #[TestWith([['^.*$'], 'https://example.com:8443/base/profile'])]
    #[TestWith([['^tenant\.example\.com$'], 'http://tenant.example.com:8080/profile'])]
    public function testApplicationUrlDependsOnTrustedHosts(array $trustedHosts, string $expected)
    {
        Request::setTrustedHosts($trustedHosts);
        try {
            $this->app['config']->set('app.url', 'https://example.com:8443/base');
            $request = Request::create('http://tenant.example.com:8080/');
            $this->app->instance('request', $request);
            $this->app['router']->get('profile', fn () => '')->name('profile');

            $url = $this->app->make(UrlGeneratorContract::class);
            $this->app->make(TrustProxies::class)->handle($request, function () use ($url, $expected) {
                $this->assertSame($expected, $url->route('profile'));
            });
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    public function testApplicationOriginCanBeResetAfterExceptionsAndBetweenRequests()
    {
        $this->app['config']->set('app.url', 'https://example.com');
        $request = Request::create('http://first.example/');
        $this->app->instance('request', $request);
        $url = $this->app->make(UrlGeneratorContract::class);
        $middleware = $this->app->make(TrustProxies::class);

        try {
            $middleware->handle($request, function () use ($url) {
                $this->assertSame('https://example.com/profile', $url->to('profile'));
                throw new \RuntimeException('Request failed.');
            });
            $this->fail('The exception was not propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Request failed.', $exception->getMessage());
        }

        $this->assertSame('https://example.com/profile', $url->to('profile'));
        $url->useOrigin(null);
        $url->forceScheme(null);
        $this->assertSame('http://first.example/profile', $url->to('profile'));
        $request = Request::create('http://second.example/');
        $this->app->instance('request', $request);
        Request::setTrustedHosts(['^second\.example$']);

        try {
            $middleware->handle($request, function () use ($url) {
                $this->assertSame('http://second.example/profile', $url->to('profile'));
            });
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    #[TestWith(['http://custom.example', 'http', 'http://custom.example/profile'])]
    #[TestWith(['http://custom.example', null, 'https://custom.example/profile'])]
    #[TestWith([null, 'http', 'http://example.com/profile'])]
    public function testExplicitOriginAndSchemeArePreserved($origin, $scheme, $expected)
    {
        $this->app['config']->set('app.url', 'https://example.com');
        $request = Request::create('http://request.example/');
        $this->app->instance('request', $request);
        $url = $this->app->make(UrlGeneratorContract::class);
        $url->useOrigin($origin);
        $url->forceScheme($scheme);

        $this->app->make(TrustProxies::class)->handle($request, function () use ($url, $expected) {
            $this->assertSame($expected, $url->to('profile'));
        });
    }

    #[TestWith(['http://custom.example', null, 'https://custom.example/profile'])]
    #[TestWith([null, 'http', 'http://example.com/profile'])]
    #[TestWith(['http://custom.example', 'http', 'http://custom.example/profile'])]
    public function testExplicitOverridesMadeDuringTheRequestArePreserved($origin, $scheme, $expected)
    {
        $this->app['config']->set('app.url', 'https://example.com');
        $request = Request::create('http://request.example/');
        $this->app->instance('request', $request);
        $url = $this->app->make(UrlGeneratorContract::class);

        $this->app->make(TrustProxies::class)->handle($request, function () use ($url, $origin, $scheme) {
            if ($origin !== null) {
                $url->useOrigin($origin);
            }

            if ($scheme !== null) {
                $url->forceScheme($scheme);
            }
        });

        $this->assertSame($expected, $url->to('profile'));
    }

    #[TestWith(['http://localhost'])]
    #[TestWith(['http://localhost/'])]
    #[TestWith(['http://localhost:8000'])]
    public function testLocalhostApplicationUrlUsesRequestOrigin($appUrl)
    {
        $this->app['config']->set('app.url', $appUrl);
        $this->app->forgetInstance('url');
        $this->app->instance('request', Request::create('https://request.example/'));

        $url = $this->app->make(UrlGeneratorContract::class);
        $this->app->make(TrustProxies::class)->handle($this->app['request'], function () use ($url) {
            $this->assertSame('https://request.example/profile', $url->to('profile'));
        });
    }

    public function testLocalhostPrefixedDomainUsesConfiguredOrigin()
    {
        $this->app['config']->set('app.url', 'http://localhost.example');
        $this->app->forgetInstance('url');
        $this->app->instance('request', Request::create('https://request.example/'));

        $url = $this->app->make(UrlGeneratorContract::class);
        $this->app->make(TrustProxies::class)->handle($this->app['request'], function () use ($url) {
            $this->assertSame('https://localhost.example/profile', $url->to('profile'));
        });
    }

    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith(['example.com'])]
    #[TestWith(['ftp://example.com'])]
    #[TestWith(['https://example.com:99999'])]
    #[TestWith(['https://example.com/path?query=value'])]
    #[TestWith(['https://example.com/path?'])]
    #[TestWith(['https://example.com/path#fragment'])]
    #[TestWith(['https://example.com/path#'])]
    #[TestWith(['HTTPS://example.com'])]
    #[TestWith(['https://example.com/invalid path'])]
    public function testInvalidApplicationUrlUsesRequestOrigin($appUrl)
    {
        $this->app['config']->set('app.url', $appUrl);
        $this->app->forgetInstance('url');
        $this->app->instance('request', Request::create('https://request.example/'));

        $url = $this->app->make(UrlGeneratorContract::class);
        $this->app->make(TrustProxies::class)->handle($this->app['request'], function () use ($url) {
            $this->assertSame('https://request.example/profile', $url->to('profile'));
        });
    }

    public function testResolvingPsrRequest()
    {
        $psrRequest = $this->app->make(ServerRequestInterface::class);

        $this->assertInstanceOf(ServerRequestInterface::class, $psrRequest);
    }

    public function testResolvingPsrResponse()
    {
        $psrResponse = $this->app->make(ResponseInterface::class);

        $this->assertInstanceOf(ResponseInterface::class, $psrResponse);
    }
}
