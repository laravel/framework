<?php

namespace Illuminate\Tests\Support;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Testing\Fakes\HttpFake;
use Illuminate\Support\Testing\HttpHistory;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class SupportTestingHttpFakeTest extends TestCase
{
    public function testCanSendJsonData()
    {
        $factory = new HttpFake();
        $factory->pushStatus(200);

        /** @var PendingRequest $factory */
        $fooResponse = $factory->withHeaders([
            'X-Test-Header' => 'foo',
        ])->post('http://foo.com/json', [
            'name' => 'Taylor',
        ]);

        $factory->assertSent(function (HttpHistory $history) {
            $this->assertSame('http://foo.com/json', $history->request->url());
            $this->assertTrue($history->request->hasHeader('Content-Type', 'application/json'));
            $this->assertTrue($history->request->hasHeader('X-Test-Header', 'foo'));
            $this->assertSame('Taylor', $history->request['name']);

            return true;
        });
    }

    public function testCanSendFormData()
    {
        $factory = new HttpFake();
        $factory->pushStatus(422);

        /** @var PendingRequest $factory */
        $fooResponse = $factory->asForm()->post('http://foo.com/form', [
            'name' => 'Taylor',
            'title' => 'Laravel Developer',
        ]);

        $factory->assertSent(function (HttpHistory $history) {
            $this->assertSame('http://foo.com/form', $history->request->url());
            $this->assertTrue($history->request->hasHeader('Content-Type', 'application/x-www-form-urlencoded'));
            $this->assertSame('Taylor', $history->request['name']);
            $this->assertSame('Laravel Developer', $history->request['title']);

            return true;
        });
    }

    public function testCanSendMultipartData()
    {
        $factory = new HttpFake();
        $factory->pushStatus(200);

        /** @var PendingRequest $factory */
        $fooResponse = $factory->asMultipart()->post('http://foo.com/multipart', [
            [
                'name' => 'foo',
                'contents' => 'data',
                'headers' => ['X-Test-Header' => 'foo'],
            ],
        ]);

        $factory->assertSent(function (HttpHistory $history) {
            $this->assertSame('http://foo.com/multipart', $history->request->url());
            $this->assertStringStartsWith('multipart', $history->request->header('Content-Type')[0]);
            $this->assertSame('foo', $history->options['laravel_data'][0]['name']);

            return true;
        });
    }

    public function testFilesCanBeAttached()
    {
        $factory = new HttpFake();
        $factory->pushStatus(200);

        /** @var PendingRequest $factory */
        $fooResponse = $factory
            ->attach('foo', 'data', 'file.txt', ['X-Test-Header' => 'foo'])
            ->post('http://foo.com/file');

        $factory->assertSent(function (HttpHistory $history) {
            $this->assertSame('http://foo.com/file', $history->request->url());
            $this->assertStringStartsWith('multipart', $history->request->header('Content-Type')[0]);
            $this->assertSame('foo', $history->request[0]['name']);
            $this->assertTrue($history->request->hasFile('foo', 'data', 'file.txt'));

            return true;
        });
    }

    public function testMockQueue()
    {
        $factory = (new HttpFake)
            ->push('Ok', 201)
            ->push(['fact' => 'Cats are great!'])
            ->pushFile(__DIR__.'/fixtures/example-01.json')
            ->pushStatus(403);

        $factory->assertMockQueueCount(4);

        /** @var PendingRequest $factory */
        $response = $factory->get('https://example.com');
        $this->assertSame('Ok', $response->body());
        $this->assertSame(201, $response->status());

        $response = $factory->get('https://example.com');
        $this->assertSame(['fact' => 'Cats are great!'], $response->json());
        $this->assertSame(200, $response->status());

        $response = $factory->get('https://example.com');
        $this->assertSame(['fact' => 'Cats are great!'], $response->json());
        $this->assertSame(200, $response->status());

        $response = $factory->get('https://example.com');
        $this->assertSame('', $response->body());
        $this->assertSame(403, $response->status());

        $factory->assertMockQueueEmpty();

        $this->expectException(OutOfBoundsException::class);

        // The sequence is empty, it should throw an exception.
        $factory->get('https://example.com');
    }

    public function testMockQueueWithDefaultResponse()
    {
        $factory = (new HttpFake)
            ->push('Ok')
            ->defaultResponse();

        /** @var PendingRequest $factory */
        $this->assertSame('Ok', $factory->get('https://laravel.com')->body());

        $response = $factory->get('https://laravel.com');
        $this->assertSame('', $response->body());
        $this->assertSame(200, $response->status());

        $factory->assertMockQueueEmpty();

        $factory->defaultResponse(function () {
            return new \GuzzleHttp\Psr7\Response(422);
        });

        $response = $factory->get('https://laravel.com');
        $this->assertSame(422, $response->status());
    }

    public function testWithCookies()
    {
        $factory = (new HttpFake)
            ->pushStatus(200);

        /** @var PendingRequest $factory */
        $response = $factory->withCookies(
            CookieJar::fromArray(['bar' => 'baz'], 'http://laravel.com')
        )->get('http://laravel.com');

        $cookieArray = $response->cookies()->toArray();

        $this->assertSame('bar', $cookieArray[0]['Name']);
        $this->assertSame('baz', $cookieArray[0]['Value']);
        $this->assertSame('http://laravel.com', $cookieArray[0]['Domain']);
    }

    public function testEffectiveUriFromTransferStats()
    {
        $factory = (new HttpFake)
            ->push('Ok');

        /** @var PendingRequest $factory */
        $response = $factory->get('https://laravel.com');

        $this->assertInstanceOf(Uri::class, $response->effectiveUri());

        $this->assertSame('https://laravel.com', (string) $response->effectiveUri());
    }
}
