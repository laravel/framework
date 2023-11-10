<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase;

class HandleCorsTest extends TestCase
{
    use ValidatesRequests;

    protected function getEnvironmentSetUp($app)
    {
        $kernel = $app->make(Kernel::class);
        $kernel->prependMiddleware(HandleCors::class);

        $router = $app['router'];

        $this->addWebRoutes($router);
        $this->addApiRoutes($router);

        parent::getEnvironmentSetUp($app);
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']['cors'] = [
            'paths' => ['api/*'],
            'supports_credentials' => false,
            'allowed_origins' => ['http://localhost'],
            'allowed_headers' => ['X-Custom-1', 'X-Custom-2'],
            'allowed_methods' => ['GET', 'POST'],
            'exposed_headers' => [],
            'max_age' => 0,
        ];
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
    }
}
