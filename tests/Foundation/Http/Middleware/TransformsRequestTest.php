<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TransformsRequestTest extends TestCase
{
    public function testTransformOncePerKeyWhenMethodIsGet()
    {
        $middleware = new TruncateInput;
        $symfonyRequest = new SymfonyRequest([
            'bar' => '123',
            'baz' => 'abc',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('12', $request->get('bar'));
            $this->assertEquals('ab', $request->get('baz'));
        });
    }

    public function testTransformOncePerKeyWhenMethodIsPost()
    {
        $middleware = new ManipulateInput;
        $symfonyRequest = new SymfonyRequest(
            [
                'name' => 'Damian',
                'beers' => 4,
            ],
            ['age' => 28]
        );
        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('Damian', $request->get('name'));
            $this->assertEquals(27, $request->get('age'));
            $this->assertEquals(5, $request->get('beers'));
        });
    }

    public function testTransformOncePerKeyWhenContentTypeIsJson()
    {
        $middleware = new ManipulateInput;
        $symfonyRequest = new SymfonyRequest(
            [
                'name' => 'Damian',
                'beers' => 4,
            ],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => '/json'],
            json_encode(['age' => 28])
        );
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('Damian', $request->input('name'));
            $this->assertEquals(27, $request->input('age'));
            $this->assertEquals(5, $request->input('beers'));
        });
    }
}

class ManipulateInput extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if ($key === 'beers') {
            $value++;
        }
        if ($key === 'age') {
            $value--;
        }

        return $value;
    }
}

class TruncateInput extends TransformsRequest
{
    protected function transform($key, $value)
    {
        return substr($value, 0, -1);
    }
}
