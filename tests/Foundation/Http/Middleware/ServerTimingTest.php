<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ServerTiming;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;

class ServerTimingTest extends TestCase
{
    public function testServerTimingHeader()
    {
        $middleware = new ServerTiming();
        $request = Request::createFromBase(new SymfonyRequest());

        $response = $middleware->handle($request, function () {
            return new Response();
        });

        self::assertMatchesRegularExpression('/^total;desc="Request execution time";dur=\d+/', $response->headers->get('Server-Timing'));
    }
}

