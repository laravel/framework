<?php

namespace Illuminate\Tests\Http;

use Exception;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use JsonSerializable;
use Mockery as m;
use OutOfBoundsException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;

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

        RequestException::truncate();
    }

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testStubbedResponsesAreReturnedAfterFaking()
    {
        $this->factory->fake();

        $response = $this->factory->post('http://laravel.com/test-missing-page');

        $this->assertTrue($response->ok());
    }

    public function testCreatedRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_CREATED),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->created());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->created());
    }

    public function testStatusCodeShorthand()
    {
        $this->factory->fake([
            'forge.laravel.com' => 204,
            'vapor.laravel.com' => HttpResponse::HTTP_CREATED,
        ]);

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertTrue($response->noContent());

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->created());
    }

    public function testStatusCodeShorthandAssumeBodyWhenInvalidHttpStatusCode()
    {
        $this->factory->fake([
            'forge.laravel.com' => 999,
            'vapor.laravel.com' => 1,
        ]);

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertTrue($response->ok());
        $this->assertSame('999', $response->body());

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->ok());
        $this->assertSame('1', $response->body());
    }

    public function testBodyShorthands()
    {
        $this->factory->fake([
            'google.com' => 'Hello World',
            'github.com' => ['foo' => 'bar'],
        ]);

        $response = $this->factory->get('http://google.com');
        $this->assertTrue($response->ok());
        $this->assertSame('Hello World', $response->body());

        $response = $this->factory->post('http://github.com');
        $this->assertTrue($response->ok());
        $this->assertSame('{"foo":"bar"}', $response->body());
        $this->assertSame(['foo' => 'bar'], $response->json());
    }

    public function testAcceptedRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_ACCEPTED),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->accepted());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->accepted());
    }

    public function testMovedPermanentlyRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_MOVED_PERMANENTLY),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->movedPermanently());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->movedPermanently());
    }

    public function testNoContentRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_NO_CONTENT),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->noContent());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->noContent());
    }

    public function testFoundRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_FOUND),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->found());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->found());
    }

    public function testNotModifiedRequest(): void
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_NOT_MODIFIED),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('https://vapor.laravel.com');
        $this->assertTrue($response->notModified());

        $response = $this->factory->post('https://forge.laravel.com');
        $this->assertFalse($response->notModified());
    }

    public function testBadRequestRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_BAD_REQUEST),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->badRequest());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->badRequest());
    }

    public function testPaymentRequiredRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_PAYMENT_REQUIRED),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->paymentRequired());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->paymentRequired());
    }

    public function testRequestTimeoutRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_REQUEST_TIMEOUT),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->requestTimeout());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->requestTimeout());
    }

    public function testConflictResponseRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_CONFLICT),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->conflict());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->conflict());
    }

    public function testUnprocessableContentRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_UNPROCESSABLE_ENTITY),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->unprocessableContent());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->unprocessableContent());
    }

    public function testUnprocessableEntityRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_UNPROCESSABLE_ENTITY),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->unprocessableEntity());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->unprocessableEntity());
    }

    public function testTooManyRequestsRequest()
    {
        $this->factory->fake([
            'vapor.laravel.com' => $this->factory::response('', HttpResponse::HTTP_TOO_MANY_REQUESTS),
            'forge.laravel.com' => $this->factory::response('', HttpResponse::HTTP_OK),
        ]);

        $response = $this->factory->post('http://vapor.laravel.com');
        $this->assertTrue($response->tooManyRequests());

        $response = $this->factory->post('http://forge.laravel.com');
        $this->assertFalse($response->tooManyRequests());
    }

    public function testUnauthorizedRequest()
    {
        $this->factory->fake([
            'laravel.com' => $this->factory::response('', 401),
        ]);

        $response = $this->factory->post('http://laravel.com');

        $this->assertTrue($response->unauthorized());
    }

    public function testForbiddenRequest()
    {
        $this->factory->fake([
            'laravel.com' => $this->factory::response('', 403),
        ]);

        $response = $this->factory->post('http://laravel.com');

        $this->assertTrue($response->forbidden());
    }

    public function testNotFoundResponse()
    {
        $this->factory->fake([
            'laravel.com' => $this->factory::response('', 404),
        ]);

        $response = $this->factory->post('http://laravel.com');

        $this->assertTrue($response->notFound());
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
    }

    public function testResponseObjectAsArray()
    {
        $this->factory->fake([
            '*' => [['foo' => 'bar'], ['bar' => 'foo']],
        ]);

        $response = $this->factory->get('http://foo.com/api');

        $this->assertSame('[{"foo":"bar"},{"bar":"foo"}]', $response->body());
        $this->assertSame('[{"foo":"bar"},{"bar":"foo"}]', (string) $response);
        $this->assertIsArray($response->object());
        $this->assertSame('bar', $response->object()[0]->foo);
    }

    public function testResponseObjectAsObject()
    {
        $this->factory->fake([
            '*' => ['result' => ['foo' => 'bar']],
        ]);

        $response = $this->factory->get('http://foo.com/api');

        $this->assertIsObject($response->object());
        $this->assertSame('bar', $response->object()->result->foo);
    }

    public function testResponseCanBeReturnedAsResource()
    {
        $this->factory->fake([
            '*' => ['result' => ['foo' => 'bar']],
        ]);

        $response = $this->factory->get('http://foo.com/api');

        $this->assertIsResource($response->resource());
        $this->assertSame('{"result":{"foo":"bar"}}', stream_get_contents($response->resource()));
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

    public function testResponseCanBeReturnedAsFluent()
    {
        $this->factory->fake([
            '*' => ['result' => ['foo' => 'bar']],
        ]);

        $response = $this->factory->get('http://foo.com/api');

        $this->assertInstanceOf(Fluent::class, $response->fluent());
        $this->assertEquals(new Fluent(['result' => ['foo' => 'bar']]), $response->fluent());
        $this->assertEquals(new Fluent(['foo' => 'bar']), $response->fluent('result'));
        $this->assertEquals(new Fluent(['bar']), $response->fluent('result.foo'));
        $this->assertEquals(new Fluent([]), $response->fluent('missing_key'));
    }

    public function testSendRequestBodyAsJsonByDefault()
    {
        $body = '{"test":"phpunit"}';

        $fakeRequest = function (Request $request) use ($body) {
            self::assertSame($body, $request->body());
            self::assertContains('application/json', $request->header('Content-Type'));

            return ['my' => 'response'];
        };

        $this->factory->fake($fakeRequest);

        $this->factory->withBody($body)->send('get', 'http://foo.com/api');
    }

    public function testSendRequestBodyWithManyAmpersands()
    {
        $body = str_repeat('A thousand &. ', 1000);

        $fakeRequest = function (Request $request) use ($body) {
            self::assertSame($body, $request->body());
            self::assertContains('text/plain', $request->header('Content-Type'));

            return ['my' => 'response'];
        };

        $this->factory->fake($fakeRequest);

        $this->factory->withBody($body, 'text/plain')->send('post', 'http://foo.com/api');
    }

    public function testSendStreamRequestBody()
    {
        $string = 'Look at me, i am a stream!!';
        $resource = fopen('php://temp', 'w');
        fwrite($resource, $string);
        rewind($resource);
        $body = Utils::streamFor($resource);

        $fakeRequest = function (Request $request) use ($string) {
            self::assertSame($string, $request->body());
            self::assertContains('text/plain', $request->header('Content-Type'));

            return ['my' => 'response'];
        };

        $this->factory->fake($fakeRequest);

        $this->factory->withBody($body, 'text/plain')->send('post', 'http://foo.com/api');
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

    public function testCanSendArrayableFormData()
    {
        $this->factory->fake();

        $this->factory->asForm()->post('http://foo.com/form', new Fluent([
            'name' => 'Taylor',
            'title' => 'Laravel Developer',
        ]));

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/form' &&
                   $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') &&
                   $request['name'] === 'Taylor';
        });
    }

    public function testCanSendJsonSerializableData()
    {
        $this->factory->fake();

        $this->factory->asJson()->post('http://foo.com/form', new class implements JsonSerializable
        {
            public function jsonSerialize(): mixed
            {
                return [
                    'name' => 'Taylor',
                    'title' => 'Laravel Developer',
                ];
            }
        });

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/form' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['name'] === 'Taylor';
        });
    }

    public function testPrefersJsonSerializableOverArrayableData()
    {
        $this->factory->fake();

        $this->factory->asJson()->post('http://foo.com/form', new class implements JsonSerializable, Arrayable
        {
            public function jsonSerialize(): mixed
            {
                return [
                    'attributes' => (object) [],
                ];
            }

            public function toArray(): array
            {
                return [
                    'attributes' => [],
                ];
            }
        });

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/form' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request->body() === '{"attributes":{}}';
        });
    }

    public function testCanSendJsonDataWithStringable()
    {
        $this->factory->fake();

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
            'X-Test-ArrayHeader' => ['bar', 'baz'],
        ])->post('http://foo.com/json', [
            'name' => new Stringable('Taylor'),
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('X-Test-Header', 'foo') &&
                   $request->hasHeader('X-Test-ArrayHeader', ['bar', 'baz']) &&
                   $request['name'] === 'Taylor';
        });
    }

    public function testCanSendFormDataWithStringable()
    {
        $this->factory->fake();

        $this->factory->asForm()->post('http://foo.com/form', [
            'name' => new Stringable('Taylor'),
            'title' => 'Laravel Developer',
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/form' &&
                   $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') &&
                   $request['name'] === 'Taylor';
        });
    }

    public function testCanSendFormDataWithStringableInArrays()
    {
        $this->factory->fake();

        $this->factory->asForm()->post('http://foo.com/form', [
            'posts' => [['title' => new Stringable('Taylor')]],
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/form' &&
                   $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') &&
                   $request['posts'][0]['title'] === 'Taylor';
        });
    }

    public function testRecordedCallsAreEmptiedWhenFakeIsCalled()
    {
        $this->factory->fake([
            'http://foo.com/*' => ['page' => 'foo'],
        ]);

        $this->factory->get('http://foo.com/test');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/test';
        });

        $this->factory->fake();

        $this->factory->assertNothingSent();
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

    public function testItOnlySendsOneUserAgentHeader()
    {
        $this->factory->fake();

        $this->factory->withUserAgent('Laravel')
            ->withUserAgent('FooBar')
            ->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            $userAgent = $request->header('User-Agent');

            return $request->url() === 'http://foo.com/json' &&
                count($userAgent) === 1 &&
                $userAgent[0] === 'FooBar';
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
        $this->assertSame('application/json', $response->header('Content-Type'));
        $this->assertSame(200, $response->status());

        $response = $this->factory->get('https://example.com');
        $this->assertSame(
            "This is a story about something that happened long ago when your grandfather was a child.\n",
            str_replace("\r\n", "\n", $response->body())
        );
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

    public function testWithQueryParameters()
    {
        $this->factory->fake();

        $this->factory->withQueryParameters(
            ['foo' => 'bar']
        )->get('https://laravel.com');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'https://laravel.com?foo=bar';
        });
    }

    public function testWithArrayQueryParameters()
    {
        $this->factory->fake();

        $this->factory->withQueryParameters(
            ['foo' => ['bar', 'baz']],
        )->get('https://laravel.com');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'https://laravel.com?foo%5B0%5D=bar&foo%5B1%5D=baz';
        });
    }

    public function testWithQueryParametersAllowsAddingMoreOnRequest()
    {
        $this->factory->fake();

        $this->factory->withQueryParameters(
            ['foo' => 'bar']
        )->get('https://laravel.com', [
            'baz' => 'qux',
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'https://laravel.com?foo=bar&baz=qux';
        });
    }

    public function testWithQueryParametersAllowsOverridingParameterOnRequest()
    {
        $this->factory->fake();

        $this->factory->withQueryParameters([
            'foo' => 'bar',
            'baz' => 'baz',
        ])->get('https://laravel.com', [
            // Override the previously set value
            'baz' => 'qux',
        ]);

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'https://laravel.com?foo=bar&baz=qux';
        });
    }

    public function testWithStringableQueryParameters()
    {
        $this->factory->fake();

        $this->factory->withQueryParameters(
            ['foo' => new Stringable('bar')]
        )->get('https://laravel.com');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'https://laravel.com?foo=bar';
        });
    }

    public function testWithArrayStringableQueryParameters()
    {
        $this->factory->fake();

        $this->factory->withQueryParameters(
            ['foo' => ['bar', new Stringable('baz')]],
        )->get('https://laravel.com');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'https://laravel.com?foo%5B0%5D=bar&foo%5B1%5D=baz';
        });
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

    public function testGetWithArrayableQueryParam()
    {
        $this->factory->fake();

        $this->factory->get('http://foo.com/get', new Fluent(['foo' => 'bar']));

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

    public function testWithBaseUrl()
    {
        $this->factory->fake();

        $this->factory->baseUrl('http://foo.com/')->get('get');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/get';
        });

        $this->factory->fake();

        $this->factory->baseUrl('http://foo.com/')->get('http://bar.com/get');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://bar.com/get';
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

    public function testItMergesMultipleHeaders()
    {
        $this->factory->fake();

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
        ])->withHeaders([
            'X-Test-Header' => 'bar',
        ])->withHeaders([
            'X-Test-Header' => ['baz', 'qux'],
        ])->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeaders(['X-Test-Header' => ['foo', 'bar', 'baz', 'qux']]);
        });
    }

    public function testItCanReplaceHeaders()
    {
        $this->factory->fake();

        $this->factory->withHeaders([
            'X-Test-Header' => 'foo',
        ])->replaceHeaders([
            'X-Test-Header' => 'baz',
        ])->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeaders(['X-Test-Header' => ['baz']]);
        });
    }

    public function testItCanReplaceHeadersWhenNoHeadersYetSet()
    {
        $this->factory->fake();

        $this->factory->replaceHeaders([
            'X-Test-Header' => 'baz',
        ])->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                   $request->hasHeaders(['X-Test-Header' => ['baz']]);
        });
    }

    public function testCanConfirmSingleStringHeader()
    {
        $this->factory->fake();

        $this->factory->withHeader('X-Test-Header', 'foo')->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                $request->hasHeaders([
                    'X-Test-Header' => 'foo',
                ]);
        });
    }

    public function testCanConfirmSingleArrayHeader()
    {
        $this->factory->fake();

        $this->factory->withHeader('X-Test-ArrayHeader', ['bar', 'baz'])->post('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'http://foo.com/json' &&
                $request->hasHeaders([
                    'X-Test-ArrayHeader' => ['bar', 'baz'],
                ]);
        });
    }

    public function testExceptionAccessorOnSuccess()
    {
        $resp = new Response(new Psr7Response());

        $this->assertNull($resp->toException());
    }

    public function testExceptionAccessorOnFailure()
    {
        $error = [
            'error' => [
                'code' => 403,
                'message' => 'The Request can not be completed',
            ],
        ];
        $response = new Psr7Response(403, [], json_encode($error));
        $resp = new Response($response);

        $this->assertInstanceOf(RequestException::class, $resp->toException());
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

    public function testRequestExceptionWithoutTruncatedSummary()
    {
        RequestException::dontTruncate();

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('{"error":{"code":403,"message":"The Request can not be completed because quota limit was exceeded. Please, check our support team to increase your limit');

        $error = [
            'error' => [
                'code' => 403,
                'message' => 'The Request can not be completed because quota limit was exceeded. Please, check our support team to increase your limit',
            ],
        ];
        $response = new Psr7Response(403, [], json_encode($error));

        throw new RequestException(new Response($response));
    }

    public function testRequestExceptionWithCustomTruncatedSummary()
    {
        RequestException::truncateAt(60);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('{"error":{"code":403,"message":"The Request can not be compl (truncated...)');

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

        $this->expectException(AssertionFailedError::class);

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

        $this->expectException(AssertionFailedError::class);

        $this->factory->assertSentInOrder($exampleUrls);
    }

    public function testCanAssertAgainstOrderOfHttpRequestsWithCallables()
    {
        $this->factory->fake();

        $exampleUrls = [
            function ($request) {
                return $request->url() === 'http://example.com/1';
            },
            function ($request) {
                return $request->url() === 'http://example.com/2';
            },
            function ($request) {
                return $request->url() === 'http://example.com/3';
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

        $this->expectException(AssertionFailedError::class);

        $this->factory->assertSentInOrder($executionOrder);
    }

    public function testCanDump()
    {
        $dumped = [];

        VarDumper::setHandler(function ($value) use (&$dumped) {
            $dumped[] = $value;
        });

        $this->factory->fake()->dump(1, 2, 3)->withOptions(['delay' => 1000])->get('http://foo.com');

        $this->assertSame(1, $dumped[0]);
        $this->assertSame(2, $dumped[1]);
        $this->assertSame(3, $dumped[2]);
        $this->assertInstanceOf(Request::class, $dumped[3]);
        $this->assertSame(1000, $dumped[4]['delay']);

        VarDumper::setHandler(null);
    }

    public function testResponseCanDump()
    {
        $dumped = [];

        VarDumper::setHandler(function ($value) use (&$dumped) {
            $dumped[] = $value;
        });

        $this->factory->fake([
            '200.com' => $this->factory::response('hello', 200),
        ]);

        $this->factory->get('http://200.com')->dump();

        $this->assertSame('hello', $dumped[0]);

        VarDumper::setHandler(null);
    }

    public function testResponseCanDumpWithKey()
    {
        $dumped = [];

        VarDumper::setHandler(function ($value) use (&$dumped) {
            $dumped[] = $value;
        });

        $this->factory->fake([
            '200.com' => $this->factory::response(['hello' => 'world'], 200),
        ]);

        $this->factory->get('http://200.com')->dump('hello');

        $this->assertSame('world', $dumped[0]);

        VarDumper::setHandler(null);
    }

    public function testResponseCanDumpHeaders()
    {
        $dumped = [];

        VarDumper::setHandler(function ($value) use (&$dumped) {
            $dumped[] = $value;
        });

        $this->factory->fake([
            '200.com' => $this->factory::response('hello', 200, ['hello' => 'world']),
        ]);

        $this->factory->get('http://200.com')->dumpHeaders();

        $this->assertSame(['hello' => ['world']], $dumped[0]);

        VarDumper::setHandler(null);
    }

    public function testResponseSequenceIsMacroable()
    {
        ResponseSequence::macro('customMethod', function () {
            return 'yes!';
        });

        $this->assertSame('yes!', $this->factory->fakeSequence()->customMethod());
    }

    public function testRequestsCanBeAsync()
    {
        $request = new PendingRequest($this->factory);

        $promise = $request->async()->get('http://foo.com');

        $this->assertInstanceOf(PromiseInterface::class, $promise);

        $this->assertSame($promise, $request->getPromise());
    }

    public function testClientCanBeSet()
    {
        $client = $this->factory->buildClient();

        $request = new PendingRequest($this->factory);

        $this->assertNotSame($client, $request->buildClient());

        $request->setClient($client);

        $this->assertSame($client, $request->buildClient());
    }

    public function testRequestsCanReplaceOptions()
    {
        $request = new PendingRequest($this->factory);

        $request = $request->withOptions(['http_errors' => true, 'connect_timeout' => 10]);

        $this->assertSame(['connect_timeout' => 10, 'crypto_method' => 33, 'http_errors' => true, 'timeout' => 30], $request->getOptions());

        $request = $request->withOptions(['connect_timeout' => 20]);

        $this->assertSame(['connect_timeout' => 20, 'crypto_method' => 33, 'http_errors' => true, 'timeout' => 30], $request->getOptions());
    }

    public function testMultipleRequestsAreSentInThePool()
    {
        $this->factory->fake([
            '200.com' => $this->factory::response('', 200),
            '400.com' => $this->factory::response('', 400),
            '500.com' => $this->factory::response('', 500),
        ]);

        $responses = $this->factory->pool(function (Pool $pool) {
            return [
                $pool->get('200.com'),
                $pool->get('400.com'),
                $pool->get('500.com'),
            ];
        });

        $this->assertSame(200, $responses[0]->status());
        $this->assertSame(400, $responses[1]->status());
        $this->assertSame(500, $responses[2]->status());
    }

    public function testMultipleRequestsAreSentInThePoolWithKeys()
    {
        $this->factory->fake([
            '200.com' => $this->factory::response('', 200),
            '400.com' => $this->factory::response('', 400),
            '500.com' => $this->factory::response('', 500),
        ]);

        $responses = $this->factory->pool(function (Pool $pool) {
            return [
                $pool->as('test200')->get('200.com'),
                $pool->as('test400')->get('400.com'),
                $pool->as('test500')->get('500.com'),
            ];
        });

        $this->assertSame(200, $responses['test200']->status());
        $this->assertSame(400, $responses['test400']->status());
        $this->assertSame(500, $responses['test500']->status());
    }

    public function testMiddlewareRunsInPool()
    {
        $this->factory->fake(function (Request $request) {
            return $this->factory->response('Fake');
        });

        $history = [];

        $middleware = Middleware::history($history);

        $responses = $this->factory->pool(fn (Pool $pool) => [
            $pool->withMiddleware($middleware)->post('https://example.com', ['hyped-for' => 'laravel-movie']),
        ]);

        $response = $responses[0];

        $this->assertSame('Fake', $response->body());

        $this->assertCount(1, $history);

        $this->assertSame('Fake', tap($history[0]['response']->getBody())->rewind()->getContents());

        $this->assertSame(['hyped-for' => 'laravel-movie'], json_decode(tap($history[0]['request']->getBody())->rewind()->getContents(), true));
    }

    public function testTheRequestSendingAndResponseReceivedEventsAreFiredWhenARequestIsSent()
    {
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->times(5)->with(m::type(RequestSending::class));
        $events->shouldReceive('dispatch')->times(5)->with(m::type(ResponseReceived::class));

        $factory = new Factory($events);
        $factory->fake();

        $factory->get('https://example.com');
        $factory->head('https://example.com');
        $factory->post('https://example.com');
        $factory->patch('https://example.com');
        $factory->delete('https://example.com');
    }

    public function testTheRequestSendingAndResponseReceivedEventsAreFiredWhenARequestIsSentAsync()
    {
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->times(5)->with(m::type(RequestSending::class));
        $events->shouldReceive('dispatch')->times(5)->with(m::type(ResponseReceived::class));

        $factory = new Factory($events);
        $factory->fake();
        $factory->pool(function (Pool $pool) {
            return [
                $pool->get('https://example.com'),
                $pool->head('https://example.com'),
                $pool->post('https://example.com'),
                $pool->patch('https://example.com'),
                $pool->delete('https://example.com'),
            ];
        });
    }

    public function testTheRequestSendingAndResponseReceivedEventsAreFiredForEveryRetry()
    {
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->times(2)->with(m::type(RequestSending::class));
        $events->shouldReceive('dispatch')->times(2)->with(m::type(ResponseReceived::class));

        $factory = new Factory($events);
        $factory->fake([
            '*' => $factory->response(['error'], 403),
        ]);

        $response = $factory->retry(2, 1000, null, false)->get('http://foo.com/get');

        $this->assertTrue($response->failed());

        $factory->assertSentCount(2);
    }

    public function testTheTransferStatsAreCalledSafelyWhenFakingTheRequest()
    {
        $this->factory->fake(['https://example.com' => ['world' => 'Hello world']]);
        $stats = $this->factory->get('https://example.com')->handlerStats();
        $effectiveUri = $this->factory->get('https://example.com')->effectiveUri();

        $this->assertIsArray($stats);
        $this->assertEmpty($stats);

        $this->assertNull($effectiveUri);
    }

    public function testTransferStatsArePresentWhenFakingTheRequestUsingAPromiseResponse()
    {
        $this->factory->fake(['https://example.com' => $this->factory->response()]);
        $effectiveUri = $this->factory->get('https://example.com')->effectiveUri();

        $this->assertSame('https://example.com', (string) $effectiveUri);
    }

    public function testClonedClientsWorkSuccessfullyWithTheRequestObject()
    {
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(m::type(RequestSending::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(ResponseReceived::class));

        $factory = new Factory($events);
        $factory->fake(['example.com' => $factory->response('foo', 200)]);

        $client = $factory->timeout(10);
        $clonedClient = clone $client;

        $clonedClient->get('https://example.com');
    }

    public function testRequestIsMacroable()
    {
        Request::macro('customMethod', function () {
            return 'yes!';
        });

        $this->factory->fake(function (Request $request) {
            $this->assertSame('yes!', $request->customMethod());

            return $this->factory->response();
        });

        $this->factory->get('https://example.com');
    }

    public function testRequestExceptionIsThrownWhenRetriesExhausted()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $exception = null;

        try {
            $this->factory
                ->retry(2, 1000, null, true)
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $this->factory->assertSentCount(2);
    }

    public function testRequestExceptionIsThrownWhenRetriesExhaustedWithBackoffArray()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $exception = null;

        try {
            $this->factory
                ->retry([1], 0, null, true)
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $this->factory->assertSentCount(2);
    }

    public function testRequestExceptionIsThrownWithoutRetriesIfRetryNotNecessary()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $exception = null;
        $whenAttempts = 0;

        try {
            $this->factory
                ->retry(2, 1000, function ($exception) use (&$whenAttempts) {
                    $whenAttempts++;

                    return $exception->response->status() === 403;
                }, true)
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $this->assertSame(1, $whenAttempts);

        $this->factory->assertSentCount(1);
    }

    public function testRequestExceptionIsThrownWithoutRetriesIfRetryNotNecessaryWithBackoffArray()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $exception = null;
        $whenAttempts = 0;

        try {
            $this->factory
                ->retry([1000, 1000], 1000, function ($exception) use (&$whenAttempts) {
                    $whenAttempts++;

                    return $exception->response->status() === 403;
                }, true)
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $this->assertSame(1, $whenAttempts);

        $this->factory->assertSentCount(1);
    }

    public function testRequestExceptionIsNotThrownWhenDisabledAndRetriesExhausted()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $response = $this->factory
            ->retry(2, 1000, null, false)
            ->get('http://foo.com/get');

        $this->assertTrue($response->failed());

        $this->factory->assertSentCount(2);
    }

    public function testRequestExceptionIsNotThrownWhenDisabledAndRetriesExhaustedWithBackoffArray()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $response = $this->factory
            ->retry([1, 2], throw: false)
            ->get('http://foo.com/get');

        $this->assertTrue($response->failed());

        $this->factory->assertSentCount(3);
    }

    public function testRequestExceptionIsNotThrownWithoutRetriesIfRetryNotNecessary()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $whenAttempts = 0;

        $response = $this->factory
            ->retry(2, 1000, function ($exception) use (&$whenAttempts) {
                $whenAttempts++;

                return $exception->response->status() === 403;
            }, false)
            ->get('http://foo.com/get');

        $this->assertTrue($response->failed());

        $this->assertSame(1, $whenAttempts);

        $this->factory->assertSentCount(1);
    }

    public function testRequestExceptionIsNotThrownWithoutRetriesIfRetryNotNecessaryWithBackoffArray()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $whenAttempts = 0;

        $response = $this->factory
            ->retry([1, 2], 0, function ($exception) use (&$whenAttempts) {
                $whenAttempts++;

                return $exception->response->status() === 403;
            }, false)
            ->get('http://foo.com/get');

        $this->assertTrue($response->failed());

        $this->assertSame(1, $whenAttempts);

        $this->factory->assertSentCount(1);
    }

    public function testRequestCanBeModifiedInRetryCallback()
    {
        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->push(['error'], 500)
                ->push(['ok'], 200),
        ]);

        $response = $this->factory
            ->retry(2, 1000, function ($exception, $request) {
                $this->assertInstanceOf(PendingRequest::class, $request);

                $request->withHeaders(['Foo' => 'Bar']);

                return true;
            }, false)
            ->get('http://foo.com/get');

        $this->assertTrue($response->successful());

        $this->factory->assertSent(function (Request $request) {
            return $request->hasHeader('Foo') && $request->header('Foo') === ['Bar'];
        });
    }

    public function testRequestCanBeModifiedInRetryCallbackWithBackoffArray()
    {
        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->push(['error'], 500)
                ->push(['ok'], 200),
        ]);

        $response = $this->factory
            ->retry([2], when: function ($exception, $request) {
                $this->assertInstanceOf(PendingRequest::class, $request);

                $request->withHeaders(['Foo' => 'Bar']);

                return true;
            }, throw: false)
            ->get('http://foo.com/get');

        $this->assertTrue($response->successful());

        $this->factory->assertSent(function (Request $request) {
            return $request->hasHeader('Foo') && $request->header('Foo') === ['Bar'];
        });
    }

    public function testExceptionThrownInRetryCallbackWithoutRetrying()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $exception = null;

        try {
            $this->factory
                ->retry(2, 1000, function ($exception) use (&$whenAttempts) {
                    throw new Exception('Foo bar');
                }, false)
                ->get('http://foo.com/get');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals('Foo bar', $exception->getMessage());

        $this->factory->assertSentCount(1);
    }

    public function testExceptionThrownInRetryCallbackWithoutRetryingWithBackoffArray()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $exception = null;

        try {
            $this->factory
                ->retry([1, 2, 3], when: function ($exception) use (&$whenAttempts) {
                    throw new Exception('Foo bar');
                }, throw: false)
                ->get('http://foo.com/get');
        } catch (Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals('Foo bar', $exception->getMessage());

        $this->factory->assertSentCount(1);
    }

    public function testRequestsWillBeWaitingSleepMillisecondsReceivedBeforeRetry()
    {
        Sleep::fake();

        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->push(['error'], 500)
                ->push(['error'], 500)
                ->push(['ok'], 200),
        ]);

        $this->factory
            ->retry(3, function ($attempt, $exception) {
                $this->assertInstanceOf(RequestException::class, $exception);

                return $attempt * 100;
            }, null, true)
            ->get('http://foo.com/get');

        $this->factory->assertSentCount(3);

        // Make sure we waited 300ms for the first two attempts
        Sleep::assertSleptTimes(2);

        Sleep::assertSequence([
            Sleep::usleep(100_000),
            Sleep::usleep(200_000),
        ]);
    }

    public function testRequestExceptionReturnedWhenRetriesExhaustedInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->retry(2, 1000, null, true)->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $this->factory->assertSentCount(2);
    }

    public function testRequestExceptionIsReturnedWithoutRetriesIfRetryNotNecessaryInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $whenAttempts = collect();

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->retry(2, 1000, function ($exception) use ($whenAttempts) {
                $whenAttempts->push($exception);

                return $exception->response->status() === 403;
            }, true)->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $this->assertCount(1, $whenAttempts);

        $this->factory->assertSentCount(1);
    }

    public function testRequestExceptionIsNotReturnedWhenDisabledAndRetriesExhaustedInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        [$response] = $this->factory->pool(fn ($pool) => [
            $pool->retry(2, 1000, null, false)->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->failed());

        $this->factory->assertSentCount(2);
    }

    public function testRequestExceptionIsNotReturnedWithoutRetriesIfRetryNotNecessaryInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        $whenAttempts = collect();

        [$response] = $this->factory->pool(fn ($pool) => [
            $pool->retry(2, 1000, function ($exception) use ($whenAttempts) {
                $whenAttempts->push($exception);

                return $exception->response->status() === 403;
            }, false)->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->failed());

        $this->assertCount(1, $whenAttempts);

        $this->factory->assertSentCount(1);
    }

    public function testRequestCanBeModifiedInRetryCallbackInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->push(['error'], 500)
                ->push(['ok'], 200),
        ]);

        [$response] = $this->factory->pool(fn ($pool) => [
            $pool->retry(2, 1000, function ($exception, $request) {
                $this->assertInstanceOf(PendingRequest::class, $request);

                $request->withHeaders(['Foo' => 'Bar']);

                return true;
            }, false)->get('http://foo.com/get'),
        ]);

        $this->assertTrue($response->successful());

        $this->factory->assertSent(function (Request $request) {
            return $request->hasHeader('Foo') && $request->header('Foo') === ['Bar'];
        });
    }

    public function testHandleRequestExeptionWithNoResponseInPoolConsideredConnectionException()
    {
        $requestException = new \GuzzleHttp\Exception\RequestException('Error', new \GuzzleHttp\Psr7\Request('GET', '/'));
        $this->factory->fake([
            'noresponse.com' => new RejectedPromise($requestException),
        ]);

        $responses = $this->factory->pool(function (Pool $pool) {
            return [
                $pool->get('noresponse.com'),
            ];
        });

        self::assertInstanceOf(ConnectionException::class, $responses[0]);
        self::assertSame($requestException, $responses[0]->getPrevious());
    }

    public function testExceptionThrownInRetryCallbackIsReturnedWithoutRetryingInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 500),
        ]);

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->retry(2, 1000, function ($exception) {
                throw new Exception('Foo bar');
            }, false)->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals('Foo bar', $exception->getMessage());

        $this->factory->assertSentCount(1);
    }

    public function testRequestsWillBeWaitingSleepMillisecondsReceivedInBackoffArray()
    {
        Sleep::fake();

        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->push(['error'], 500)
                ->push(['error'], 500)
                ->push(['error'], 500)
                ->push(['ok'], 200),
        ]);

        $this->factory
            ->retry([50, 100, 200], 0, null, true)
            ->get('http://foo.com/get');

        $this->factory->assertSentCount(4);

        // Make sure we waited 300ms for the first two attempts
        Sleep::assertSleptTimes(3);

        Sleep::assertSequence([
            Sleep::usleep(50_000),
            Sleep::usleep(100_000),
            Sleep::usleep(200_000),
        ]);
    }

    public function testFakeConnectionException()
    {
        $this->factory->fake($this->factory->failedConnection('Fake'));

        $exception = null;

        try {
            $this->factory->post('https://example.com');
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(ConnectionException::class, $exception);
        $this->assertSame('Fake', $exception->getMessage());

        $this->factory->assertSentCount(1);
        $this->factory->assertSent(function (Request $request, ?Response $response) {
            return $request->url() === 'https://example.com' && $response === null;
        });
    }

    public function testFakeConnectionExceptionWithinFakeClosure()
    {
        $this->factory->fake(fn () => $this->factory->failedConnection('Fake'));

        $exception = null;

        try {
            $this->factory->post('https://example.com');
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(ConnectionException::class, $exception);
        $this->assertSame('Fake', $exception->getMessage());

        $this->factory->assertSentCount(1);
    }

    public function testFakeConnectionExceptionWithinArray()
    {
        $this->factory->fake(['*' => $this->factory->failedConnection('Fake')]);

        $exception = null;

        try {
            $this->factory->post('https://example.com');
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(ConnectionException::class, $exception);
        $this->assertSame('Fake', $exception->getMessage());

        $this->factory->assertSentCount(1);
    }

    public function testFakeConnectionExceptionWithinSequence()
    {
        $this->factory->fake([
            '*' => $this->factory->sequence()
                ->pushFailedConnection('Fake')
                ->push('Success'),
        ]);

        $exception = null;

        $response = $this->factory->retry(3, function ($attempt, $e) use (&$exception) {
            $exception = $e;

            return true;
        })->post('https://example.com');

        $this->assertSame('Success', $response->body());

        $this->assertNotNull($exception);
        $this->assertInstanceOf(ConnectionException::class, $exception);
        $this->assertSame('Fake', $exception->getMessage());

        $this->factory->assertSentCount(2);
    }

    public function testMiddlewareRunsWhenFaked()
    {
        $this->factory->fake(function (Request $request) {
            return $this->factory->response('Fake');
        });

        $history = [];

        $pendingRequest = $this->factory->withMiddleware(
            Middleware::history($history)
        );

        $response = $pendingRequest->post('https://example.com', ['hyped-for' => 'laravel-movie']);

        $this->assertSame('Fake', $response->body());

        $this->assertCount(1, $history);

        $this->assertSame('Fake', tap($history[0]['response']->getBody())->rewind()->getContents());

        $this->assertSame(['hyped-for' => 'laravel-movie'], json_decode(tap($history[0]['request']->getBody())->rewind()->getContents(), true));
    }

    public function testMiddlewareRunsAndCanChangeRequestOnAssertSent()
    {
        $this->factory->fake(function (Request $request) {
            return $this->factory->response('Fake');
        });

        $pendingRequest = $this->factory->withMiddleware(
            Middleware::mapRequest(fn (RequestInterface $request) => $request->withHeader('X-Test-Header', 'Test'))
        );

        $pendingRequest->post('https://laravel.example', ['laravel' => 'framework']);

        $this->factory->assertSent(function (Request $request) {
            return
                $request->url() === 'https://laravel.example' &&
                $request->hasHeader('X-Test-Header', 'Test');
        });
    }

    public function testRequestExceptionIsNotThrownIfThePendingRequestIsSetToThrowOnFailureButTheResponseIsSuccessful()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['success'], 200),
        ]);

        $response = $this->factory
            ->throw()
            ->get('http://foo.com/get');

        $this->assertSame(200, $response->status());
    }

    public function testRequestExceptionIsThrownIfThePendingRequestIsSetToThrowOnFailure()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $exception = null;

        try {
            $this->factory
                ->throw()
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsThrownIfTheThrowIfOnThePendingRequestIsSetToTrueOnFailure()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $exception = null;

        try {
            $this->factory
                ->throwIf(true)
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsNotThrownIfTheThrowIfOnThePendingRequestIsSetToFalseOnFailure()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $response = $this->factory
            ->throwIf(false)
            ->get('http://foo.com/get');

        $this->assertSame(403, $response->status());
    }

    public function testRequestExceptionIsThrownIfTheThrowIfClosureOnThePendingRequestReturnsTrue()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $exception = null;

        $hitThrowCallback = false;

        try {
            $this->factory
                ->throwIf(function ($response) {
                    $this->assertInstanceOf(Response::class, $response);
                    $this->assertSame(403, $response->status());

                    return true;
                }, function ($response, $e) use (&$hitThrowCallback) {
                    $this->assertInstanceOf(Response::class, $response);
                    $this->assertSame(403, $response->status());

                    $this->assertInstanceOf(RequestException::class, $e);
                    $hitThrowCallback = true;
                })
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
        $this->assertTrue($hitThrowCallback);
    }

    public function testRequestExceptionIsNotThrownIfTheThrowIfClosureOnThePendingRequestReturnsFalse()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $hitThrowCallback = false;

        $response = $this->factory
            ->throwIf(function ($response) {
                $this->assertInstanceOf(Response::class, $response);
                $this->assertSame(403, $response->status());

                return false;
            }, function ($response, $e) use (&$hitThrowCallback) {
                $hitThrowCallback = true;
            })
            ->get('http://foo.com/get');

        $this->assertSame(403, $response->status());
        $this->assertFalse($hitThrowCallback);
    }

    public function testRequestExceptionIsThrownWithCallbackIfThePendingRequestIsSetToThrowOnFailure()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $exception = null;

        $flag = false;

        try {
            $this->factory
                ->throw(function ($exception) use (&$flag) {
                    $flag = true;
                })
                ->get('http://foo.com/get');
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertTrue($flag);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsThrownIfTheRequestFails()
    {
        $this->factory->fake([
            '*' => $this->factory::response('', 400),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api')->throw();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsThrownWithCallbackIfTheRequestFails()
    {
        $this->factory->fake([
            '*' => $this->factory::response('', 400),
        ]);

        $exception = null;

        $flag = false;

        try {
            $this->factory->get('http://foo.com/api')->throw(function () use (&$flag) {
                $flag = true;
            });
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertTrue($flag);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsNotThrownIfTheRequestDoesNotFail()
    {
        $this->factory->fake([
            '*' => ['result' => ['foo' => 'bar']],
        ]);

        $response = $this->factory->get('http://foo.com/api')->throw();

        $this->assertSame('{"result":{"foo":"bar"}}', $response->body());
    }

    public function testRequestExceptionIsNotReturnedIfThePendingRequestIsSetToThrowOnFailureButTheResponseIsSuccessfulInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['success'], 200),
        ]);

        [$response] = $this->factory->pool(fn ($pool) => [
            $pool->throw()->get('http://foo.com/get'),
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->status());
    }

    public function testRequestExceptionIsReturnedIfThePendingRequestIsSetToThrowOnFailureInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->throw()->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsReturnedIfTheThrowIfOnThePendingRequestIsSetToTrueOnFailureInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->throwIf(true)->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsNotReturnedIfTheThrowIfOnThePendingRequestIsSetToFalseOnFailureInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        [$response] = $this->factory->pool(fn ($pool) => [
            $pool->throwIf(false)->get('http://foo.com/get'),
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(403, $response->status());
    }

    public function testRequestExceptionIsReturnedIfTheThrowIfClosureOnThePendingRequestReturnsTrueInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $hitThrowCallback = collect();

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->throwIf(function ($response) {
                $this->assertInstanceOf(Response::class, $response);
                $this->assertSame(403, $response->status());

                return true;
            }, function ($response, $e) use (&$hitThrowCallback) {
                $this->assertInstanceOf(Response::class, $response);
                $this->assertSame(403, $response->status());

                $this->assertInstanceOf(RequestException::class, $e);

                $hitThrowCallback->push(true);
            })->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
        $this->assertCount(1, $hitThrowCallback);
    }

    public function testRequestExceptionIsNotReturnedIfTheThrowIfClosureOnThePendingRequestReturnsFalseInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $hitThrowCallback = collect();

        [$response] = $this->factory->pool(fn ($pool) => [
            $pool->throwIf(function ($response) {
                $this->assertInstanceOf(Response::class, $response);
                $this->assertSame(403, $response->status());

                return false;
            }, function ($response, $e) use (&$hitThrowCallback) {
                $hitThrowCallback->push(true);
            })->get('http://foo.com/get'),
        ]);

        $this->assertCount(0, $hitThrowCallback);
        $this->assertSame(403, $response->status());
    }

    public function testRequestExceptionIsReturnedWithCallbackIfThePendingRequestIsSetToThrowOnFailureInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        $flag = collect();

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->throw(function ($exception) use (&$flag) {
                $flag->push(true);
            })->get('http://foo.com/get'),
        ]);

        $this->assertCount(1, $flag);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsReturnedAfterLastRetryInPool()
    {
        $this->factory->fake([
            '*' => $this->factory->response(['error'], 403),
        ]);

        [$exception] = $this->factory->pool(fn ($pool) => [
            $pool->retry(3)->throw()->get('http://foo.com/get'),
        ]);

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $this->factory->assertSentCount(3);
    }

    public function testRequestExceptionIsThrowIfConditionIsSatisfied()
    {
        $this->factory->fake([
            '*' => $this->factory::response('', 400),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api')->throwIf(true);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsNotThrownIfConditionIsNotSatisfied()
    {
        $this->factory->fake([
            '*' => $this->factory::response(['result' => ['foo' => 'bar']], 400),
        ]);

        $response = $this->factory->get('http://foo.com/api')->throwIf(false);

        $this->assertSame('{"result":{"foo":"bar"}}', $response->body());
    }

    public function testRequestExceptionIsThrowIfConditionClosureIsSatisfied()
    {
        $this->factory->fake([
            '*' => $this->factory::response('', 400),
        ]);

        $exception = null;

        $hitThrowCallback = false;

        try {
            $this->factory->get('http://foo.com/api')->throwIf(function ($response) {
                $this->assertSame(400, $response->status());

                return true;
            }, function ($response, $e) use (&$hitThrowCallback) {
                $this->assertSame(400, $response->status());
                $this->assertInstanceOf(RequestException::class, $e);

                $hitThrowCallback = true;
            });
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
        $this->assertTrue($hitThrowCallback);
    }

    public function testRequestExceptionIsNotThrownIfConditionClosureIsNotSatisfied()
    {
        $this->factory->fake([
            '*' => $this->factory::response(['result' => ['foo' => 'bar']], 400),
        ]);

        $hitThrowCallback = false;

        $response = $this->factory->get('http://foo.com/api')->throwIf(function ($response) {
            $this->assertSame(400, $response->status());

            return false;
        }, function ($response, $e) use (&$hitThrowCallback) {
            $hitThrowCallback = true;
        });

        $this->assertSame('{"result":{"foo":"bar"}}', $response->body());
        $this->assertFalse($hitThrowCallback);
    }

    public function testRequestExceptionIsThrownIfStatusCodeIsSatisfied()
    {
        $this->factory->fake([
            '*' => $this->factory::response('', 400),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api')->throwIfStatus(400);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsThrownIfStatusCodeIsSatisfiedWithClosure()
    {
        $this->factory->fake([
            '*' => $this->factory::response('', 400),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api')->throwIfStatus(fn ($status) => $status === 400);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testRequestExceptionIsNotThrownIfStatusCodeIsNotSatisfied()
    {
        $this->factory->fake([
            '*' => $this->factory::response('', 400),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api')->throwIfStatus(500);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function testRequestExceptionIsThrownUnlessStatusCodeIsSatisfied()
    {
        $this->factory->fake([
            'http://foo.com/api/400' => $this->factory::response('', 400),
            'http://foo.com/api/408' => $this->factory::response('', 408),
            'http://foo.com/api/500' => $this->factory::response('', 500),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/400')->throwUnlessStatus(500);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $exception = null;

        $this->factory->fake([
            'http://foo.com/api/400' => $this->factory::response('', 400),
            'http://foo.com/api/408' => $this->factory::response('', 408),
            'http://foo.com/api/500' => $this->factory::response('', 500),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/400')->throwUnlessStatus(fn ($status) => $status === 500);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/408')->throwUnlessStatus(500);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/500')->throwUnlessStatus(500);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNull($exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/500')->throwUnlessStatus(fn ($status) => $status === 500);
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function testRequestExceptionIsThrownIfIsClientError()
    {
        $this->factory->fake([
            'http://foo.com/api/400' => $this->factory::response('', 400),
            'http://foo.com/api/408' => $this->factory::response('', 408),
            'http://foo.com/api/500' => $this->factory::response('', 500),
            'http://foo.com/api/504' => $this->factory::response('', 504),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/400')->throwIfClientError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/408')->throwIfClientError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/500')->throwIfClientError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNull($exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/504')->throwIfClientError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function testRequestExceptionIsThrownIfIsServerError()
    {
        $this->factory->fake([
            'http://foo.com/api/400' => $this->factory::response('', 400),
            'http://foo.com/api/408' => $this->factory::response('', 408),
            'http://foo.com/api/500' => $this->factory::response('', 500),
            'http://foo.com/api/504' => $this->factory::response('', 504),
        ]);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/400')->throwIfServerError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNull($exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/408')->throwIfServerError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNull($exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/500')->throwIfServerError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);

        $exception = null;

        try {
            $this->factory->get('http://foo.com/api/504')->throwIfServerError();
        } catch (RequestException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(RequestException::class, $exception);
    }

    public function testItCanEnforceFaking()
    {
        $this->factory->preventStrayRequests();
        $this->factory->fake(['https://vapor.laravel.com' => Factory::response('ok', 200)]);
        $this->factory->fake(['https://forge.laravel.com' => Factory::response('ok', 200)]);

        $responses = [];
        $responses[] = $this->factory->get('https://vapor.laravel.com')->body();
        $responses[] = $this->factory->get('https://forge.laravel.com')->body();
        $this->assertSame(['ok', 'ok'], $responses);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Attempted request to [https://laravel.com] without a matching fake.');

        $this->factory->get('https://laravel.com');
    }

    public function testPreventingStrayRequests()
    {
        $this->assertFalse($this->factory->preventingStrayRequests());

        $this->factory->preventStrayRequests();

        $this->assertTrue($this->factory->preventingStrayRequests());
    }

    public function testItCanAddAuthorizationHeaderIntoRequestUsingBeforeSendingCallback()
    {
        $this->factory->fake();

        $this->factory->beforeSending(function (Request $request) {
            $requestLine = sprintf(
                '%s %s HTTP/%s',
                $request->toPsrRequest()->getMethod(),
                $request->toPsrRequest()->getUri()->withScheme('')->withHost(''),
                $request->toPsrRequest()->getProtocolVersion()
            );

            return $request->toPsrRequest()->withHeader('Authorization', 'Bearer '.$requestLine);
        })->get('http://foo.com/json');

        $this->factory->assertSent(function (Request $request) {
            return
                $request->url() === 'http://foo.com/json' &&
                $request->hasHeader('Authorization', 'Bearer GET /json HTTP/1.1');
        });
    }

    public function testItCanSetAllowMaxRedirects(): void
    {
        $request = new PendingRequest($this->factory);

        $request = $request->withOptions(['allow_redirects' => ['max' => 5]]);

        $this->assertSame(['connect_timeout' => 10, 'crypto_method' => 33, 'http_errors' => false, 'timeout' => 30, 'allow_redirects' => ['max' => 5]], $request->getOptions());

        $request = $request->maxRedirects(10);

        $this->assertSame(['connect_timeout' => 10, 'crypto_method' => 33, 'http_errors' => false, 'timeout' => 30, 'allow_redirects' => ['max' => 10]], $request->getOptions());
    }

    public function testPreventDuplicatedContentType(): void
    {
        $client = $this->factory->asJson();

        $this->assertSame('application/json', Arr::get($client->getOptions(), 'headers.Content-Type'));

        $client->asJson();
        $client->asJson();

        $this->assertSame('application/json', Arr::get($client->getOptions(), 'headers.Content-Type'));

        $client->contentType('foo');

        $this->assertSame('foo', Arr::get($client->getOptions(), 'headers.Content-Type'));
    }

    public function testItCanSubstituteUrlParams(): void
    {
        $this->factory->fake();

        $this->factory->withUrlParameters([
            'endpoint' => 'https://laravel.com',
            'page' => 'docs',
            'version' => '9.x',
            'thing' => 'validation',
        ])->get('{+endpoint}/{page}/{version}/{thing}');

        $this->factory->assertSent(function (Request $request) {
            return $request->url() === 'https://laravel.com/docs/9.x/validation';
        });
    }

    public function testTheTransferStatsAreCustomizable(): void
    {
        $onStatsFunctionCalled = false;

        $stats = $this->factory
            ->withOptions([
                'on_stats' => function (TransferStats $stats) use (&$onStatsFunctionCalled) {
                    $onStatsFunctionCalled = true;
                },
            ])
            ->get('https://example.com')
            ->handlerStats();

        $this->assertIsArray($stats);
        $this->assertNotEmpty($stats);
        $this->assertTrue($onStatsFunctionCalled);
    }

    public function testTheTransferStatsAreCustomizableOnFake(): void
    {
        $onStatsFunctionCalled = false;

        $this->factory
            ->fake()
            ->withOptions([
                'on_stats' => function (TransferStats $stats) use (&$onStatsFunctionCalled) {
                    $onStatsFunctionCalled = true;
                },
            ])
            ->get('https://foo.bar')
            ->handlerStats();

        $this->assertTrue($onStatsFunctionCalled);
    }

    public function testItCanAddGlobalMiddleware()
    {
        Carbon::setTestNow(now()->startOfDay());
        $requests = [];
        $responses = [];
        $this->factory->fake(function ($r) use (&$requests) {
            $requests[] = $r;

            Carbon::setTestNow(now()->addSeconds(6 * count($requests)));

            return $this->factory::response('expected content');
        });

        $this->factory->globalMiddleware(Middleware::mapRequest(function ($request) {
            // Test manipulating headers on outgoing request...
            return $request->withHeader('User-Agent', 'Laravel Framework/1.0')
                ->withAddedHeader('shared', 'global')
                ->withHeader('list', ['item-1', 'item-2'])
                ->withAddedHeader('list', ['item-3']);
        }))->globalMiddleware(Middleware::mapResponse(function ($response) use (&$requests) {
            // Test adding headers in incoming response..
            return $response->withHeader('X-Count', (string) count($requests));
        }))->globalMiddleware(function ($handler) {
            // Test wrapping request in timing function...
            return function ($request, $options) use ($handler) {
                $startedAt = now();

                return $handler($request, $options)->then(function (ResponseInterface $response) use ($startedAt) {
                    return $response->withHeader('X-Duration', "{$startedAt->diffInSeconds(now())} seconds");
                });
            };
        });
        $responses[] = $this->factory->post('http://forge.laravel.com');
        $responses[] = $this->factory->withHeader('shared', 'local')->post('http://vapor.laravel.com');

        $this->assertCount(2, $requests);
        $this->assertCount(2, $responses);

        $this->assertSame(['Laravel Framework/1.0'], $requests[0]->header('User-Agent'));
        $this->assertSame(['item-1', 'item-2', 'item-3'], $requests[0]->header('list'));
        $this->assertSame(['global'], $requests[0]->header('shared'));
        $this->assertSame('1', $responses[0]->header('X-Count'));
        $this->assertSame('6 seconds', $responses[0]->header('X-Duration'));

        $this->assertSame(['Laravel Framework/1.0'], $requests[1]->header('User-Agent'));
        $this->assertSame(['item-1', 'item-2', 'item-3'], $requests[1]->header('list'));
        $this->assertSame(['local', 'global'], $requests[1]->header('shared'));
        $this->assertSame('2', $responses[1]->header('X-Count'));
        $this->assertSame('12 seconds', $responses[1]->header('X-Duration'));
    }

    public function testItCanAddGlobalRequestMiddleware()
    {
        $requests = [];
        $this->factory->fake(function ($r) use (&$requests) {
            $requests[] = $r;

            return Factory::response('expected content');
        });

        $this->factory->globalRequestMiddleware(function ($request) {
            return $request->withHeader('User-Agent', 'Laravel Framework/1.0');
        });
        $this->factory->post('http://forge.laravel.com');
        $this->factory->post('http://laravel.com');

        $this->assertSame(['Laravel Framework/1.0'], $requests[0]->header('User-Agent'));
        $this->assertSame(['Laravel Framework/1.0'], $requests[1]->header('User-Agent'));
    }

    public function testItCanAddGlobalResponseMiddleware()
    {
        $responses = [];
        $this->factory->fake(function ($r) use (&$request) {
            return Factory::response('expected content');
        });

        $this->factory->globalResponseMiddleware(function ($response) {
            return $response->withHeader('X-Foo', 'Bar');
        });
        $responses[] = $this->factory->post('http://forge.laravel.com');
        $responses[] = $this->factory->post('http://laravel.com');

        $this->assertSame('Bar', $responses[0]->header('X-Foo'));
        $this->assertSame('Bar', $responses[1]->header('X-Foo'));
    }

    public function testItCanGetTheGlobalMiddleware()
    {
        $this->factory->globalMiddleware($middleware = fn () => null);

        $this->assertEquals([$middleware], $this->factory->getGlobalMiddleware());
    }

    public function testItCanAddRequestMiddleware()
    {
        $requests = [];
        $this->factory->fake(function ($r) use (&$requests) {
            $requests[] = $r;

            return Factory::response('expected content');
        });

        $this->factory->withRequestMiddleware(function ($request) {
            return $request->withHeader('User-Agent', 'Laravel Framework/1.0');
        })->post('http://forge.laravel.com');
        $this->factory->post('http://laravel.com');

        $this->assertSame(['Laravel Framework/1.0'], $requests[0]->header('User-Agent'));
        $this->assertSame(['GuzzleHttp/7'], $requests[1]->header('User-Agent'));
    }

    public function testItCanAddResponseMiddleware()
    {
        $responses = [];
        $this->factory->fake(function ($r) use (&$request) {
            return Factory::response('expected content');
        });

        $responses[] = $this->factory->withResponseMiddleware(function ($response) {
            return $response->withHeader('X-Foo', 'Bar');
        })->post('http://forge.laravel.com');
        $responses[] = $this->factory->post('http://laravel.com');

        $this->assertSame('Bar', $responses[0]->header('X-Foo'));
        $this->assertSame('', $responses[1]->header('X-Foo'));
    }

    public function testItReturnsResponse(): void
    {
        $this->factory->fake([
            '*' => $this->factory::response('expected content'),
        ]);

        $response = $this->factory->get('http://laravel.com');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('expected content', $response->body());
    }

    public function testItCanReturnCustomResponseClass(): void
    {
        $factory = new CustomFactory;

        $factory->fake([
            '*' => $factory::response('expected content'),
        ]);

        $response = $factory->get('http://laravel.fake');

        $this->assertInstanceOf(TestResponse::class, $response);
        $this->assertSame('expected content', $response->body());
    }

    public function testItCanHaveGlobalDefaultValues()
    {
        $factory = new Factory;
        $timeout = null;
        $allowRedirects = null;
        $headers = null;
        $factory->fake(function ($request, $options) use (&$timeout, &$allowRedirects, &$headers, $factory) {
            $timeout = $options['timeout'];
            $allowRedirects = $options['allow_redirects'];
            $headers = $request->headers();

            return $factory->response('');
        });

        $factory->get('https://laravel.com');
        $this->assertSame(30, $timeout);
        $this->assertSame(['max' => 5, 'protocols' => ['http', 'https'], 'strict' => false, 'referer' => false, 'track_redirects' => false], $allowRedirects);
        $this->assertNull($headers['X-Foo'] ?? null);

        $factory->globalOptions([
            'timeout' => 5,
            'allow_redirects' => false,
            'headers' => [
                'X-Foo' => 'true',
            ],
        ]);

        $factory->get('https://laravel.com');
        $this->assertSame(5, $timeout);
        $this->assertFalse($allowRedirects);
        $this->assertSame(['true'], $headers['X-Foo']);

        $factory->globalOptions(fn () => [
            'timeout' => 10,
            'headers' => [
                'X-Foo' => 'false',
                'X-Bar' => 'true',
            ],
        ]);

        $factory->get('https://laravel.com');
        $this->assertSame(10, $timeout);
        $this->assertSame(['max' => 5, 'protocols' => ['http', 'https'], 'strict' => false, 'referer' => false, 'track_redirects' => false], $allowRedirects);
        $this->assertSame(['false'], $headers['X-Foo']);
        $this->assertSame(['true'], $headers['X-Bar']);
    }

    public function testItCanCreatePendingRequest()
    {
        $factory = new Factory();

        $this->assertInstanceOf(PendingRequest::class, $factory->createPendingRequest());
    }
}

class CustomFactory extends Factory
{
    protected function newPendingRequest()
    {
        return new class extends PendingRequest
        {
            protected function newResponse($response)
            {
                return new TestResponse($response);
            }
        };
    }
}

class TestResponse extends Response
{
}
