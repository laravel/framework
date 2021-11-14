<?php

namespace Illuminate\Tests\Testing;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
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
        $model = new class extends Model
        {
            public function is($model)
            {
                return $this == $model;
            }
        };

        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'gatherData' => ['foo' => $model],
        ]);

        $response->original->foo = $model;

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

    public function testAssertStatusShowsExceptionOnUnexpected500()
    {
        $statusCode = 500;
        $expectedStatusCode = 200;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('Test exception message');

        $baseResponse = tap(new Response, function ($response) use ($statusCode) {
            $response->setStatusCode($statusCode);
        });
        $exceptions = collect([new Exception('Test exception message')]);

        $response = TestResponse::fromBaseResponse($baseResponse)
            ->withExceptions($exceptions);
        $response->assertStatus($expectedStatusCode);
    }

    public function testAssertStatusShowsErrorsOnUnexpectedErrorRedirect()
    {
        $statusCode = 302;
        $expectedStatusCode = 200;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('The first name field is required.');

        $baseResponse = tap(new RedirectResponse('/', $statusCode), function ($response) {
            $response->setSession(new Store('test-session', new ArraySessionHandler(1)));
            $response->withErrors([
                'first_name' => 'The first name field is required.',
                'last_name' => 'The last name field is required.',
            ]);
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertStatus($expectedStatusCode);
    }

    public function testAssertStatusShowsJsonErrorsOnUnexpected422()
    {
        $statusCode = 422;
        $expectedStatusCode = 200;

        $this->expectException(AssertionFailedError::class);

        $this->expectExceptionMessage('"The first name field is required."');

        $baseResponse = new Response(
            [
                'message' => 'The given data was invalid.',
                'errors' => [
                    'first_name' => 'The first name field is required.',
                    'last_name' => 'The last name field is required.',
                ],
            ],
            $statusCode
        );

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertStatus($expectedStatusCode);
    }

    public function testAssertStatusWhenJsonIsFalse()
    {
        $baseResponse = new Response('false', 200, ['Content-Type' => 'application/json']);

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertStatus(200);
    }

    public function testAssertStatusWhenJsonIsEncoded()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->header('Content-Type', 'application/json');
            $response->header('Content-Encoding', 'gzip');
            $response->setContent('b"x£½V*.I,)-V▓R╩¤V¬\x05\x00+ü\x059"');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertStatus(200);
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

    public function testAssertRedirectContains()
    {
        $response = TestResponse::fromBaseResponse(
            (new Response('', 302))->withHeaders(['Location' => 'https://url.com'])
        );

        $response->assertRedirectContains('url.com');

        $this->expectException(ExpectationFailedException::class);

        $response->assertRedirectContains('url.net');
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
