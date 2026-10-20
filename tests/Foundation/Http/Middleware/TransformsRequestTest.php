<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TransformsRequestTest extends TestCase
{
    #[DataProvider('amplifiedInputProvider')]
    public function testRejectsAmplifiedJsonKeys($middlewareClass, $value)
    {
        $key = str_repeat('è', 197499);
        $request = Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            $key => array_fill(0, 100, $value),
        ], JSON_UNESCAPED_UNICODE));

        try {
            (new $middlewareClass)->handle($request, function () {
                $this->fail('An amplified request should not reach the next middleware.');
            });

            $this->fail('An amplified request should be rejected.');
        } catch (PostTooLargeException $e) {
            $this->assertSame(413, $e->getStatusCode());
        }
    }

    public static function amplifiedInputProvider()
    {
        foreach ([TransformsRequest::class, TrimStrings::class, ConvertEmptyStringsToNull::class] as $middleware) {
            foreach ([0, ' value ', '', []] as $value) {
                yield [$middleware, $value];
            }
        }
    }

    public function testKeyBudgetIncludesNestedPathsAndPreservesDottedKeys()
    {
        $middleware = new class extends TransformsRequest
        {
            protected $maxKeyBytes = 10;

            protected function transform($key, $value)
            {
                return $key;
            }
        };

        // The processed keys are "foo" (3 bytes) and "foo.bar" (7 bytes).
        $middleware->handle(new Request(['foo' => ['bar' => 'value']]), function ($request) {
            $this->assertSame('foo.bar', $request->input('foo.bar'));
        });

        $this->expectException(PostTooLargeException::class);

        $middleware->handle(new Request(['foo' => ['bars' => 'value']]), fn () => null);
    }

    public function testKeyBudgetIsSharedAcrossParameterBags()
    {
        $middleware = new class extends TransformsRequest
        {
            protected $maxKeyBytes = 10;
        };

        $request = Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"foobar": 0}');
        $request->query->set('hello', 0);

        $this->expectException(PostTooLargeException::class);

        $middleware->handle($request, fn () => null);
    }

    public function testKeyBudgetIsSharedAcrossQueryAndFormParameters()
    {
        $middleware = new class extends TransformsRequest
        {
            protected $maxKeyBytes = 10;
        };

        $request = Request::create('/?hello=0', 'POST', ['foobar' => 0]);

        $this->expectException(PostTooLargeException::class);

        $middleware->handle($request, fn () => null);
    }

    public function testKeyBudgetResetsWhenMiddlewareIsReused()
    {
        $middleware = new class extends TransformsRequest
        {
            protected $maxKeyBytes = 10;
        };

        for ($i = 0; $i < 2; $i++) {
            $middleware->handle(new Request(['1234567890' => 'value']), function ($request) {
                $this->assertSame('value', $request->input('1234567890'));
            });
        }

        try {
            $middleware->handle(new Request(['12345678901' => 'value']), fn () => null);

            $this->fail('The key budget should be exceeded.');
        } catch (PostTooLargeException $e) {
            $this->assertSame(413, $e->getStatusCode());
        }

        $middleware->handle(new Request(['1234567890' => 'value']), function ($request) {
            $this->assertSame('value', $request->input('1234567890'));
        });
    }

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
            $this->assertSame('12', $request->get('bar'));
            $this->assertSame('ab', $request->get('baz'));
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
            $this->assertSame('Damian', $request->get('name'));
            $this->assertEquals(27, $request->get('age'));
            $this->assertEquals(5, $request->get('beers'));
        });
    }

    public function testTransformOncePerArrayKeysWhenMethodIsPost()
    {
        $middleware = new ManipulateArrayInput;
        $symfonyRequest = new SymfonyRequest(
            [
                'name' => 'Damian',
                'beers' => [4, 8, 12],
            ],
            [
                'age' => [28, 56, 84],
            ]
        );
        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('Damian', $request->get('name'));
            $this->assertEquals([27, 55, 83], $request->get('age'));
            $this->assertEquals([5, 9, 13], $request->get('beers'));
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
            $this->assertSame('Damian', $request->input('name'));
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

class ManipulateArrayInput extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if (str_contains($key, 'beers')) {
            $value++;
        }

        if (str_contains($key, 'age')) {
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
