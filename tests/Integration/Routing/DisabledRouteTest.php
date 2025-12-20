<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class DisabledRouteTest extends TestCase
{
    public function testRouteCanBeDisabledWithDefaultMessage()
    {
        Route::get('/disabled', function () {
            return 'This should not be returned';
        })->disabled();

        $response = $this->get('/disabled');

        $response->assertStatus(503);
        $this->assertSame('This route is temporarily disabled.', $response->content());
    }

    public function testRouteCanBeDisabledWithCustomMessage()
    {
        Route::get('/custom-disabled', function () {
            return 'This should not be returned';
        })->disabled('Feature is under maintenance');

        $response = $this->get('/custom-disabled');

        $response->assertStatus(503);
        $this->assertSame('Feature is under maintenance', $response->content());
    }

    public function testRouteCanBeDisabledWithCallback()
    {
        Route::get('/callback-disabled', function () {
            return 'This should not be returned';
        })->disabled(function ($request) {
            return response()->json([
                'message' => 'Route disabled',
                'path' => $request->path(),
            ], 503);
        });

        $response = $this->get('/callback-disabled');

        $response->assertStatus(503);
        $response->assertJson([
            'message' => 'Route disabled',
            'path' => 'callback-disabled',
        ]);
    }

    public function testEnabledRouteWorksNormally()
    {
        Route::get('/enabled', function () {
            return 'This should work';
        });

        $response = $this->get('/enabled');

        $response->assertStatus(200);
        $this->assertSame('This should work', $response->content());
    }

    public function testDisabledRouteWithPostMethod()
    {
        Route::post('/post-disabled', function () {
            return 'This should not be returned';
        })->disabled('POST endpoint disabled');

        $response = $this->post('/post-disabled');

        $response->assertStatus(503);
        $this->assertSame('POST endpoint disabled', $response->content());
    }

    public function testDisabledRouteWithEmptyStringUsesDefaultMessage()
    {
        Route::get('/empty-message', function () {
            return 'This should not be returned';
        })->disabled('');

        $response = $this->get('/empty-message');

        $response->assertStatus(503);
        $this->assertSame('This route is temporarily disabled.', $response->content());
    }

    public function testDisabledRouteWorksWithRouteGroups()
    {
        Route::prefix('admin')->group(function () {
            Route::get('/users', function () {
                return 'User list';
            })->disabled('Admin area under maintenance');

            Route::get('/posts', function () {
                return 'Post list';
            });
        });

        $disabledResponse = $this->get('/admin/users');
        $disabledResponse->assertStatus(503);
        $this->assertSame('Admin area under maintenance', $disabledResponse->content());

        $enabledResponse = $this->get('/admin/posts');
        $enabledResponse->assertStatus(200);
        $this->assertSame('Post list', $enabledResponse->content());
    }

    public function testDisabledClosureCanBeSerializedForRouteCaching()
    {
        Route::get('/serialized-closure', function () {
            return 'This should not be returned';
        })->disabled(function ($request) {
            return response()->json([
                'message' => 'Serialized closure works',
                'method' => $request->method(),
            ], 503);
        });

        $routes = Route::getRoutes();
        $route = $routes->getByAction('GET', '/serialized-closure');

        if (! $route) {
            foreach ($routes as $r) {
                if ($r->uri() === 'serialized-closure' && in_array('GET', $r->methods())) {
                    $route = $r;
                    break;
                }
            }
        }

        $this->assertNotNull($route, 'Route not found');

        $route->prepareForSerialization();

        // Verify the closure was serialized
        $this->assertIsString($route->getAction('disabled'));
        $this->assertStringStartsWith('O:', $route->getAction('disabled'));

        // Verify the getDisabled() method deserializes it correctly
        $disabled = $route->getDisabled();
        $this->assertInstanceOf(\Closure::class, $disabled);

        // Verify the deserialized closure works
        $mockRequest = $this->app->make('request');
        $response = $disabled($mockRequest);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testDisabledStringMessageSerializationDoesNotAffectNormalStrings()
    {
        Route::get('/normal-string', function () {
            return 'This should not be returned';
        })->disabled('Normal string message');

        $routes = Route::getRoutes();
        $route = null;
        foreach ($routes as $r) {
            if ($r->uri() === 'normal-string' && in_array('GET', $r->methods())) {
                $route = $r;
                break;
            }
        }

        $this->assertNotNull($route, 'Route not found');

        $route->prepareForSerialization();

        // Verify normal strings are not affected
        $this->assertSame('Normal string message', $route->getAction('disabled'));
        $this->assertSame('Normal string message', $route->getDisabled());
    }

    public function testDisabledBooleanValueSerializationDoesNotAffectBooleans()
    {
        Route::get('/boolean-true', function () {
            return 'This should not be returned';
        })->disabled(true);

        $routes = Route::getRoutes();
        $route = null;
        foreach ($routes as $r) {
            if ($r->uri() === 'boolean-true' && in_array('GET', $r->methods())) {
                $route = $r;
                break;
            }
        }

        $this->assertNotNull($route, 'Route not found');

        $route->prepareForSerialization();

        // Verify booleans are not affected
        $this->assertTrue($route->getAction('disabled'));
        $this->assertTrue($route->getDisabled());
    }

    public function testDisabledCallbackReturningNullAllowsRouteToExecute()
    {
        Route::get('/conditional-disabled', function () {
            return 'Route executed';
        })->disabled(function ($request) {
            // Return null to allow route execution
            return null;
        });

        $response = $this->get('/conditional-disabled');

        $response->assertStatus(200);
        $this->assertSame('Route executed', $response->content());
    }
}
