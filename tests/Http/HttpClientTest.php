<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    public function testStubbedResponsesAreReturnedAfterFaking()
    {
        $factory = new Factory;
        $factory->fake();

        $response = $factory->post('http://laravel.com/test-missing-page');

        $this->assertTrue($response->ok());
    }

    public function testUrlsCanBeStubbedByPath()
    {
        $factory = new Factory;

        $factory->fake([
            'foo.com/*' => ['page' => 'foo'],
            'bar.com/*' => ['page' => 'bar'],
            '*' => ['page' => 'fallback'],
        ]);

        $fooResponse = $factory->post('http://foo.com/test');
        $barResponse = $factory->post('http://bar.com/test');
        $fallbackResponse = $factory->post('http://fallback.com/test');

        $this->assertEquals('foo', $fooResponse['page']);
        $this->assertEquals('bar', $barResponse['page']);
        $this->assertEquals('fallback', $fallbackResponse['page']);

        $factory->assertSent(function ($request) {
            return $request->url() === 'http://foo.com/test' &&
                   $request->hasHeader('Content-Type', 'application/json');
        });
    }

    public function testCanSendJsonData()
    {
        $factory = new Factory;

        $factory->fake();

        $fooResponse = $factory->withHeaders([
            'X-Test-Header' => 'foo',
        ])->post('http://foo.com/json', [
            'name' => 'Taylor',
        ]);

        $factory->assertSent(function ($request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('X-Test-Header', 'foo') &&
                   $request['name'] == 'Taylor';
        });
    }

    public function testCanSendFormData()
    {
        $factory = new Factory;

        $factory->fake();

        $fooResponse = $factory->asForm()->post('http://foo.com/form', [
            'name' => 'Taylor',
            'title' => 'Laravel Developer',
        ]);

        $factory->assertSent(function ($request) {
            return $request->url() === 'http://foo.com/form' &&
                   $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') &&
                   $request['name'] == 'Taylor';
        });
    }

    public function testCanSendMultipartData()
    {
        $factory = new Factory;

        $factory->fake();

        $fooResponse = $factory->asMultipart()->post('http://foo.com/multipart', [
            [
                'name' => 'foo',
                'contents' => 'data',
                'headers' => ['X-Test-Header' => 'foo'],
            ],
        ]);

        $factory->assertSent(function ($request) {
            return $request->url() === 'http://foo.com/multipart' &&
                   Str::startsWith($request->header('Content-Type')[0], 'multipart') &&
                   $request[0]['name'] == 'foo';
        });
    }

    public function testFilesCanBeAttached()
    {
        $factory = new Factory;

        $factory->fake();

        $fooResponse = $factory
                        ->attach('foo', 'data', 'file.txt', ['X-Test-Header' => 'foo'])
                        ->post('http://foo.com/file');

        $factory->assertSent(function ($request) {
            return $request->url() === 'http://foo.com/file' &&
                   Str::startsWith($request->header('Content-Type')[0], 'multipart') &&
                   $request[0]['name'] == 'foo' &&
                   $request->hasFile('foo', 'data', 'file.txt');
        });
    }

    public function testSequenceBuilder()
    {
        $factory = new Factory;

        $factory->fake([
            '*' => Factory::sequence()
                ->pushString('Ok', 201)
                ->pushJson(['fact' => 'Cats are great!'])
                ->pushFile(__DIR__.'/fixtures/test.txt')
                ->pushEmptyResponse(403),
        ]);

        /** @var PendingRequest $factory */
        $response = $factory->get('https://example.com');
        $this->assertSame('Ok', $response->body());
        $this->assertSame(201, $response->status());

        $response = $factory->get('https://example.com');
        $this->assertSame(['fact' => 'Cats are great!'], $response->json());
        $this->assertSame(200, $response->status());

        $response = $factory->get('https://example.com');
        $this->assertSame("This is a story about something that happened long ago when your grandfather was a child.\n", $response->body());
        $this->assertSame(200, $response->status());

        $response = $factory->get('https://example.com');
        $this->assertSame('', $response->body());
        $this->assertSame(403, $response->status());
    }
}
