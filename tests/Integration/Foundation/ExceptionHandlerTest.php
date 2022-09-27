<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class ExceptionHandlerTest extends TestCase
{
    public function testItRendersAuthorizationExceptions()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(403)
            ->assertSeeText('expected message');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(403)
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithCustomStatusCode()
    {
        Route::get('test-route', fn () => Response::deny('expected message', 321)->withStatus(404)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(404)
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(404)
            ->assertExactJson([
                'message' => 'expected message',
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusCodeTextWhenNoMessageIsSet()
    {
        Route::get('test-route', fn () => Response::denyWithStatus(404)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(404)
            ->assertSeeText('Not Found');

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(404)
            ->assertExactJson([
                'message' => 'Not Found',
            ]);

        Route::get('test-route', fn () => Response::denyWithStatus(418)->authorize());

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(418)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(418)
            ->assertExactJson([
                'message' => "I'm a teapot",
            ]);
    }

    public function testItRendersAuthorizationExceptionsWithStatusButWithoutResponse()
    {
        Route::get('test-route', fn () => throw (new AuthorizationException())->withStatus(418));

        // HTTP request...
        $this->get('test-route')
            ->assertStatus(418)
            ->assertSeeText("I'm a teapot", false);

        // JSON request...
        $this->getJson('test-route')
            ->assertStatus(418)
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
