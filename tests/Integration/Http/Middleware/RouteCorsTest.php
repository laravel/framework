<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Attributes\Cors;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase;

class RouteCorsTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']['cors'] = [
            'paths' => ['api/*'],
            'supports_credentials' => false,
            'allowed_origins' => ['http://global.example.com'],
            'allowed_headers' => ['X-Global-Header'],
            'allowed_methods' => ['GET', 'POST'],
            'exposed_headers' => [],
            'max_age' => 0,
        ];

        $kernel = $app->make(Kernel::class);
        $kernel->prependMiddleware(HandleCors::class);
    }

    protected function defineRoutes($router)
    {
        $router->get('api/route-cors', ['uses' => fn () => 'OK'])
            ->cors(['origins' => ['https://app.example.com'], 'methods' => ['GET']]);

        $router->post('api/route-cors', ['uses' => fn () => 'OK'])
            ->cors(['origins' => ['https://app.example.com'], 'methods' => ['GET', 'POST']]);

        $router->get('api/global-cors', ['uses' => fn () => 'GLOBAL']);

        $router->get('api/sibling', ['uses' => fn () => 'SIBLING'])
            ->cors(['origins' => ['https://sibling.example.com']]);

        $router->get('web/no-cors', ['uses' => fn () => 'WEB']);

        $router->prefix('api/grouped')->cors([
            'origins' => ['https://group.example.com'],
            'methods' => ['GET', 'POST'],
        ])->group(function (Router $router) {
            $router->get('child', ['uses' => fn () => 'CHILD']);

            $router->get('override', ['uses' => fn () => 'OVERRIDE'])
                ->cors(['origins' => ['https://override.example.com']]);
        });

        $router->get('api/controller-cors', [ControllerWithClassCors::class, 'index']);

        $router->get('api/method-cors', [ControllerWithMethodCors::class, 'specific']);

        $router->get('api/attr-over-route', [ControllerWithClassCors::class, 'index'])
            ->cors(['origins' => ['https://route-loses.example.com']]);
    }

    public function testRouteLevelCorsOnActualRequest()
    {
        $this->call('GET', 'api/route-cors', server: [
            'HTTP_ORIGIN' => 'https://app.example.com',
        ])->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');
    }

    public function testRouteLevelCorsPreflightRequest()
    {
        $this->call('OPTIONS', 'api/route-cors', server: [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');
    }

    public function testRouteLevelCorsPreflightWithPostMethod()
    {
        $this->call('OPTIONS', 'api/route-cors', server: [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://app.example.com');
    }

    public function testFallbackToGlobalConfigWhenNoRouteCors()
    {
        $this->call('GET', 'api/global-cors', server: [
            'HTTP_ORIGIN' => 'http://global.example.com',
        ])->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://global.example.com');
    }

    public function testFallbackToGlobalConfigOnPreflight()
    {
        $this->call('OPTIONS', 'api/global-cors', server: [
            'HTTP_ORIGIN' => 'http://global.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://global.example.com');
    }

    public function testRouteCorsDoesNotBleedIntoSiblingRoute()
    {
        $response = $this->call('GET', 'api/sibling', server: [
            'HTTP_ORIGIN' => 'https://app.example.com',
        ]);

        $this->assertNotSame('https://app.example.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testRouteCorsReplacesGlobalDefaults()
    {
        $response = $this->call('GET', 'api/route-cors', server: [
            'HTTP_ORIGIN' => 'http://global.example.com',
        ]);

        $this->assertNotSame('http://global.example.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testNoCorsHeadersOnWebRoute()
    {
        $this->call('GET', 'web/no-cors', server: [
            'HTTP_ORIGIN' => 'http://anywhere.com',
        ])->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function testGroupCorsAppliesToChildRoutes()
    {
        $this->call('GET', 'api/grouped/child', server: [
            'HTTP_ORIGIN' => 'https://group.example.com',
        ])->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://group.example.com');
    }

    public function testChildRouteCanOverrideGroupCors()
    {
        $this->call('GET', 'api/grouped/override', server: [
            'HTTP_ORIGIN' => 'https://override.example.com',
        ])->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://override.example.com');
    }

    public function testGroupCorsOriginsNotAllowedOnOverrideRoute()
    {
        $response = $this->call('GET', 'api/grouped/override', server: [
            'HTTP_ORIGIN' => 'https://group.example.com',
        ]);

        $this->assertNotSame('https://group.example.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testControllerClassAttributeCors()
    {
        $this->call('GET', 'api/controller-cors', server: [
            'HTTP_ORIGIN' => 'https://class-attr.example.com',
        ])->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://class-attr.example.com');
    }

    public function testControllerMethodAttributeOverridesClassAttribute()
    {
        $this->call('GET', 'api/method-cors', server: [
            'HTTP_ORIGIN' => 'https://method-attr.example.com',
        ])->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://method-attr.example.com');
    }

    public function testControllerAttributeOverridesRouteAction()
    {
        $this->call('GET', 'api/attr-over-route', server: [
            'HTTP_ORIGIN' => 'https://class-attr.example.com',
        ])->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://class-attr.example.com');
    }

    public function testRouteActionIgnoredWhenControllerAttributeExists()
    {
        $response = $this->call('GET', 'api/attr-over-route', server: [
            'HTTP_ORIGIN' => 'https://route-loses.example.com',
        ]);

        $this->assertNotSame('https://route-loses.example.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testPreflightOnGroupCorsRoute()
    {
        $this->call('OPTIONS', 'api/grouped/child', server: [
            'HTTP_ORIGIN' => 'https://group.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://group.example.com');
    }

    public function testPreflightOnControllerAttributeRoute()
    {
        $this->call('OPTIONS', 'api/controller-cors', server: [
            'HTTP_ORIGIN' => 'https://class-attr.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://class-attr.example.com');
    }

    public function testRouteCorsWithCredentials()
    {
        $this->app['router']->get('api/creds', ['uses' => fn () => 'OK'])
            ->cors(['origins' => ['https://creds.example.com'], 'credentials' => true]);

        $this->call('GET', 'api/creds', server: [
            'HTTP_ORIGIN' => 'https://creds.example.com',
        ])->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}

#[Cors(origins: ['https://class-attr.example.com'])]
class ControllerWithClassCors
{
    public function index()
    {
        return 'CLASS_CORS';
    }
}

#[Cors(origins: ['https://class-attr.example.com'])]
class ControllerWithMethodCors
{
    #[Cors(origins: ['https://method-attr.example.com'])]
    public function specific()
    {
        return 'METHOD_CORS';
    }
}
