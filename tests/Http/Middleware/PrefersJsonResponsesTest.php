<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Http\Middleware\PrefersJsonResponses;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PrefersJsonResponsesTest extends TestCase
{
    public function testItRewritesMissingAcceptHeader()
    {
        $request = Request::create('/', 'GET');
        $request->headers->remove('Accept');

        $this->runMiddleware($request);

        $this->assertSame('application/json', $request->headers->get('Accept'));
        $this->assertFalse($request->headers->has('X-Original-Accept'));
        $this->assertTrue($request->wantsJson());
        $this->assertTrue($request->expectsJson());
    }

    public function testItRewritesEmptyAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '']);

        $this->runMiddleware($request);

        $this->assertSame('application/json', $request->headers->get('Accept'));
    }

    public function testItRewritesStarSlashStarAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*']);

        $this->runMiddleware($request);

        $this->assertSame('application/json', $request->headers->get('Accept'));
        $this->assertSame('*/*', $request->headers->get('X-Original-Accept'));
        $this->assertTrue($request->wantsJson());
    }

    public function testItDoesNotRewriteBareStarAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*']);

        $this->runMiddleware($request);

        $this->assertSame('*', $request->headers->get('Accept'));
        $this->assertFalse($request->headers->has('X-Original-Accept'));
    }

    public function testItRewritesApplicationWildcardAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*']);

        $this->runMiddleware($request);

        $this->assertSame('application/json', $request->headers->get('Accept'));
    }

    public function testItRewritesBroadAcceptHeaderWithQualityParameter()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*;q=0.8']);

        $this->runMiddleware($request);

        $this->assertSame('application/json', $request->headers->get('Accept'));
    }

    public function testItRewritesMultipleBroadMarkers()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*, */*;q=0.5']);

        $this->runMiddleware($request);

        $this->assertSame('application/json', $request->headers->get('Accept'));
        $this->assertSame('application/*, */*;q=0.5', $request->headers->get('X-Original-Accept'));
    }

    public function testItInvokesNextAndReturnsItsResponse()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*']);

        $called = false;
        $expected = new Response('ok');

        $result = (new PrefersJsonResponses)->handle($request, function ($passed) use ($request, &$called, $expected) {
            $called = true;
            $this->assertSame($request, $passed);

            return $expected;
        });

        $this->assertTrue($called);
        $this->assertSame($expected, $result);
    }

    public function testItLeavesExplicitHtmlAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);

        $this->runMiddleware($request);

        $this->assertSame('text/html', $request->headers->get('Accept'));
        $this->assertFalse($request->wantsJson());
    }

    public function testItLeavesExplicitXmlAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/xml']);

        $this->runMiddleware($request);

        $this->assertSame('application/xml', $request->headers->get('Accept'));
    }

    public function testItLeavesExplicitPlainTextAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/plain']);

        $this->runMiddleware($request);

        $this->assertSame('text/plain', $request->headers->get('Accept'));
    }

    public function testItLeavesMultiValueExplicitAcceptList()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html, text/plain']);

        $this->runMiddleware($request);

        $this->assertSame('text/html, text/plain', $request->headers->get('Accept'));
        $this->assertFalse($request->wantsJson());
    }

    public function testItLeavesMixedBroadAndExplicitAcceptList()
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]);

        $this->runMiddleware($request);

        $this->assertSame(
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            $request->headers->get('Accept')
        );
        $this->assertFalse($request->headers->has('X-Original-Accept'));
    }

    public function testItLeavesWildcardFirstMixedAcceptList()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*, text/html']);

        $this->runMiddleware($request);

        $this->assertSame('*/*, text/html', $request->headers->get('Accept'));
    }

    public function testItLeavesExplicitJsonAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->runMiddleware($request);

        $this->assertSame('application/json', $request->headers->get('Accept'));
        $this->assertTrue($request->wantsJson());
    }

    public function testItDoesNotMutateBodyMethodUriQueryOrOtherHeaders()
    {
        $request = Request::create(
            '/resource?page=2',
            'POST',
            ['foo' => 'bar'],
            [],
            [],
            [
                'HTTP_ACCEPT' => '*/*',
                'HTTP_X_CUSTOM' => 'value',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            'raw-body'
        );

        $originalMethod = $request->getMethod();
        $originalPath = $request->getPathInfo();
        $originalQuery = $request->query->all();
        $originalBody = $request->getContent();
        $originalCustomHeader = $request->headers->get('X-Custom');

        $this->runMiddleware($request);

        $this->assertSame($originalMethod, $request->getMethod());
        $this->assertSame($originalPath, $request->getPathInfo());
        $this->assertSame($originalQuery, $request->query->all());
        $this->assertSame($originalBody, $request->getContent());
        $this->assertSame($originalCustomHeader, $request->headers->get('X-Custom'));
        $this->assertSame('application/json', $request->headers->get('Accept'));
        $this->assertSame('*/*', $request->headers->get('X-Original-Accept'));
    }

    protected function runMiddleware(Request $request): void
    {
        (new PrefersJsonResponses)->handle($request, fn ($request) => new Response);
    }
}
