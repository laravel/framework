<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

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

        $this->app->instance('request', Request::create('https://another-attacker.example/'));

        $this->assertSame('https://example.com:8443/base/profile', $url->route('profile'));
    }

    public function testApplicationUrlIsUsedOnlyWhenTrustedHostsAreNotActive()
    {
        Request::setTrustedHosts([]);

        try {
            $this->app['config']->set('app.url', 'https://example.com:8443/base');
            $this->app->forgetInstance('url');
            $request = Request::create('http://tenant.example.com:8080/');
            $this->app->instance('request', $request);
            $this->app['router']->get('profile', fn () => '')->name('profile');
            $this->app['router']->domain('other.example')->get('domain', fn () => '')->name('domain');

            $url = $this->app->make(UrlGeneratorContract::class);
            $this->assertSame('https://example.com:8443/base/profile', $url->route('profile'));
            $this->assertSame('https://other.example:8443/domain', $url->route('domain'));

            $middleware = new class($this->app) extends TrustHosts
            {
                protected function shouldSpecifyTrustedHosts()
                {
                    return true;
                }
            };
            $middleware->handle($request, fn () => null);

            $this->assertSame('http://tenant.example.com:8080/profile', $url->route('profile'));
            $this->assertSame('http://other.example:8080/domain', $url->route('domain'));
            $this->assertSame('/profile', $url->route('profile', [], false));

            Request::setTrustedHosts([]);
            $this->assertSame('https://example.com:8443/base/profile', $url->route('profile'));

            $middleware->handle($request, fn () => null);
            $this->app->instance('request', Request::create('http://attacker.example/'));

            $this->expectException(SuspiciousOperationException::class);
            $url->to('profile');
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    #[TestWith(['http://localhost'])]
    #[TestWith(['http://localhost/'])]
    public function testDefaultApplicationUrlUsesRequestOrigin($appUrl)
    {
        $this->app['config']->set('app.url', $appUrl);
        $this->app->forgetInstance('url');
        $this->app->instance('request', Request::create('https://request.example/'));

        $this->assertSame('https://request.example/profile', $this->app->make(UrlGeneratorContract::class)->to('profile'));
    }

    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith(['example.com'])]
    #[TestWith(['ftp://example.com'])]
    #[TestWith(['https://example.com:99999'])]
    #[TestWith(['https://example.com/path?query=value'])]
    #[TestWith(['https://example.com/path#fragment'])]
    #[TestWith(['https://example.com/invalid path'])]
    public function testInvalidApplicationUrlUsesRequestOrigin($appUrl)
    {
        $this->app['config']->set('app.url', $appUrl);
        $this->app->forgetInstance('url');
        $this->app->instance('request', Request::create('https://request.example/'));

        $this->assertSame('https://request.example/profile', $this->app->make(UrlGeneratorContract::class)->to('profile'));
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
