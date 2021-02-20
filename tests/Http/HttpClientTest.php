<?php

namespace Illuminate\Tests\Http;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    /**
     * @var \Illuminate\Http\Client\Factory
     */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory;
    }

    public function testStubbedResponsesAreReturnedAfterFaking()
    {
        $this->factory->fake();

        $response = $this->factory->post('http://laravel.com/test-missing-page');

        $this->assertTrue($response->ok());
    }

    public function testResponseBodyCasting()
    {
        $this->factory->fake([
            '*' => ['result' => ['foo' => 'bar']],
        ]);

        $response = $this->factory->get('http://foo.com/api');

        $this->assertSame('{"result":{"foo":"bar"}}', $response->body());
        $this->assertSame('{"result":{"foo":"bar"}}', (string) $response);
        $this->assertIsArray($response->json());
        $this->assertSame(['foo' => 'bar'], $response->json()['result']);
        $this->assertSame(['foo' => 'bar'], $response->json('result'));
        $this->assertSame('bar', $response->json('result.foo'));
        $this->assertSame('default', $response->json('missing_key', 'default'));
        $this->assertSame(['foo' => 'bar'], $response['result']);
        $this->assertIsObject($response->object());
        $this->assertSame('bar', $response->object()->result->foo);
    }

    public function testResponseCanBeReturnedAsCollection()
    {
        $this->factory->fake([
            '*' => ['result' => ['foo' => 'bar']],
        ]);

        $response = $this->factory->get('http://foo.com/api');

        $this->assertInstanceOf(Collection::class, $response->collect());
        $this->assertEquals(collect(['result' => ['foo' => 'bar']]), $response->collect());
        $this->assertEquals(collect(['foo' => 'bar']), $response->collect('result'));
        $this->assertEquals(collect(['bar']), $response->collect('result.foo'));
        $this->assertEquals(collect(), $response->collect('missing_key'));
    }

    public function testUrlsCanBeStubbedByPath()
    {
        $this->factory->fake([
            'foo.com/*' => ['page' => 'foo'],
            'bar.com/*' => ['page' => 'bar'],
            '*' => ['page' => 'fallback'],
        ]);

        $fooResponse = $this->factory->post('http://foo.com/test');
        $barResponse = $this->factory->post('http://bar.com/test');
        $fallbackResponse = $this->factory->post('http://fallback.com/test');

        $this->assertSame('foo', $fooResponse['page']);
        $this->assertSame('bar', $barResponse['page']);
        $this->assertSame('fallback', $fallbackResponse['page']);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/test' &&
                   $request->hasHeader('Content-Type', 'application/json');
        });
    }

    public function testCanSendJsonData()
    {
        $this->factory->fake();

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://foo.com/json', [
            'name' => 'Taylor',
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('X-Test-Header', 'foo') &&
                   $request->hasHeader('X-Test-ArrayHeader', ['bar', 'baz']) &&
                   $request['name'] === 'Taylor';
        });
    }

    public function testCanSendFormData()
    {
        $this->factory->fake();

        $this->factory->asForm()->post('http://foo.com/form', [
            'name' => 'Taylor',
            'title' => 'Laravel Developer',
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/form' &&
                   $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') &&
                   $request['name'] === 'Taylor';
        });
    }

    public function testSpecificRequestIsNotBeingSent()
    {
        $this->factory->fake();

        $this->factory->post('http://foo.com/form', [
            'name' => 'Taylor',
        ]);

        $this->factory->assertNotSent(function (Request $request) {
            return $request->url() === 'http://foo.com/form' &&
                $request['name'] === 'Peter';
        });
    }

    public function testNoRequestIsNotBeingSent()
    {
        $this->factory->fake();

        $this->factory->assertNothingSent();
    }

    public function testRequestCount()
    {
        $this->factory->fake();
        $this->factory->assertSentCount(0);

        $this->factory->post('http://foo.com/form', [
            'name' => 'Taylor',
        ]);

        $this->factory->assertSentCount(1);

        $this->factory->post('http://foo.com/form', [
            'name' => 'Jim',
        ]);

        $this->factory->assertSentCount(2);
    }

    public function testCanSendMultipartData()
    {
        $this->factory->fake();

        $this->factory->asMultipart()->post('http://foo.com/multipart', [
            [
                'name' => 'foo',
                'contents' => 'data',
                'headers' => ['X-Test-Header' => 'foo'],
            ],
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/multipart' &&
                   Str::startsWith($request->header('Content-Type')[0], 'multipart') &&
                   $request[0]['name'] === 'foo';
        });
    }

    public function testFilesCanBeAttached()
    {
        $this->factory->fake();

        $this->factory->attach('foo', 'data', 'file.txt', ['X-Test-Header' => 'foo'])
                ->post('http://foo.com/file');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/file' &&
                   Str::startsWith($request->header('Content-Type')[0], 'multipart') &&
                   $request[0]['name'] === 'foo' &&
                   $request->hasFile('foo', 'data', 'file.txt');
        });
    }

    public function testCanSendMultipartDataWithSimplifiedParameters()
    {
        $this->factory->fake();

        $this->factory->asMultipart()->post('http://foo.com/multipart', [
            'foo' => 'bar',
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/multipart' &&
                Str::startsWith($request->header('Content-Type')[0], 'multipart') &&
                $request[0]['name'] === 'foo' &&
                $request[0]['contents'] === 'bar';
        });
    }

    public function testCanSendMultipartDataWithBothSimplifiedAndExtendedParameters()
    {
        $this->factory->fake();

        $this->factory->asMultipart()->post('http://foo.com/multipart', [
            'foo' => 'bar',
            [
                'name' => 'foobar',
                'contents' => 'data',
                'headers' => ['X-Test-Header' => 'foo'],
            ],
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/multipart' &&
                Str::startsWith($request->header('Content-Type')[0], 'multipart') &&
                $request[0]['name'] === 'foo' &&
                $request[0]['contents'] === 'bar' &&
                $request[1]['name'] === 'foobar' &&
                $request[1]['contents'] === 'data' &&
                $request[1]['headers']['X-Test-Header'] === 'foo';
        });
    }

    public function testItCanSendToken()
    {
        $this->factory->fake();

        $this->factory->withToken('token')->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                $request->hasHeader('Authorization', 'Bearer token');
        });
    }

    public function testItCanSendUserAgent()
    {
        $this->factory->fake();

        $this->factory->withUserAgent('Laravel')->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                $request->hasHeader('User-Agent', 'Laravel');
        });
    }

    public function testSequenceBuilder()
    {
        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->push('Ok', 201)
                ->push(['fact' => 'Cats are great!'])
                ->pushFile(__DIR__.'/fixtures/test.txt')
                ->pushStatus(403),
        ]);

        $response = $this->factory->get('https://example.com');
        $this->assertSame('Ok', $response->body());
        $this->assertSame(201, $response->status());

        $response = $this->factory->get('https://example.com');
        $this->assertSame(['fact' => 'Cats are great!'], $response->json());
        $this->assertSame(200, $response->status());

        $response = $this->factory->get('https://example.com');
        $this->assertSame("This is a story about something that happened long ago when your grandfather was a child.\n", $response->body());
        $this->assertSame(200, $response->status());

        $response = $this->factory->get('https://example.com');
        $this->assertSame('', $response->body());
        $this->assertSame(403, $response->status());

        $this->expectException(OutOfBoundsException::class);

        // The sequence is empty, it should throw an exception.
        $this->factory->get('https://example.com');
    }

    public function testSequenceBuilderCanKeepGoingWhenEmpty()
    {
        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->dontFailWhenEmpty()
                ->push('Ok'),
        ]);

        $response = $this->factory->get('https://laravel.com');
        $this->assertSame('Ok', $response->body());

        // The sequence is empty, but it should not fail.
        $this->factory->get('https://laravel.com');
    }

    public function testAssertSequencesAreEmpty()
    {
        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->push('1')
                ->push('2'),
        ]);

        $this->factory->get('https://example.com');
        $this->factory->get('https://example.com');

        $this->factory->assertSequencesAreEmpty();
    }

    public function testFakeSequence()
    {
        $this->factory->fakeSequence()
            ->pushStatus(201)
            ->pushStatus(301);

        $this->assertSame(201, $this->factory->get('https://example.com')->status());
        $this->assertSame(301, $this->factory->get('https://example.com')->status());
    }

    public function testWithCookies()
    {
        $this->factory->fakeSequence()->pushStatus(200);

        $response = $this->factory->withCookies(
            ['foo' => 'bar'], 'https://laravel.com'
        )->get('https://laravel.com');

        $this->assertCount(1, $response->cookies()->toArray());

        /** @var \GuzzleHttp\Cookie\CookieJarInterface $responseCookies */
        $responseCookie = $response->cookies()->toArray()[0];

        $this->assertSame('foo', $responseCookie['Name']);
        $this->assertSame('bar', $responseCookie['Value']);
        $this->assertSame('https://laravel.com', $responseCookie['Domain']);
    }

    public function testGetWithArrayQueryParam()
    {
        $this->factory->fake();

        $this->factory->get('http://foo.com/get', ['foo' => 'bar']);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/get?foo=bar'
                && $request['foo'] === 'bar';
        });
    }

    public function testGetWithStringQueryParam()
    {
        $this->factory->fake();

        $this->factory->get('http://foo.com/get', 'foo=bar');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/get?foo=bar'
                && $request['foo'] === 'bar';
        });
    }

    public function testGetWithQuery()
    {
        $this->factory->fake();

        $this->factory->get('http://foo.com/get?foo=bar&page=1');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/get?foo=bar&page=1'
                && $request['foo'] === 'bar'
                && $request['page'] === '1';
        });
    }

    public function testGetWithQueryWontEncode()
    {
        $this->factory->fake();

        $this->factory->get('http://foo.com/get?foo;bar;1;5;10&page=1');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/get?foo;bar;1;5;10&page=1'
                && ! isset($request['foo'])
                && ! isset($request['bar'])
                && $request['page'] === '1';
        });
    }

    public function testGetWithArrayQueryParamOverwrites()
    {
        $this->factory->fake();

        $this->factory->get('http://foo.com/get?foo=bar&page=1', ['hello' => 'world']);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/get?hello=world'
                && $request['hello'] === 'world';
        });
    }

    public function testGetWithArrayQueryParamEncodes()
    {
        $this->factory->fake();

        $this->factory->get('http://foo.com/get', ['foo;bar; space test' => 'laravel']);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/get?foo%3Bbar%3B%20space%20test=laravel'
                && $request['foo;bar; space test'] === 'laravel';
        });
    }

    public function testCanConfirmManyHeaders()
    {
        $this->factory->fake();

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeaders([
                       'X-Test-Header' => 'foo',
                       'X-Test-ArrayHeader' => ['bar', 'baz'],
                   ]);
        });
    }

    public function testCanConfirmManyHeadersUsingAString()
    {
        $this->factory->fake();

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeaders('X-Test-Header');
        });
    }

    public function testRequestExceptionSummary()
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('{"error":{"code":403,"message":"The Request can not be completed"}}');

        $error = [
            'error' => [
                'code' => 403,
                'message' => 'The Request can not be completed',
            ],
        ];
        $response = new Psr7Response(403, [], json_encode($error));

        throw new RequestException(new Response($response));
    }

    public function testRequestExceptionTruncatedSummary()
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('{"error":{"code":403,"message":"The Request can not be completed because quota limit was exceeded. Please, check our sup (truncated...)');

        $error = [
            'error' => [
                'code' => 403,
                'message' => 'The Request can not be completed because quota limit was exceeded. Please, check our support team to increase your limit',
            ],
        ];
        $response = new Psr7Response(403, [], json_encode($error));

        throw new RequestException(new Response($response));
    }

    public function testRequestExceptionEmptyBody()
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessageMatches('/HTTP request returned status code 403$/');

        $response = new Psr7Response(403);

        throw new RequestException(new Response($response));
    }

    public function testOnErrorDoesntCallClosureOnInformational()
    {
        $status = 0;
        $client = $this->factory->fake([
            'laravel.com' => $this->factory::response('', 101),
        ]);

        $response = $client->get('laravel.com')
            ->onError(function ($response) use (&$status) {
                $status = $response->status();
            });

        $this->assertSame(0, $status);
        $this->assertSame(101, $response->status());
    }

    public function testOnErrorDoesntCallClosureOnSuccess()
    {
        $status = 0;
        $client = $this->factory->fake([
            'laravel.com' => $this->factory::response('', 201),
        ]);

        $response = $client->get('laravel.com')
            ->onError(function ($response) use (&$status) {
                $status = $response->status();
            });

        $this->assertSame(0, $status);
        $this->assertSame(201, $response->status());
    }

    public function testOnErrorDoesntCallClosureOnRedirection()
    {
        $status = 0;
        $client = $this->factory->fake([
            'laravel.com' => $this->factory::response('', 301),
        ]);

        $response = $client->get('laravel.com')
            ->onError(function ($response) use (&$status) {
                $status = $response->status();
            });

        $this->assertSame(0, $status);
        $this->assertSame(301, $response->status());
    }

    public function testOnErrorCallsClosureOnClientError()
    {
        $status = 0;
        $client = $this->factory->fake([
            'laravel.com' => $this->factory::response('', 401),
        ]);

        $response = $client->get('laravel.com')
            ->onError(function ($response) use (&$status) {
                $status = $response->status();
            });

        $this->assertSame(401, $status);
        $this->assertSame(401, $response->status());
    }

    public function testOnErrorCallsClosureOnServerError()
    {
        $status = 0;
        $client = $this->factory->fake([
            'laravel.com' => $this->factory::response('', 501),
        ]);

        $response = $client->get('laravel.com')
            ->onError(function ($response) use (&$status) {
                $status = $response->status();
            });

        $this->assertSame(501, $status);
        $this->assertSame(501, $response->status());
    }

    public function testSinkToFile()
    {
        $this->factory->fakeSequence()->push('abc123');

        $destination = __DIR__.'/fixtures/sunk.txt';

        if (file_exists($destination)) {
            unlink($destination);
        }

        $this->factory->withOptions(['sink' => $destination])->get('https://example.com');

        $this->assertFileExists($destination);
        $this->assertSame('abc123', file_get_contents($destination));

        unlink($destination);
    }

    public function testSinkToResource()
    {
        $this->factory->fakeSequence()->push('abc123');

        $resource = fopen('php://temp', 'w');

        $this->factory->sink($resource)->get('https://example.com');

        $this->assertSame(0, ftell($resource));
        $this->assertSame('abc123', stream_get_contents($resource));
    }

    public function testSinkWhenStubbedByPath()
    {
        $this->factory->fake([
            'foo.com/*' => ['page' => 'foo'],
        ]);

        $resource = fopen('php://temp', 'w');

        $this->factory->sink($resource)->get('http://foo.com/test');

        $this->assertSame(json_encode(['page' => 'foo']), stream_get_contents($resource));
    }

    public function testCanAssertAgainstOrderOfHttpRequestsWithUrlStrings()
    {
        $this->factory->fake();

        $exampleUrls = [
            'http://example.com/1',
            'http://example.com/2',
            'http://example.com/3',
        ];

        foreach ($exampleUrls as $url) {
            $this->factory->get($url);
        }

        $this->factory->assertSentInOrder($exampleUrls);
    }

    public function testAssertionsSentOutOfOrderThrowAssertionFailed()
    {
        $this->factory->fake();

        $exampleUrls = [
            'http://example.com/1',
            'http://example.com/2',
            'http://example.com/3',
        ];

        $this->factory->get($exampleUrls[0]);
        $this->factory->get($exampleUrls[2]);
        $this->factory->get($exampleUrls[1]);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->factory->assertSentInOrder($exampleUrls);
    }

    public function testWrongNumberOfRequestsThrowAssertionFailed()
    {
        $this->factory->fake();

        $exampleUrls = [
            'http://example.com/1',
            'http://example.com/2',
            'http://example.com/3',
        ];

        $this->factory->get($exampleUrls[0]);
        $this->factory->get($exampleUrls[1]);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->factory->assertSentInOrder($exampleUrls);
    }

    public function testCanAssertAgainstOrderOfHttpRequestsWithCallables()
    {
        $this->factory->fake();

        $exampleUrls = [
            function ($request) {
                return $request->url() == 'http://example.com/1';
            },
            function ($request) {
                return $request->url() == 'http://example.com/2';
            },
            function ($request) {
                return $request->url() == 'http://example.com/3';
            },
        ];

        $this->factory->get('http://example.com/1');
        $this->factory->get('http://example.com/2');
        $this->factory->get('http://example.com/3');

        $this->factory->assertSentInOrder($exampleUrls);
    }

    public function testCanAssertAgainstOrderOfHttpRequestsWithCallablesAndHeaders()
    {
        $this->factory->fake();

        $executionOrder = [
            function (Request $request) {
                return $request->url() === 'http://foo.com/json' &&
                       $request->hasHeader('Content-Type', 'application/json') &&
                       $request->hasHeader('X-Test-Header', 'foo') &&
                       $request->hasHeader('X-Test-ArrayHeader', ['bar', 'baz']) &&
                       $request['name'] === 'Taylor';
            },
            function (Request $request) {
                return $request->url() === 'http://bar.com/json' &&
                       $request->hasHeader('Content-Type', 'application/json') &&
                       $request->hasHeader('X-Test-Header', 'bar') &&
                       $request->hasHeader('X-Test-ArrayHeader', ['bar', 'baz']) &&
                       $request['name'] === 'Taylor';
            },
        ];

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://foo.com/json', [
            'name' => 'Taylor',
        ]);

        $this->factory->withHeaders([
            'X-Test-Header' => 'bar',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://bar.com/json', [
            'name' => 'Taylor',
        ]);

        $this->factory->assertSentInOrder($executionOrder);
    }

    public function testCanAssertAgainstOrderOfHttpRequestsWithCallablesAndHeadersFailsCorrectly()
    {
        $this->factory->fake();

        $executionOrder = [
            function (Request $request) {
                return $request->url() === 'http://bar.com/json' &&
                       $request->hasHeader('Content-Type', 'application/json') &&
                       $request->hasHeader('X-Test-Header', 'bar') &&
                       $request->hasHeader('X-Test-ArrayHeader', ['bar', 'baz']) &&
                       $request['name'] === 'Taylor';
            },
            function (Request $request) {
                return $request->url() === 'http://foo.com/json' &&
                       $request->hasHeader('Content-Type', 'application/json') &&
                       $request->hasHeader('X-Test-Header', 'foo') &&
                       $request->hasHeader('X-Test-ArrayHeader', ['bar', 'baz']) &&
                       $request['name'] === 'Taylor';
            },
        ];

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://foo.com/json', [
            'name' => 'Taylor',
        ]);

        $this->factory->withHeaders([
            'X-Test-Header' => 'bar',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://bar.com/json', [
            'name' => 'Taylor',
        ]);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->factory->assertSentInOrder($executionOrder);
    }
}
