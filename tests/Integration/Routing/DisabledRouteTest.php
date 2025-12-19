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
}
