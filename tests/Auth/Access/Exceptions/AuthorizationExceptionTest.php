<?php

namespace Illuminate\Tests\Auth\Access\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use PHPUnit\Framework\TestCase;

class AuthorizationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new AuthorizationException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionDefaultsToUnauthorizedMessage(): void
    {
        $exception = new AuthorizationException;

        $this->assertSame('This action is unauthorized.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new AuthorizationException('Custom message.', 'ability-code', $previous);

        $this->assertSame('Custom message.', $exception->getMessage());
        $this->assertSame('ability-code', $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testSetResponseIsFluentAndAssignsResponse(): void
    {
        $exception = new AuthorizationException;
        $response = Response::deny('Denied.');

        $result = $exception->setResponse($response);

        $this->assertSame($exception, $result);
        $this->assertSame($response, $exception->response());
    }

    public function testWithStatusIsFluentAndAssignsStatus(): void
    {
        $exception = new AuthorizationException;

        $this->assertFalse($exception->hasStatus());

        $result = $exception->withStatus(404);

        $this->assertSame($exception, $result);
        $this->assertTrue($exception->hasStatus());
        $this->assertSame(404, $exception->status());
    }

    public function testAsNotFoundSetsStatusTo404(): void
    {
        $exception = new AuthorizationException;

        $exception->asNotFound();

        $this->assertSame(404, $exception->status());
    }

    public function testToResponseBuildsDenyResponseWithMessageCodeAndStatus(): void
    {
        $exception = new AuthorizationException('Custom message.', 'ability-code');
        $exception->withStatus(403);

        $response = $exception->toResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->allowed());
        $this->assertSame('Custom message.', $response->message());
        $this->assertSame('ability-code', $response->code());
        $this->assertSame(403, $response->status());
    }
}
