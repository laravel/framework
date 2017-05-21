<?php

namespace Illuminate\Tests\Foundation;

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

    public function testAssertHeader()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->header('Location', '/foo');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        try {
            $response->assertHeader('Location', '/bar');
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $this->assertEquals('/bar', $e->getComparisonFailure()->getExpected());
            $this->assertEquals('/foo', $e->getComparisonFailure()->getActual());
        }
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
        $files = new Filesystem();
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');

        $response = TestResponse::fromBaseResponse(new BinaryFileResponse($tempDir.'/file.txt'));

        $this->assertEquals($tempDir.'/file.txt', $response->getFile()->getPathname());

        $files->deleteDirectory($tempDir);
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
