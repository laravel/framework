<?php

namespace Illuminate\Tests\Foundation;

use Exception;
use JsonSerializable;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FoundationTestResponseTest extends TestCase
{
    public function testAssertViewIs()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(\Mockery::mock(View::class, [
                'render' => 'hello world',
                'getData' => ['foo' => 'bar'],
                'getName' => 'dir.my-view',
            ]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertViewIs('dir.my-view');
    }

    public function testAssertViewHas()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(\Mockery::mock(View::class, [
                'render' => 'hello world',
                'getData' => ['foo' => 'bar'],
            ]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertViewHas('foo');
    }

    public function testAssertSeeInOrder()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(\Mockery::mock(View::class, [
                'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
            ]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertSeeInOrder(['foo', 'bar', 'baz']);
        $response->assertSeeInOrder(['foo', 'bar', 'baz', 'foo']);

        try {
            $response->assertSeeInOrder(['baz', 'bar', 'foo']);
            TestCase::fail('Assertion was expected to fail.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
        }

        try {
            $response->assertSeeInOrder(['foo', 'qux', 'bar', 'baz']);
            TestCase::fail('Assertion was expected to fail.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
        }
    }

    public function testAssertSeeText()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(\Mockery::mock(View::class, [
                'render' => 'foo<strong>bar</strong>',
            ]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertSeeText('foobar');
    }

    public function testAssertSeeTextInOrder()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(\Mockery::mock(View::class, [
                'render' => 'foo<strong>bar</strong> baz <strong>foo</strong>',
            ]));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertSeeTextInOrder(['foobar', 'baz']);
        $response->assertSeeTextInOrder(['foobar', 'baz', 'foo']);

        try {
            $response->assertSeeTextInOrder(['baz', 'foobar']);
            TestCase::fail('Assertion was expected to fail.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
        }

        try {
            $response->assertSeeTextInOrder(['foobar', 'qux', 'baz']);
            TestCase::fail('Assertion was expected to fail.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
        }
    }

    public function testAssertHeader()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->header('Location', '/foo');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        try {
            $response->assertHeader('Location', '/bar');
            $this->fail('No exception was thrown');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $this->assertEquals('/bar', $e->getComparisonFailure()->getExpected());
            $this->assertEquals('/foo', $e->getComparisonFailure()->getActual());
        }
    }

    /**
     * @expectedException \PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage Unexpected header [Location] is present on response.
     */
    public function testAssertHeaderMissing()
    {
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

    public function testAssertJsonWithMixed()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $resource = new JsonSerializableMixedResourcesStub;

        $response->assertJson($resource->jsonSerialize());
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

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub()));

        $response->assertJsonFragment(['id' => 10]);

        try {
            $response->assertJsonFragment(['id' => 1]);
            $this->fail('Asserting id => 1, existing in JsonSerializableSingleResourceWithIntegersStub should fail');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
        }
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

        // Nested after wildcard
        $response->assertJsonStructure(['baz' => ['*' => ['foo', 'bar' => ['foo', 'bar']]]]);

        // Wildcard (repeating structure) at root
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJsonStructure(['*' => ['foo', 'bar', 'foobar']]);
    }

    public function testAssertJsonCount()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

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
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissing(['id' => 2]);
    }

    public function testAssertJsonMissingValidationErrors()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['errors' => [
                    'foo' => [],
                    'bar' => ['one', 'two'],
                ]]
            ));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        try {
            $response->assertJsonMissingValidationErrors('foo');
            $this->fail('No exception was thrown');
        } catch (Exception $e) {
        }

        try {
            $response->assertJsonMissingValidationErrors('bar');
            $this->fail('No exception was thrown');
        } catch (Exception $e) {
        }

        $response->assertJsonMissingValidationErrors('baz');

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['foo' => 'bar']));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertJsonMissingValidationErrors('foo');
    }

    public function testMacroable()
    {
        TestResponse::macro('foo', function () {
            return 'bar';
        });

        $response = TestResponse::fromBaseResponse(new Response);

        $this->assertEquals(
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

        $this->assertEquals('foo', $response->json('foobar.foobar_foo'));
        $this->assertEquals(
            json_decode($response->getContent(), true),
            $response->json()
        );
    }
}

class JsonSerializableMixedResourcesStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'foo'    => 'bar',
            'foobar' => [
                'foobar_foo' => 'foo',
                'foobar_bar' => 'bar',
            ],
            'bars'   => [
                ['bar' => 'foo 0', 'foo' => 'bar 0'],
                ['bar' => 'foo 1', 'foo' => 'bar 1'],
                ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
            'baz'    => [
                ['foo' => 'bar 0', 'bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']],
                ['foo' => 'bar 1', 'bar' => ['foo' => 'bar 1', 'bar' => 'foo 1']],
            ],
            'barfoo' => [
                ['bar' => ['bar' => 'foo 0']],
                ['bar' => ['bar' => 'foo 0', 'bar' => 'foo 0']],
                ['bar' => ['foo' => 'bar 0', 'bar' => 'foo 0', 'rab' => 'rab 0']],
            ],
        ];
    }
}

class JsonSerializableSingleResourceStub implements JsonSerializable
{
    public function jsonSerialize()
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
    public function jsonSerialize()
    {
        return [
            ['id' => 10, 'foo' => 'bar'],
            ['id' => 20, 'foo' => 'bar'],
            ['id' => 30, 'foo' => 'bar'],
        ];
    }
}
