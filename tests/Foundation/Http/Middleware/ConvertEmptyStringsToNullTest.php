<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;

class ConvertEmptyStringsToNullTest extends TestCase
{
    public function testConvertEmptyStringsToNull()
    {
        $middleware = new ConvertEmptyStringsToNull();
        $symfonyRequest = new SymfonyRequest([
            'empty' => '',
            'null' => null,
            'toString' => new ToString(),
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertNull($request->get('empty'));
            $this->assertNull($request->get('null'));
            $this->assertInstanceOf(ToString::class, $request->get('toString'));
        });
    }
}

class ToString
{
    public function __toString()
    {
        return '';
    }
}
