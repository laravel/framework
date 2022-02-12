<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ConvertStringBooleansToBoolean;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class ConvertStringBooleansToBooleanTest extends TestCase
{
    public function testConvertsStringBooleansToTrueBooleans()
    {
        $middleware = new ConvertStringBooleansToBoolean;
        $symfonyRequest = new SymfonyRequest([
            'foo' => 'value',
            'bar' => 1,
            'baz' => 'true',
            'qux' => 'false',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('value', $request->get('foo'));
            $this->assertSame(1, $request->get('bar'));
            $this->assertTrue($request->get('baz'));
            $this->assertFalse($request->get('qux'));
        });
    }
}
