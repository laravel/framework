<?php

namespace Illuminate\Tests\Auth\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    protected function tearDown(): void
    {
        AuthenticationException::redirectUsing(fn () => null);

        parent::tearDown();
    }

    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new AuthenticationException;

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionDefaultsToUnauthenticatedMessageAndNoGuards(): void
    {
        $exception = new AuthenticationException;

        $this->assertSame('Unauthenticated.', $exception->getMessage());
        $this->assertSame([], $exception->guards());
    }

    public function testExceptionHoldsMessageAndGuards(): void
    {
        $exception = new AuthenticationException('Custom message.', ['web', 'api']);

        $this->assertSame('Custom message.', $exception->getMessage());
        $this->assertSame(['web', 'api'], $exception->guards());
    }

    public function testRedirectToReturnsExplicitPathWhenProvided(): void
    {
        $exception = new AuthenticationException('Unauthenticated.', [], '/login');

        $this->assertSame('/login', $exception->redirectTo(Request::create('/')));
    }

    public function testRedirectToReturnsNullWithoutPathOrCallback(): void
    {
        $exception = new AuthenticationException;

        $this->assertNull($exception->redirectTo(Request::create('/')));
    }

    public function testRedirectToUsesRegisteredCallbackWhenNoExplicitPath(): void
    {
        AuthenticationException::redirectUsing(fn (Request $request) => '/custom-login');

        $exception = new AuthenticationException;

        $this->assertSame('/custom-login', $exception->redirectTo(Request::create('/')));
    }

    public function testRedirectToPrefersExplicitPathOverCallback(): void
    {
        AuthenticationException::redirectUsing(fn (Request $request) => '/custom-login');

        $exception = new AuthenticationException('Unauthenticated.', [], '/login');

        $this->assertSame('/login', $exception->redirectTo(Request::create('/')));
    }
}
