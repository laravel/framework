<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use JsonSerializable;
use Mockery as m;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestResponseTest extends TestCase
{
    public function testAssertViewIs()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'getData' => ['foo' => 'bar'],
            'name' => 'dir.my-view',
        ]);

        $response->assertViewIs('dir.my-view');
    }

    public function testAssertViewHas()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => 'bar'],
        ]);

        $response->assertViewHas('foo');
    }

    public function testAssertViewHasModel()
    {
        $model = new TestModel(['id' => 1]);

        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => $model],
        ]);

        $response->assertViewHas('foo', $model);
    }

    public function testAssertViewHasWithClosure()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => 'bar'],
        ]);

        $response->assertViewHas('foo', function ($value) {
            return $value === 'bar';
        });
    }

    public function testAssertViewHasWithValue()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => 'bar'],
        ]);

        $response->assertViewHas('foo', 'bar');
    }

    public function testAssertViewHasNested()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => [
                'foo' => [
                    'nested' => 'bar',
                ],
            ],
        ]);

        $response->assertViewHas('foo.nested');
    }

    public function testAssertViewHasWithNestedValue()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => [
                'foo' => [
                    'nested' => 'bar',
                ],
            ],
        ]);

        $response->assertViewHas('foo.nested', 'bar');
    }

    public function testAssertViewHasEloquentCollection()
    {
        $collection = new EloquentCollection([
            new TestModel(['id' => 1]),
            new TestModel(['id' => 2]),
            new TestModel(['id' => 3]),
        ]);

        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foos' => $collection],
        ]);

        $response->assertViewHas('foos', $collection);
    }

    public function testAssertViewHasEloquentCollectionRespectsOrder()
    {
        $collection = new EloquentCollection([
            new TestModel(['id' => 3]),
            new TestModel(['id' => 2]),
            new TestModel(['id' => 1]),
        ]);

        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foos' => $collection],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertViewHas('foos', $collection->reverse()->values());
    }

    public function testAssertViewHasEloquentCollectionRespectsType()
    {
        $actual = new EloquentCollection([
            new TestModel(['id' => 1]),
            new TestModel(['id' => 2]),
        ]);

        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foos' => $actual],
        ]);

        $expected = new EloquentCollection([
            new AnotherTestModel(['id' => 1]),
            new AnotherTestModel(['id' => 2]),
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertViewHas('foos', $expected);
    }

    public function testAssertViewHasEloquentCollectionRespectsSize()
    {
        $actual = new EloquentCollection([
            new TestModel(['id' => 1]),
            new TestModel(['id' => 2]),
        ]);

        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foos' => $actual],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertViewHas('foos', $actual->concat([new TestModel(['id' => 3])]));
    }

    public function testAssertViewHasWithArray()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => 'bar'],
        ]);

        $response->assertViewHas(['foo' => 'bar']);
    }

    public function testAssertViewHasAll()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => 'bar'],
        ]);

        $response->assertViewHasAll([
            'foo' => 'bar',
        ]);
    }

    public function testAssertViewMissing()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => 'bar'],
        ]);

        $response->assertViewMissing('baz');
    }

    public function testAssertViewMissingNested()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => [
                'foo' => [
                    'nested' => 'bar',
                ],
            ],
        ]);

        $response->assertViewMissing('foo.baz');
    }

    public function testAssertContent()
    {
        $response = $this->makeMockResponse([
            'render' => 'expected response data',
        ]);

        $response->assertContent('expected response data');

        try {
            $response->assertContent('expected');
            $this->fail('xxxx');
        } catch (AssertionFailedError $e) {
            $this->assertSame('Failed asserting that two strings are identical.', $e->getMessage());
        }

        try {
            $response->assertContent('expected response data with extra');
            $this->fail('xxxx');
        } catch (AssertionFailedError $e) {
            $this->assertSame('Failed asserting that two strings are identical.', $e->getMessage());
        }
    }

    public function testAssertStreamedContent()
    {
        $response = TestResponse::fromBaseResponse(
            new StreamedResponse(function () {
                $stream = fopen('php://memory', 'r+');
                fwrite($stream, 'expected response data');
                rewind($stream);
                fpassthru($stream);
            })
        );

        $response->assertStreamedContent('expected response data');

        try {
            $response->assertStreamedContent('expected');
            $this->fail('xxxx');
        } catch (AssertionFailedError $e) {
            $this->assertSame('Failed asserting that two strings are identical.', $e->getMessage());
        }

        try {
            $response->assertStreamedContent('expected response data with extra');
            $this->fail('xxxx');
        } catch (AssertionFailedError $e) {
            $this->assertSame('Failed asserting that two strings are identical.', $e->getMessage());
        }
    }

    public function testAssertStreamedJsonContent()
    {
        $response = TestResponse::fromBaseResponse(
            new StreamedJsonResponse([
                'data' => $this->yieldTestModels(),
            ])
        );

        $response->assertStreamedJsonContent([
            'data' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
        ]);

        try {
            $response->assertStreamedJsonContent([
                'data' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ]);
            $this->fail('xxxx');
        } catch (AssertionFailedError $e) {
            $this->assertSame('Failed asserting that two strings are identical.', $e->getMessage());
        }

        try {
            $response->assertStreamedContent('not expected response string');
            $this->fail('xxxx');
        } catch (AssertionFailedError $e) {
            $this->assertSame('Failed asserting that two strings are identical.', $e->getMessage());
        }
    }

    public function yieldTestModels()
    {
        yield new TestModel(['id' => 1]);
        yield new TestModel(['id' => 2]);
        yield new TestModel(['id' => 3]);
    }

    public function testAssertSee()
    {
        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSee('foo');
        $response->assertSee(['baz', 'bar']);
    }

    public function testAssertSeeCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSee('item');
        $response->assertSee(['not', 'found']);
    }

    public function testAssertSeeEscaped()
    {
        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertSee('laravel & php');
        $response->assertSee(['php & friends', 'laravel & php']);
    }

    public function testAssertSeeEscapedCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertSee('foo & bar');
        $response->assertSee(['bar & baz', 'baz & qux']);
    }

    public function testAssertSeeInOrder()
    {
        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSeeInOrder(['foo', 'bar', 'baz']);

        $response->assertSeeInOrder(['foo', 'bar', 'baz', 'foo']);
    }

    public function testAssertSeeInOrderCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSeeInOrder(['baz', 'bar', 'foo']);
    }

    public function testAssertSeeInOrderCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSeeInOrder(['foo', 'qux', 'bar', 'baz']);
    }

    public function testAssertSeeText()
    {
        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong>baz<strong>qux</strong>',
        ]);

        $response->assertSeeText('foobar');
        $response->assertSeeText(['bazqux', 'foobar']);
    }

    public function testAssertSeeTextCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong>',
        ]);

        $response->assertSeeText('bazfoo');
        $response->assertSeeText(['bazfoo', 'barqux']);
    }

    public function testAssertSeeTextEscaped()
    {
        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertSeeText('laravel & php');
        $response->assertSeeText(['php & friends', 'laravel & php']);
    }

    public function testAssertSeeTextEscapedCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertSeeText('foo & bar');
        $response->assertSeeText(['foo & bar', 'bar & baz']);
    }

    public function testAssertSeeTextInOrder()
    {
        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong> baz <strong>foo</strong>',
        ]);

        $response->assertSeeTextInOrder(['foobar', 'baz']);

        $response->assertSeeTextInOrder(['foobar', 'baz', 'foo']);
    }

    public function testAssertSeeTextInOrderEscaped()
    {
        $response = $this->makeMockResponse([
            'render' => '<strong>laravel &amp; php</strong> <i>phpstorm &gt; sublime</i>',
        ]);

        $response->assertSeeTextInOrder(['laravel & php', 'phpstorm > sublime']);
    }

    public function testAssertSeeTextInOrderCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong> baz <strong>foo</strong>',
        ]);

        $response->assertSeeTextInOrder(['baz', 'foobar']);
    }

    public function testAssertSeeTextInOrderCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong> baz <strong>foo</strong>',
        ]);

        $response->assertSeeTextInOrder(['foobar', 'qux', 'baz']);
    }

    public function testAssertDontSee()
    {
        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertDontSee('laravel');
        $response->assertDontSee(['php', 'friends']);
    }

    public function testAssertDontSeeCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertDontSee('foo');
        $response->assertDontSee(['baz', 'bar']);
    }

    public function testAssertDontSeeEscaped()
    {
        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertDontSee('foo & bar');
        $response->assertDontSee(['bar & baz', 'foo & bar']);
    }

    public function testAssertDontSeeEscapedCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertDontSee('laravel & php');
        $response->assertDontSee(['php & friends', 'laravel & php']);
    }

    public function testAssertDontSeeText()
    {
        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong>baz<strong>qux</strong>',
        ]);

        $response->assertDontSeeText('laravelphp');
        $response->assertDontSeeText(['phpfriends', 'laravelphp']);
    }

    public function testAssertDontSeeTextCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong>baz<strong>qux</strong>',
        ]);

        $response->assertDontSeeText('foobar');
        $response->assertDontSeeText(['bazqux', 'foobar']);
    }

    public function testAssertDontSeeTextEscaped()
    {
        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertDontSeeText('foo & bar');
        $response->assertDontSeeText(['bar & baz', 'foo & bar']);
    }

    public function testAssertDontSeeTextEscapedCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'laravel &amp; php &amp; friends',
        ]);

        $response->assertDontSeeText('laravel & php');
        $response->assertDontSeeText(['php & friends', 'laravel & php']);
    }

    public function testAssertOk()
    {
        $statusCode = 500;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertOk();
    }

    public function testAssertCreated()
    {
        $statusCode = 500;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertCreated();
    }

    public function testAssertNotFound()
    {
        $statusCode = 500;

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertNotFound();
    }

    public function testAssertMethodNotAllowed()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED)
        );

        $response->assertMethodNotAllowed();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [405] but received 200.\nFailed asserting that 200 is identical to 405.");

        $response->assertMethodNotAllowed();
    }

    public function testAssertNotAcceptable()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_NOT_ACCEPTABLE)
        );

        $response->assertNotAcceptable();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [406] but received 200.\nFailed asserting that 200 is identical to 406.");

        $response->assertNotAcceptable();
        $this->fail();
    }

    public function testAssertForbidden()
    {
        $statusCode = 500;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertForbidden();
    }

    public function testAssertUnauthorized()
    {
        $statusCode = 500;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertUnauthorized();
    }

    public function testAssertBadRequest()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_BAD_REQUEST)
        );

        $response->assertBadRequest();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [400] but received 200.\nFailed asserting that 200 is identical to 400.");

        $response->assertBadRequest();
        $this->fail();
    }

    public function testAssertRequestTimeout()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_REQUEST_TIMEOUT)
        );

        $response->assertRequestTimeout();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [408] but received 200.\nFailed asserting that 200 is identical to 408.");

        $response->assertRequestTimeout();
        $this->fail();
    }

    public function testAssertPaymentRequired()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_PAYMENT_REQUIRED)
        );

        $response->assertPaymentRequired();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [402] but received 200.\nFailed asserting that 200 is identical to 402.");

        $response->assertPaymentRequired();
        $this->fail();
    }

    public function testAssertMovedPermanently()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_MOVED_PERMANENTLY)
        );

        $response->assertMovedPermanently();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [301] but received 200.\nFailed asserting that 200 is identical to 301.");

        $response->assertMovedPermanently();
        $this->fail();
    }

    public function testAssertFound()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_FOUND)
        );

        $response->assertFound();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [302] but received 200.\nFailed asserting that 200 is identical to 302.");

        $response->assertFound();
        $this->fail();
    }

    public function testAssertNotModified()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_NOT_MODIFIED)
        );

        $response->assertNotModified();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [304] but received 200.\nFailed asserting that 200 is identical to 304.");

        $response->assertNotModified();
        $this->fail();
    }

    public function testAssertTemporaryRedirect()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_TEMPORARY_REDIRECT)
        );

        $response->assertTemporaryRedirect();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [307] but received 200.\nFailed asserting that 200 is identical to 307.");

        $response->assertTemporaryRedirect();
        $this->fail();
    }

    public function testAssertPermanentRedirect()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_PERMANENTLY_REDIRECT)
        );

        $response->assertPermanentRedirect();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [308] but received 200.\nFailed asserting that 200 is identical to 308.");

        $response->assertPermanentRedirect();
        $this->fail();
    }

    public function testAssertConflict()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_CONFLICT)
        );

        $response->assertConflict();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [409] but received 200.\nFailed asserting that 200 is identical to 409.");

        $response->assertConflict();
        $this->fail();
    }

    public function testAssertGone()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_GONE)
        );

        $response->assertGone();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [410] but received 200.\nFailed asserting that 200 is identical to 410.");

        $response->assertGone();
    }

    public function testAssertTooManyRequests()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_TOO_MANY_REQUESTS)
        );

        $response->assertTooManyRequests();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [429] but received 200.\nFailed asserting that 200 is identical to 429.");

        $response->assertTooManyRequests();
        $this->fail();
    }

    public function testAssertAccepted()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_ACCEPTED)
        );

        $response->assertAccepted();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [202] but received 200.\nFailed asserting that 200 is identical to 202.");

        $response->assertAccepted();
        $this->fail();
    }

    public function testAssertUnprocessable()
    {
        $statusCode = 500;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertUnprocessable();
    }

    public function testAssertServerError()
    {
        $statusCode = 500;

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertServerError();
    }

    public function testAssertInternalServerError()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        $response->assertInternalServerError();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [500] but received 200.\nFailed asserting that 200 is identical to 500.");

        $response->assertInternalServerError();
    }

    public function testAssertServiceUnavailable()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE)
        );

        $response->assertServiceUnavailable();

        $response = TestResponse::fromBaseResponse(
            (new Response)->setStatusCode(Response::HTTP_OK)
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Expected response status code [503] but received 200.\nFailed asserting that 200 is identical to 503.");

        $response->assertServiceUnavailable();
    }

    public function testAssertNoContentAsserts204StatusCodeByDefault()
    {
        $statusCode = 500;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertNoContent();
    }

    public function testAssertNoContentAssertsExpectedStatusCode()
    {
        $statusCode = 500;
        $expectedStatusCode = 418;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertNoContent($expectedStatusCode);
    }

    public function testAssertNoContentAssertsEmptyContent()
    {
        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Response content is not empty');

        $baseResponse = tap(new Response, function ($response) {
            $response->setStatusCode(204);
            $response->setContent('non-empty-response-content');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertNoContent();
    }

    public function testAssertStatus()
    {
        $statusCode = 500;
        $expectedStatusCode = 401;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Expected response status code');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertStatus($expectedStatusCode);
    }

    public function testAssertHeader()
    {
        $this->expectException(AssertionFailedError::class);

        $baseResponse = tap(new Response, function ($response) {
            $response->header('Location', '/foo');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertHeader('Location', '/bar');
    }

    public function testAssertHeaderMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Unexpected header [Location] is present on response.');

        $baseResponse = tap(new Response, function ($response) {
            $response->header('Location', '/foo');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertHeaderMissing('Location');
    }

    public function testAssertPrecognitionSuccessfulWithMissingHeader()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Header [Precognition-Success] not present on response.');

        $baseResponse = new Response('', 204);

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertSuccessfulPrecognition();
    }

    public function testAssertPrecognitionSuccessfulWithIncorrectValue()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The Precognition-Success header was found, but the value is not `true`.');

        $baseResponse = tap(new Response('', 204), function ($response) {
            $response->header('Precognition-Success', '');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertSuccessfulPrecognition();
    }

    public function testAssertJsonWithArray()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $resource = new JsonSerializableSingleResourceStub;

        $response->assertJson($resource->jsonSerialize());
    }

    public function testAssertJsonWithNull()
    {
        $response = TestResponse::fromBaseResponse(new Response(null));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Invalid JSON was returned from the route.');

        $resource = new JsonSerializableSingleResourceStub;

        $response->assertJson($resource->jsonSerialize());
    }

    public function testAssertJsonWithFluent()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJson(function (AssertableJson $json) {
            $json->where('0.foo', 'foo 0');
        });
    }

    public function testAssertJsonWithFluentFailsWhenNotInteractingWithAllProps()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unexpected properties were found on the root level.');

        $response->assertJson(function (AssertableJson $json) {
            $json->where('foo', 'bar');
        });
    }

    public function testAssertJsonWithFluentSkipsInteractionWhenTopLevelKeysNonAssociative()
    {
        $response = TestResponse::fromBaseResponse(new Response([
            ['foo' => 'bar'],
            ['foo' => 'baz'],
        ]));

        $response->assertJson(function (AssertableJson $json) {
            //
        });
    }

    public function testAssertJsonWithFluentHasAnyThrows()
    {
        $response = TestResponse::fromBaseResponse(new Response([]));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('None of properties [data, errors, meta] exist.');

        $response->assertJson(function (AssertableJson $json) {
            $json->hasAny('data', 'errors', 'meta');
        });
    }

    public function testAssertJsonWithFluentHasAnyPasses()
    {
        $response = TestResponse::fromBaseResponse(new Response([
            'data' => [],
        ]));

        $response->assertJson(function (AssertableJson $json) {
            $json->hasAny('data', 'errors', 'meta');
        });
    }

    public function testAssertSimilarJsonWithMixed()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $resource = new JsonSerializableMixedResourcesStub;

        $expected = $resource->jsonSerialize();

        $response->assertSimilarJson($expected);

        $expected['bars'][0] = ['bar' => 'foo 2', 'foo' => 'bar 2'];
        $expected['bars'][2] = ['bar' => 'foo 0', 'foo' => 'bar 0'];

        $response->assertSimilarJson($expected);
    }

    public function testAssertExactJsonWithMixedWhenDataIsExactlySame()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $resource = new JsonSerializableMixedResourcesStub;

        $expected = $resource->jsonSerialize();

        $response->assertExactJson($expected);
    }

    public function testAssertExactJsonWithMixedWhenDataIsSimilar()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that two strings are equal.');

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $resource = new JsonSerializableMixedResourcesStub;

        $expected = $resource->jsonSerialize();
        $expected['bars'][0] = ['bar' => 'foo 2', 'foo' => 'bar 2'];
        $expected['bars'][2] = ['bar' => 'foo 0', 'foo' => 'bar 0'];

        $response->assertExactJson($expected);
    }

    public function testAssertJsonPath()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJsonPath('0.foo', 'foo 0');

        $response->assertJsonPath('0.foo', 'foo 0');
        $response->assertJsonPath('0.bar', 'bar 0');
        $response->assertJsonPath('0.foobar', 'foobar 0');

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $response->assertJsonPath('foo', 'bar');

        $response->assertJsonPath('foobar.foobar_foo', 'foo');
        $response->assertJsonPath('foobar.foobar_bar', 'bar');

        $response->assertJsonPath('foobar.foobar_foo', 'foo')->assertJsonPath('foobar.foobar_bar', 'bar');

        $response->assertJsonPath('bars', [
            ['bar' => 'foo 0', 'foo' => 'bar 0'],
            ['bar' => 'foo 1', 'foo' => 'bar 1'],
            ['bar' => 'foo 2', 'foo' => 'bar 2'],
        ]);
        $response->assertJsonPath('bars.0', ['bar' => 'foo 0', 'foo' => 'bar 0']);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonPath('0.id', 10);
        $response->assertJsonPath('1.id', 20);
        $response->assertJsonPath('2.id', 30);
    }

    public function testAssertJsonPathCanFail()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that 10 is identical to \'10\'.');

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonPath('0.id', '10');
    }

    public function testAssertJsonPathWithClosure()
    {
        $response = TestResponse::fromBaseResponse(new Response([
            'data' => ['foo' => 'bar'],
        ]));

        $response->assertJsonPath('data.foo', fn ($value) => $value === 'bar');
    }

    public function testAssertJsonPathWithClosureCanFail()
    {
        $response = TestResponse::fromBaseResponse(new Response([
            'data' => ['foo' => 'bar'],
        ]));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that false is true.');

        $response->assertJsonPath('data.foo', fn ($value) => $value === null);
    }

    public function testAssertJsonPathCanonicalizing()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJsonPathCanonicalizing('*.foo', ['foo 0', 'foo 1', 'foo 2', 'foo 3']);
        $response->assertJsonPathCanonicalizing('*.foo', ['foo 1', 'foo 0', 'foo 3', 'foo 2']);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonPathCanonicalizing('*.id', [10, 20, 30]);
        $response->assertJsonPathCanonicalizing('*.id', [30, 10, 20]);
    }

    public function testAssertJsonPathCanonicalizingCanFail()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that two arrays are equal.');

        $response->assertJsonPathCanonicalizing('*.foo', ['foo 0', 'foo 2', 'foo 3']);
    }

    public function testAssertJsonFragment()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJsonFragment(['foo' => 'foo 0']);

        $response->assertJsonFragment(['foo' => 'foo 0', 'bar' => 'bar 0', 'foobar' => 'foobar 0']);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $response->assertJsonFragment(['foo' => 'bar']);

        $response->assertJsonFragment(['foobar_foo' => 'foo']);

        $response->assertJsonFragment(['foobar' => ['foobar_foo' => 'foo', 'foobar_bar' => 'bar']]);

        $response->assertJsonFragment(['foo' => 'bar 0', 'bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']]);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonFragment(['id' => 10]);
    }

    public function testAssertJsonFragmentCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonFragment(['id' => 1]);
    }

    public function testAssertJsonFragmentUnicodeCanFail()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('/Привет|Мир/');

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithUnicodeStub));

        $response->assertJsonFragment(['id' => 1]);
    }

    public function testAssertJsonStructure()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        // Without structure
        $response->assertJsonStructure();

        // At root
        $response->assertJsonStructure(['foo']);

        // Nested
        $response->assertJsonStructure(['foobar' => ['foobar_foo', 'foobar_bar']]);

        // Wildcard (repeating structure)
        $response->assertJsonStructure(['bars' => ['*' => ['bar', 'foo']]]);

        // Wildcard (numeric keys)
        $response->assertJsonStructure(['numeric_keys' => ['*' => ['bar', 'foo']]]);

        // Nested after wildcard
        $response->assertJsonStructure(['baz' => ['*' => ['foo', 'bar' => ['foo', 'bar']]]]);

        // Wildcard (repeating structure) at root
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJsonStructure(['*' => ['foo', 'bar', 'foobar']]);
    }

    public function testAssertJsonCount()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        // With falsey key
        $response->assertJsonCount(1, '0');

        // With simple key
        $response->assertJsonCount(3, 'bars');

        // With nested key
        $response->assertJsonCount(1, 'barfoo.0.bar');
        $response->assertJsonCount(3, 'barfoo.2.bar');

        // Without structure
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));
        $response->assertJsonCount(4);
    }

    public function testAssertJsonMissing()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissing(['id' => 20]);
    }

    public function testAssertJsonMissingExact()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissingExact(['id' => 2]);

        // This is missing because bar has changed to baz
        $response->assertJsonMissingExact(['id' => 20, 'foo' => 'baz']);
    }

    public function testAssertJsonMissingExactCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissingExact(['id' => 20]);
    }

    public function testAssertJsonMissingExactCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissingExact(['id' => 20, 'foo' => 'bar']);
    }

    public function testAssertJsonMissingPath()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        // With simple key
        $response->assertJsonMissingPath('missing');

        // With nested key
        $response->assertJsonMissingPath('foobar.missing');
        $response->assertJsonMissingPath('numeric_keys.0');
    }

    public function testAssertJsonMissingPathCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $response->assertJsonMissingPath('foo');
    }

    public function testAssertJsonMissingPathCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $response->assertJsonMissingPath('foobar.foobar_foo');
    }

    public function testAssertJsonMissingPathCanFail3()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $response->assertJsonMissingPath('numeric_keys.3');
    }

    public function testAssertJsonValidationErrors()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['foo' => 'oops'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('foo');
    }

    public function testAssertJsonValidationErrorsUsingAssertInvalid()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['foo' => 'oops'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response('', 200, ['Content-Type' => 'application/json']))->setContent(json_encode($data))
        );

        $testResponse->assertInvalid('foo');
    }

    public function testAssertSessionValidationErrorsUsingAssertInvalid()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('errors', $errorBag = new ViewErrorBag);

        $errorBag->put('default', new MessageBag([
            'first_name' => [
                'Your first name is required',
                'Your first name must be at least 1 character',
            ],
        ]));

        $testResponse = TestResponse::fromBaseResponse(new Response);

        $testResponse->assertValid('last_name');
        $testResponse->assertValid(['last_name']);

        $testResponse->assertInvalid();
        $testResponse->assertInvalid('first_name');
        $testResponse->assertInvalid(['first_name']);
        $testResponse->assertInvalid(['first_name' => 'required']);
        $testResponse->assertInvalid(['first_name' => 'character']);
    }

    public function testAssertSessionValidationErrorsUsingAssertValid()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('errors', $errorBag = new ViewErrorBag);

        $errorBag->put('default', new MessageBag([
        ]));

        $testResponse = TestResponse::fromBaseResponse(new Response);

        $testResponse->assertValid();
    }

    public function testAssertingKeyIsInvalidErrorMessage()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));
        $store->put('errors', $errorBag = new ViewErrorBag);
        $testResponse = TestResponse::fromBaseResponse(new Response);

        try {
            $testResponse->assertInvalid(['input_name']);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith("Failed to find a validation error in session for key: 'input_name'", $e->getMessage());
        }

        try {
            $testResponse->assertInvalid(['input_name' => 'Expected error message.']);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith("Failed to find a validation error in session for key: 'input_name'", $e->getMessage());
        }
    }

    public function testInvalidWithListOfErrors()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('errors', $errorBag = new ViewErrorBag);

        $errorBag->put('default', new MessageBag([
            'first_name' => [
                'Your first name is required',
                'Your first name must be at least 1 character',
            ],
        ]));

        $testResponse = TestResponse::fromBaseResponse(new Response);

        $testResponse->assertInvalid(['first_name' => 'Your first name is required']);
        $testResponse->assertInvalid(['first_name' => 'Your first name must be at least 1 character']);
        $testResponse->assertInvalid(['first_name' => ['Your first name is required', 'Your first name must be at least 1 character']]);

        try {
            $testResponse->assertInvalid(['first_name' => ['Your first name is required', 'FOO']]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith("Failed to find a validation error for key and message: 'first_name' => 'FOO'", $e->getMessage());
        }
    }

    public function testAssertJsonValidationErrorsCustomErrorsName()
    {
        $data = [
            'status' => 'ok',
            'data' => ['foo' => 'oops'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('foo', 'data');
    }

    public function testAssertJsonValidationErrorsCustomNestedErrorsName()
    {
        $data = [
            'status' => 'ok',
            'data' => ['errors' => ['foo' => 'oops']],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('foo', 'data.errors');
    }

    public function testAssertJsonValidationErrorsCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = [
            'status' => 'ok',
            'errors' => ['foo' => 'oops'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('bar');
    }

    public function testAssertJsonValidationErrorsCanFailWhenThereAreNoErrors()
    {
        $this->expectException(AssertionFailedError::class);

        $data = ['status' => 'ok'];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('bar');
    }

    public function testAssertJsonValidationErrorsFailsWhenGivenAnEmptyArray()
    {
        $this->expectException(AssertionFailedError::class);

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode(['errors' => ['foo' => 'oops']]))
        );

        $testResponse->assertJsonValidationErrors([]);
    }

    public function testAssertJsonValidationErrorsWithArray()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['foo' => 'one', 'bar' => 'two'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['foo', 'bar']);
    }

    public function testAssertJsonValidationErrorMessages()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'foo'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['key' => 'foo']);
    }

    public function testAssertJsonValidationErrorContainsMessages()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'foo bar'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['key' => 'foo']);
    }

    public function testAssertJsonValidationErrorMessagesCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = [
            'status' => 'ok',
            'errors' => ['key' => 'foo'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['key' => 'bar']);
    }

    public function testAssertJsonValidationErrorMessageKeyCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = [
            'status' => 'ok',
            'errors' => ['foo' => 'value'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['bar' => 'value']);
    }

    public function testAssertJsonValidationErrorMessagesMultipleMessages()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['one' => 'foo', 'two' => 'bar'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['one' => 'foo', 'two' => 'bar']);
    }

    public function testAssertJsonValidationErrorMessagesMultipleMessagesCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = [
            'status' => 'ok',
            'errors' => ['one' => 'foo', 'two' => 'bar'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['one' => 'foo', 'three' => 'baz']);
    }

    public function testAssertJsonValidationErrorMessagesMixed()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['one' => 'foo', 'two' => 'bar'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['one' => 'foo', 'two']);
    }

    public function testAssertJsonValidationErrorMessagesMixedCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = [
            'status' => 'ok',
            'errors' => ['one' => 'foo', 'two' => 'bar'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['one' => 'taylor', 'otwell']);
    }

    public function testAssertJsonValidationErrorMessagesMultipleErrors()
    {
        $data = [
            'status' => 'ok',
            'errors' => [
                'one' => [
                    'First error message.',
                    'Second error message.',
                ],
            ],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['one' => ['First error message.', 'Second error message.']]);
    }

    public function testAssertJsonValidationErrorMessagesMultipleErrorsCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = [
            'status' => 'ok',
            'errors' => [
                'one' => [
                    'First error message.',
                ],
            ],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors(['one' => ['First error message.', 'Second error message.']]);
    }

    public function testAssertJsonMissingValidationErrors()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['errors' => [
                'foo' => [],
                'bar' => ['one', 'two'],
            ]]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertJsonMissingValidationErrors('baz');

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['foo' => 'bar']));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertJsonMissingValidationErrors('foo');
    }

    public function testAssertJsonMissingValidationErrorsCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['errors' => [
                'foo' => [],
                'bar' => ['one', 'two'],
            ]]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertJsonMissingValidationErrors('foo');
    }

    public function testAssertJsonMissingValidationErrorsCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['errors' => [
                'foo' => [],
                'bar' => ['one', 'two'],
            ]]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertJsonMissingValidationErrors('bar');
    }

    public function testAssertJsonMissingValidationErrorsCanFail3()
    {
        $this->expectException(AssertionFailedError::class);

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(
                json_encode([
                    'data' => [
                        'errors' => [
                            'foo' => ['one'],
                        ],
                    ],
                ]),
            );
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertJsonMissingValidationErrors('foo', 'data.errors');
    }

    public function testAssertJsonMissingValidationErrorsWithoutArgument()
    {
        $data = ['status' => 'ok'];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors();
    }

    public function testAssertJsonMissingValidationErrorsWithoutArgumentWhenErrorsIsEmpty()
    {
        $data = ['status' => 'ok', 'errors' => []];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors();
    }

    public function testAssertJsonMissingValidationErrorsWithoutArgumentCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = ['errors' => ['foo' => []]];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors();
    }

    public function testAssertJsonMissingValidationErrorsOnInvalidJson()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Invalid JSON was returned from the route.');

        $invalidJsonResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent('~invalid json')
        );

        $invalidJsonResponse->assertJsonMissingValidationErrors();
    }

    public function testAssertJsonMissingValidationErrorsCustomErrorsName()
    {
        $data = [
            'status' => 'ok',
            'data' => ['foo' => 'oops'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors('bar', 'data');
    }

    public function testAssertJsonMissingValidationErrorsNestedCustomErrorsName1()
    {
        $data = [
            'status' => 'ok',
            'data' => [
                'errors' => ['foo' => 'oops'],
            ],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors('bar', 'data.errors');
    }

    public function testAssertJsonMissingValidationErrorsNestedCustomErrorsName2()
    {
        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode([]))
        );

        $testResponse->assertJsonMissingValidationErrors('bar', 'data.errors');
    }

    public function testAssertJsonIsArray()
    {
        $responseArray = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));
        $responseArray->assertJsonIsArray();
    }

    public function testAssertJsonIsNotArray()
    {
        $this->expectException(ExpectationFailedException::class);

        $responseObject = TestResponse::fromBaseResponse(new Response([
            'foo' => 'bar',
        ]));
        $responseObject->assertJsonIsArray();
    }

    public function testAssertJsonIsObject()
    {
        $responseObject = TestResponse::fromBaseResponse(new Response([
            'foo' => 'bar',
        ]));
        $responseObject->assertJsonIsObject();
    }

    public function testAssertJsonIsNotObject()
    {
        $this->expectException(ExpectationFailedException::class);

        $responseArray = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));
        $responseArray->assertJsonIsObject();
    }

    public function testAssertDownloadOffered()
    {
        $files = new Filesystem;
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');
        $testResponse = TestResponse::fromBaseResponse(new Response(
            $files->get($tempDir.'/file.txt'), 200, [
                'Content-Disposition' => 'attachment; filename=file.txt',
            ]
        ));
        $testResponse->assertDownload();
        $files->deleteDirectory($tempDir);
    }

    public function testAssertDownloadOfferedWithAFileName()
    {
        $files = new Filesystem;
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');
        $testResponse = TestResponse::fromBaseResponse(new Response(
            $files->get($tempDir.'/file.txt'), 200, [
                'Content-Disposition' => 'attachment; filename = file.txt',
            ]
        ));
        $testResponse->assertDownload('file.txt');
        $files->deleteDirectory($tempDir);
    }

    public function testAssertDownloadOfferedWorksWithBinaryFileResponse()
    {
        $files = new Filesystem;
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');
        $testResponse = TestResponse::fromBaseResponse(new BinaryFileResponse(
            $tempDir.'/file.txt', 200, [], true, 'attachment'
        ));
        $testResponse->assertDownload('file.txt');
        $files->deleteDirectory($tempDir);
    }

    public function testAssertDownloadOfferedFailsWithInlineContentDisposition()
    {
        $this->expectException(AssertionFailedError::class);
        $files = new Filesystem;
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');
        $testResponse = TestResponse::fromBaseResponse(new BinaryFileResponse(
            $tempDir.'/file.txt', 200, [], true, 'inline'
        ));
        $testResponse->assertDownload();
        $files->deleteDirectory($tempDir);
    }

    public function testAssertDownloadOfferedWithAFileNameWithSpacesInIt()
    {
        $files = new Filesystem;
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');
        $testResponse = TestResponse::fromBaseResponse(new Response(
            $files->get($tempDir.'/file.txt'), 200, [
                'Content-Disposition' => 'attachment; filename = "test file.txt"',
            ]
        ));
        $testResponse->assertDownload('test file.txt');
        $files->deleteDirectory($tempDir);
    }

    public function testMacroable()
    {
        TestResponse::macro('foo', function () {
            return 'bar';
        });

        $response = TestResponse::fromBaseResponse(new Response);

        $this->assertSame(
            'bar', $response->foo()
        );
    }

    public function testCanBeCreatedFromBinaryFileResponses()
    {
        $files = new Filesystem;
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');

        $response = TestResponse::fromBaseResponse(new BinaryFileResponse($tempDir.'/file.txt'));

        $this->assertEquals($tempDir.'/file.txt', $response->getFile()->getPathname());

        $files->deleteDirectory($tempDir);
    }

    public function testJsonHelper()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $this->assertSame('foo', $response->json('foobar.foobar_foo'));
        $this->assertEquals(
            json_decode($response->getContent(), true),
            $response->json()
        );
    }

    public function testResponseCanBeReturnedAsCollection()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $this->assertInstanceOf(Collection::class, $response->collect());
        $this->assertEquals(collect([
            'foo' => 'bar',
            'foobar' => [
                'foobar_foo' => 'foo',
                'foobar_bar' => 'bar',
            ],
            '0' => ['foo'],
            'bars' => [
                ['bar' => 'foo 0', 'foo' => 'bar 0'],
                ['bar' => 'foo 1', 'foo' => 'bar 1'],
                ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
            'baz' => [
                ['foo' => 'bar 0', 'bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']],
                ['foo' => 'bar 1', 'bar' => ['foo' => 'bar 1', 'bar' => 'foo 1']],
            ],
            'barfoo' => [
                ['bar' => ['bar' => 'foo 0']],
                ['bar' => ['bar' => 'foo 0', 'foo' => 'foo 0']],
                ['bar' => ['foo' => 'bar 0', 'bar' => 'foo 0', 'rab' => 'rab 0']],
            ],
            'numeric_keys' => [
                2 => ['bar' => 'foo 0', 'foo' => 'bar 0'],
                3 => ['bar' => 'foo 1', 'foo' => 'bar 1'],
                4 => ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
        ]), $response->collect());
        $this->assertEquals(collect(['foobar_foo' => 'foo', 'foobar_bar' => 'bar']), $response->collect('foobar'));
        $this->assertEquals(collect(['bar']), $response->collect('foobar.foobar_bar'));
        $this->assertEquals(collect(), $response->collect('missing_key'));
    }

    public function testItCanBeTapped()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->setContent('')->setStatusCode(418)
        );

        $response->tap(function ($response) {
            $this->assertInstanceOf(TestResponse::class, $response);
        })->assertStatus(418);
    }

    public function testAssertPlainCookie()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->withCookie(new Cookie('cookie-name', 'cookie-value'))
        );

        $response->assertPlainCookie('cookie-name', 'cookie-value');
    }

    public function testAssertCookie()
    {
        $container = Container::getInstance();
        $encrypter = new Encrypter(str_repeat('a', 16));
        $container->singleton('encrypter', function () use ($encrypter) {
            return $encrypter;
        });

        $cookieName = 'cookie-name';
        $cookieValue = 'cookie-value';
        $encryptedValue = $encrypter->encrypt(CookieValuePrefix::create($cookieName, $encrypter->getKey()).$cookieValue, false);

        $response = TestResponse::fromBaseResponse(
            (new Response)->withCookie(new Cookie($cookieName, $encryptedValue))
        );

        $response->assertCookie($cookieName, $cookieValue);
    }

    public function testAssertCookieExpired()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->withCookie(new Cookie('cookie-name', 'cookie-value', time() - 5000))
        );

        $response->assertCookieExpired('cookie-name');
    }

    public function testAssertSessionCookieExpiredDoesNotTriggerOnSessionCookies()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->withCookie(new Cookie('cookie-name', 'cookie-value', 0))
        );

        $this->expectException(ExpectationFailedException::class);

        $response->assertCookieExpired('cookie-name');
    }

    public function testAssertCookieNotExpired()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->withCookie(new Cookie('cookie-name', 'cookie-value', time() + 5000))
        );

        $response->assertCookieNotExpired('cookie-name');
    }

    public function testAssertSessionCookieNotExpired()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response)->withCookie(new Cookie('cookie-name', 'cookie-value', 0))
        );

        $response->assertCookieNotExpired('cookie-name');
    }

    public function testAssertCookieMissing()
    {
        $response = TestResponse::fromBaseResponse(new Response);

        $response->assertCookieMissing('cookie-name');
    }

    public function testAssertLocation()
    {
        app()->instance('url', $url = new UrlGenerator(new RouteCollection, new Request));

        $response = TestResponse::fromBaseResponse(
            new RedirectResponse($url->to('https://foo.com'))
        );

        $response->assertLocation('https://foo.com');

        $this->expectException(ExpectationFailedException::class);
        $response->assertLocation('https://foo.net');
    }

    public function testAssertRedirectContains()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response('', 302))->withHeaders(['Location' => 'https://url.com'])
        );

        $response->assertRedirectContains('url.com');

        $this->expectException(ExpectationFailedException::class);

        $response->assertRedirectContains('url.net');
    }

    public function testAssertRedirect()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response('', 302))->withHeaders(['Location' => 'https://url.com'])
        );

        $response->assertRedirect();
    }

    public function testGetDecryptedCookie()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response())->withCookie(new Cookie('cookie-name', 'cookie-value'))
        );

        $cookie = $response->getCookie('cookie-name', false);

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame('cookie-name', $cookie->getName());
        $this->assertSame('cookie-value', $cookie->getValue());
    }

    public function testAssertSessionHasErrors()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('errors', $errorBag = new ViewErrorBag);

        $errorBag->put('default', new MessageBag([
            'foo' => [
                'foo is required',
            ],
        ]));

        $response = TestResponse::fromBaseResponse(new Response());

        $response->assertSessionHasErrors(['foo']);
    }

    public function testAssertJsonSerializedSessionHasErrors()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1), null, 'json'));

        $store->put('errors', $errorBag = new ViewErrorBag);

        $errorBag->put('default', new MessageBag([
            'foo' => [
                'foo is required',
            ],
        ]));

        $store->save(); // Required to serialize error bag to JSON

        $response = TestResponse::fromBaseResponse(new Response());

        $response->assertSessionHasErrors(['foo']);
    }

    public function testAssertSessionDoesntHaveErrors()
    {
        $this->expectException(AssertionFailedError::class);

        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('errors', $errorBag = new ViewErrorBag);

        $errorBag->put('default', new MessageBag([
            'foo' => [
                'foo is required',
            ],
        ]));

        $response = TestResponse::fromBaseResponse(new Response());

        $response->assertSessionDoesntHaveErrors(['foo']);
    }

    public function testAssertSessionHasNoErrors()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('errors', $errorBag = new ViewErrorBag);

        $errorBag->put('default', new MessageBag([
            'foo' => [
                'foo is required',
            ],
        ]));

        $errorBag->put('some-other-bag', new MessageBag([
            'bar' => [
                'bar is required',
            ],
        ]));

        $response = TestResponse::fromBaseResponse(new Response());

        try {
            $response->assertSessionHasNoErrors();
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('foo is required', $e->getMessage());
            $this->assertStringContainsString('bar is required', $e->getMessage());
        }
    }

    public function testAssertSessionHas()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('foo', 'value');
        $store->put('bar', 'value');

        $response = TestResponse::fromBaseResponse(new Response());

        $response->assertSessionHas('foo');
        $response->assertSessionHas('bar');
        $response->assertSessionHas(['foo', 'bar']);
    }

    public function testAssertSessionMissing()
    {
        $this->expectException(AssertionFailedError::class);

        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('foo', 'value');

        $response = TestResponse::fromBaseResponse(new Response());
        $response->assertSessionMissing('foo');
    }

    public function testAssertSessionHasInput()
    {
        app()->instance('session.store', $store = new Store('test-session', new ArraySessionHandler(1)));

        $store->put('_old_input', [
            'foo' => 'value',
            'bar' => 'value',
        ]);

        $response = TestResponse::fromBaseResponse(new Response());

        $response->assertSessionHasInput('foo');
        $response->assertSessionHasInput('foo', 'value');
        $response->assertSessionHasInput('bar');
        $response->assertSessionHasInput('bar', 'value');
        $response->assertSessionHasInput(['foo', 'bar']);
        $response->assertSessionHasInput('foo', function ($value) {
            return $value === 'value';
        });
    }

    public function testGetEncryptedCookie()
    {
        $container = Container::getInstance();
        $encrypter = new Encrypter(str_repeat('a', 16));
        $container->singleton('encrypter', function () use ($encrypter) {
            return $encrypter;
        });

        $cookieName = 'cookie-name';
        $cookieValue = 'cookie-value';
        $encryptedValue = $encrypter->encrypt(
            CookieValuePrefix::create($cookieName, $encrypter->getKey()).$cookieValue, false
        );

        $response = TestResponse::fromBaseResponse(
            (new Response())->withCookie(new Cookie($cookieName, $encryptedValue))
        );

        $cookie = $response->getCookie($cookieName);

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals($cookieName, $cookie->getName());
        $this->assertEquals($cookieValue, $cookie->getValue());
    }

    private function makeMockResponse($content)
    {
        $baseResponse = tap(new Response, function ($response) use ($content) {
            $response->setContent(m::mock(View::class, $content));
        });

        return TestResponse::fromBaseResponse($baseResponse);
    }
}

class JsonSerializableMixedResourcesStub implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [
            'foo' => 'bar',
            'foobar' => [
                'foobar_foo' => 'foo',
                'foobar_bar' => 'bar',
            ],
            '0' => ['foo'],
            'bars' => [
                ['bar' => 'foo 0', 'foo' => 'bar 0'],
                ['bar' => 'foo 1', 'foo' => 'bar 1'],
                ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
            'baz' => [
                ['foo' => 'bar 0', 'bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']],
                ['foo' => 'bar 1', 'bar' => ['foo' => 'bar 1', 'bar' => 'foo 1']],
            ],
            'barfoo' => [
                ['bar' => ['bar' => 'foo 0']],
                ['bar' => ['bar' => 'foo 0', 'foo' => 'foo 0']],
                ['bar' => ['foo' => 'bar 0', 'bar' => 'foo 0', 'rab' => 'rab 0']],
            ],
            'numeric_keys' => [
                2 => ['bar' => 'foo 0', 'foo' => 'bar 0'],
                3 => ['bar' => 'foo 1', 'foo' => 'bar 1'],
                4 => ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
        ];
    }
}

class JsonSerializableSingleResourceStub implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [
            ['foo' => 'foo 0', 'bar' => 'bar 0', 'foobar' => 'foobar 0'],
            ['foo' => 'foo 1', 'bar' => 'bar 1', 'foobar' => 'foobar 1'],
            ['foo' => 'foo 2', 'bar' => 'bar 2', 'foobar' => 'foobar 2'],
            ['foo' => 'foo 3', 'bar' => 'bar 3', 'foobar' => 'foobar 3'],
        ];
    }
}

class JsonSerializableSingleResourceWithIntegersStub implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [
            ['id' => 10, 'foo' => 'bar'],
            ['id' => 20, 'foo' => 'bar'],
            ['id' => 30, 'foo' => 'bar'],
        ];
    }
}

class JsonSerializableSingleResourceWithUnicodeStub implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return [
            ['id' => 10, 'foo' => 'bar'],
            ['id' => 20, 'foo' => 'Привет'],
            ['id' => 30, 'foo' => 'Мир'],
        ];
    }
}

class TestModel extends Model
{
    protected $guarded = [];
}

class AnotherTestModel extends Model
{
    protected $guarded = [];
}
