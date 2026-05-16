<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Orchestra\Testbench\TestCase;

class HandleCorsTest extends TestCase
{
    use ValidatesRequests;

    protected function setUp(): void
    {
        parent::setUp();

        SpyMiddleware::$invocations = 0;
    }

    protected function defineEnvironment($app)
    {
        $app['config']['cors'] = [
            'paths' => ['api/*'],
            'supports_credentials' => false,
            'allowed_origins' => ['http://localhost'],
            'allowed_headers' => ['X-Custom-1', 'X-Custom-2'],
            'allowed_methods' => ['GET', 'POST'],
            'exposed_headers' => [],
            'max_age' => 0,
        ];

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', ['driver' => 'handle-cors-null']);

        $app['auth']->viaRequest('handle-cors-null', fn () => null);

        $kernel = $app->make(Kernel::class);
        $kernel->prependMiddleware(HandleCors::class);
    }

    protected function defineRoutes($router)
    {
        $this->addWebRoutes($router);
        $this->addApiRoutes($router);
    }

    public function testShouldReturnHeaderAssessControlAllowOriginWhenDontHaveHttpOriginOnRequest()
    {
        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(204, $crawler->getStatusCode());
    }

    public function testOptionsAllowOriginAllowed()
    {
        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(204, $crawler->getStatusCode());
    }

    public function testAllowAllOrigins()
    {
        $this->app['config']->set('cors.allowed_origins', ['*']);

        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://laravel.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('*', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(204, $crawler->getStatusCode());
    }

    public function testAllowAllOriginsWildcard()
    {
        $this->app['config']->set('cors.allowed_origins', ['*.laravel.com']);

        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://test.laravel.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('http://test.laravel.com', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(204, $crawler->getStatusCode());
    }

    public function testOriginsWildcardIncludesNestedSubdomains()
    {
        $this->app['config']->set('cors.allowed_origins', ['*.laravel.com']);

        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://api.service.test.laravel.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('http://api.service.test.laravel.com', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(204, $crawler->getStatusCode());
    }

    public function testAllowAllOriginsWildcardNoMatch()
    {
        $this->app['config']->set('cors.allowed_origins', ['*.laravel.com']);

        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://test.symfony.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertEquals(null, $crawler->headers->get('Access-Control-Allow-Origin'));
    }

    public function testOptionsAllowOriginAllowedNonExistingRoute()
    {
        $crawler = $this->call('OPTIONS', 'api/pang', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(204, $crawler->getStatusCode());
    }

    public function testOptionsAllowOriginNotAllowed()
    {
        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://otherhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
    }

    public function testAllowMethodAllowed()
    {
        $crawler = $this->call('POST', 'web/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);
        $this->assertEquals(null, $crawler->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals(200, $crawler->getStatusCode());

        $this->assertSame('PONG', $crawler->getContent());
    }

    public function testAllowMethodNotAllowed()
    {
        $crawler = $this->call('POST', 'web/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PUT',
        ]);
        $this->assertEquals(null, $crawler->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals(200, $crawler->getStatusCode());
    }

    public function testAllowHeaderAllowedOptions()
    {
        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-custom-1, x-custom-2',
        ]);
        $this->assertSame('x-custom-1, x-custom-2', $crawler->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals(204, $crawler->getStatusCode());

        $this->assertSame('', $crawler->getContent());
    }

    public function testAllowHeaderAllowedWildcardOptions()
    {
        $this->app['config']->set('cors.allowed_headers', ['*']);

        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-custom-3',
        ]);
        $this->assertSame('x-custom-3', $crawler->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals(204, $crawler->getStatusCode());

        $this->assertSame('', $crawler->getContent());
    }

    public function testAllowHeaderNotAllowedOptions()
    {
        $crawler = $this->call('OPTIONS', 'api/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-custom-3',
        ]);
        $this->assertSame('x-custom-1, x-custom-2', $crawler->headers->get('Access-Control-Allow-Headers'));
    }

    public function testAllowHeaderAllowed()
    {
        $crawler = $this->call('POST', 'web/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-custom-1, x-custom-2',
        ]);
        $this->assertEquals(null, $crawler->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals(200, $crawler->getStatusCode());

        $this->assertSame('PONG', $crawler->getContent());
    }

    public function testAllowHeaderAllowedWildcard()
    {
        $this->app['config']->set('cors.allowed_headers', ['*']);

        $crawler = $this->call('POST', 'web/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-custom-3',
        ]);
        $this->assertEquals(null, $crawler->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals(200, $crawler->getStatusCode());

        $this->assertSame('PONG', $crawler->getContent());
    }

    public function testAllowHeaderNotAllowed()
    {
        $crawler = $this->call('POST', 'web/ping', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'x-custom-3',
        ]);
        $this->assertEquals(null, $crawler->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals(200, $crawler->getStatusCode());
    }

    public function testError()
    {
        $crawler = $this->call('POST', 'api/error', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(500, $crawler->getStatusCode());
    }

    public function testValidationException()
    {
        $crawler = $this->call('POST', 'api/validation', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);
        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(302, $crawler->getStatusCode());
    }

    public function testPreflightPassesThroughAuthMiddleware()
    {
        $crawler = $this->call('OPTIONS', 'api/protected', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertEquals(204, $crawler->getStatusCode());
        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
    }

    public function testPreflightDoesNotInvokeDownstreamMiddleware()
    {
        $this->app['router']->post('api/spy', ['uses' => fn () => 'OK'])
            ->middleware(SpyMiddleware::class);

        $crawler = $this->call('OPTIONS', 'api/spy', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertEquals(204, $crawler->getStatusCode());
        $this->assertSame('http://localhost', $crawler->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame(0, SpyMiddleware::$invocations, 'Downstream route middleware must not run on preflight requests.');
    }

    public function testPreflightPassesThroughThrottleMiddleware()
    {
        RateLimiter::for('cors-preflight', fn () => Limit::perMinute(1));

        $this->app['router']->post('api/throttled', ['uses' => fn () => 'OK'])
            ->middleware(ThrottleRequests::class.':cors-preflight');

        $first = $this->call('OPTIONS', 'api/throttled', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertEquals(204, $first->getStatusCode());
        $this->assertSame('http://localhost', $first->headers->get('Access-Control-Allow-Origin'));

        $second = $this->call('OPTIONS', 'api/throttled', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertEquals(204, $second->getStatusCode());
        $this->assertSame('http://localhost', $second->headers->get('Access-Control-Allow-Origin'));
    }

    public function testNonCorsOptionsReturnsAllowHeader()
    {
        $crawler = $this->call('OPTIONS', 'api/ping');

        $this->assertEquals(200, $crawler->getStatusCode());
        $this->assertSame('POST,PUT', $crawler->headers->get('Allow'));
    }

    protected function addWebRoutes(Router $router)
    {
        $router->post('web/ping', [
            'uses' => function () {
                return 'PONG';
            },
        ]);
    }

    protected function addApiRoutes(Router $router)
    {
        $router->post('api/ping', [
            'uses' => function () {
                return 'PONG';
            },
        ]);

        $router->put('api/ping', [
            'uses' => function () {
                return 'PONG';
            },
        ]);

        $router->post('api/error', [
            'uses' => function () {
                abort(500);
            },
        ]);

        $router->post('api/validation', [
            'uses' => function (Request $request) {
                $this->validate($request, [
                    'name' => 'required',
                ]);

                return 'ok';
            },
        ]);

        $router->post('api/protected', [
            'middleware' => Authenticate::class.':web',
            'uses' => fn () => 'PROTECTED',
        ]);
    }
}

class SpyMiddleware
{
    public static int $invocations = 0;

    public function handle($request, \Closure $next)
    {
        self::$invocations++;

        return $next($request);
    }
}
