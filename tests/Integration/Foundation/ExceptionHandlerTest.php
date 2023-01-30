<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class ExceptionHandlerTest extends TestCase
{
    public function testItRendersAuthorizationExceptions()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertForbidden()
            ->assertSeeText('expected message');

        // JSON request...
        $this->getJson('test-route')
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithCustomStatusCode()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->withStatus(404)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertNotFound()
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusCodeTextWhenNoMessageIsSet()
    {
        Route::get('test-route', fn () => Response::denyWithStatus(HttpResponse::HTTP_NOT_FOUND)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertNotFound()
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Not Found',
            ]);

        Route::get('test-route', fn () => Response::denyWithStatus(HttpResponse::HTTP_I_AM_A_TEAPOT)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(HttpResponse::HTTP_I_AM_A_TEAPOT)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(HttpResponse::HTTP_I_AM_A_TEAPOT)
            ->assertExactJson([
                'message' => "I'm a teapot",
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusButWithoutResponse()
    {
        Route::get('test-route', fn () => throw (new AuthorizationException())->withStatus(HttpResponse::HTTP_I_AM_A_TEAPOT));

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(HttpResponse::HTTP_I_AM_A_TEAPOT)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(HttpResponse::HTTP_I_AM_A_TEAPOT)
            ->assertExactJson([
                'message' => "I'm a teapot",
            ]);
    }

    public function testItHasFallbackErrorMessageForUnknownStatusCodes()
    {
        Route::get('test-route', fn () => throw (new AuthorizationException())->withStatus(399));

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(399)
            ->assertSeeText('Whoops, looks like something went wrong.');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(399)
            ->assertExactJson([
                'message' => 'Whoops, looks like something went wrong.',
            ]);
    }
}
