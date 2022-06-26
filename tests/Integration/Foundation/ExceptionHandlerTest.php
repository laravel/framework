<?php

namespace Illuminate\Tests\Integration\Foundation;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Auth\User;
use Orchestra\Testbench\Exceptions\Handler;
use Orchestra\Testbench\TestCase;
use Throwable;

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
}
