<?php

namespace Illuminate\Tests\Http;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\HttpClientCurlBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HttpClientCurlBuilderTest extends TestCase
{
    #[DataProvider('methodsReceivingArrayableDataProvider')]
    public function testItCanGeneratesCurlWithArrayableRequestMethods(string $method)
    {
        $request = new GuzzleRequest($method, 'https://laravel.com');
        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("curl ", $curl);
        $this->assertStringContainsString("--request '$method' ", $curl);
        $this->assertStringContainsString("--url 'https://laravel.com'", $curl);
    }

    public function testItCanGeneratesCurlForPostWithBody()
    {
        $data = json_encode(['title' => 'Hello', 'body' => 'World']);
        $request = new GuzzleRequest(
            'POST',
            'https://laravel.com',
            [
                'Content-Type' => 'application/json',
            ],
            $data
        );

        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("curl ", $curl);
        $this->assertStringContainsString("--request 'POST' ", $curl);
        $this->assertStringContainsString("--url 'https://laravel.com' ", $curl);
        $this->assertStringContainsString("--header 'Content-Type: application/json' ", $curl);
        $this->assertStringContainsString("--data '$data'", $curl);
    }

    public function testItCanGeneratesCurlForPostWithoutBody()
    {
        $request = new GuzzleRequest('POST', 'https://laravel.com');
        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("curl ", $curl);
        $this->assertStringContainsString("--request 'POST' ", $curl);
        $this->assertStringContainsString("--url 'https://laravel.com'", $curl);
        $this->assertStringNotContainsString("--data", $curl);
    }

    public function testItCanGeneratesCurlForGetWithQueryParameters()
    {
        $request = new GuzzleRequest('GET', 'https://laravel.com?q=taylor&page=2');
        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("--url 'https://laravel.com?q=taylor&page=2", $curl);
    }

    public function testItCanGeneratesCurlForGetWithBody()
    {
        $data = json_encode(['title' => 'Hello', 'body' => 'World']);
        $request = new GuzzleRequest('GET', 'https://laravel.com', body: $data);

        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("curl ", $curl);
        $this->assertStringContainsString("--request 'GET' ", $curl);
        $this->assertStringContainsString("--url 'https://laravel.com' ", $curl);
        $this->assertStringContainsString("--data '$data'", $curl);
    }

    public function testItCanGeneratesCurlAsFormData(): void
    {
        $body = http_build_query(['name' => 'Taylor', 'title' => 'Laravel Developer']);
        $request = new GuzzleRequest(
            'POST',
            'https://laravel.com/form-data',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $body
        );

        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("--header 'Content-Type: application/x-www-form-urlencoded'", $curl);
        $this->assertStringContainsString("--data 'name=Taylor&title=Laravel+Developer'", $curl);
    }

    public function testItCanGeneratesCurlAsMultipartFormData()
    {
        $request = new GuzzleRequest(
            'POST',
            'https://example.com/multipart',
            ['Content-Type' => 'multipart/form-data', 'X-Test-Header' => 'foo'],
            json_encode(['first_name'=>'Taylor','last_name'=> 'Otwell'])
        );

        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("--request 'POST' ", $curl);
        $this->assertStringContainsString("--header 'content-type: multipart/form-data' ", $curl);
        $this->assertStringContainsString("--header 'X-Test-Header: foo' ", $curl);
        $this->assertStringContainsString("--form 'first_name=Taylor' ", $curl);
        $this->assertStringContainsString("--form 'last_name=Otwell'", $curl);
    }

    public function testItCanGeneratesCurlForMultipartStream()
    {
        $multipart = new MultipartStream([
            [
                'name' => 'first_name',
                'contents' => 'taylor',
            ],
            [
                'name' => 'attachment',
                'contents' => Utils::tryFopen(__DIR__ . '/fixtures/test.txt', 'r'),
                'filename' => 'test.txt',
            ]
        ]);

        $request = new GuzzleRequest(
            'POST',
            'https://example.com/upload',
            ['Content-Type' => 'multipart/form-data; boundary=' . $multipart->getBoundary()],
            $multipart
        );

        $curl = HttpClientCurlBuilder::forRequest($request)->build();

        $this->assertStringContainsString("--request 'POST' ", $curl);
        $this->assertStringContainsString("--header 'content-type: multipart/form-data' ", $curl);
        $this->assertStringContainsString("--form 'first_name=taylor' ", $curl);
        $this->assertStringContainsString("--form 'attachment=@test.txt'", $curl);
    }

    public function testItCanGeneratesPrettyCurl()
    {
        $data = json_encode(['title' => 'Hello', 'body' => 'World']);

        $request = new GuzzleRequest(
            'POST',
            'https://laravel.com/pretty-output',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer token',
                'Accept-Language' => 'en-US,en;q=0.9,fa;q=0.8',
                'User-Agent' => 'Laravel'
            ],
            $data
        );

        $curl = HttpClientCurlBuilder::forRequest($request)->pretty()->build();

        $this->assertStringContainsString("curl \\\n", $curl);
        $this->assertStringContainsString("--request 'POST' \\\n", $curl);
        $this->assertStringContainsString("--url 'https://laravel.com/pretty-output' \\\n", $curl);
        $this->assertStringContainsString("--header 'Content-Type: application/json' \\\n", $curl);
        $this->assertStringContainsString("--header 'Accept: application/json' \\\n", $curl);
        $this->assertStringContainsString("--header 'Authorization: Bearer token' \\\n", $curl);
        $this->assertStringContainsString("--header 'Accept-Language: en-US,en;q=0.9,fa;q=0.8' \\\n", $curl);
        $this->assertStringContainsString("--header 'User-Agent: Laravel' \\\n", $curl);
        $this->assertStringContainsString("--data '$data'", $curl);
    }

    public function testItCanCreateCurlBuilder()
    {
        $this->assertInstanceOf(
            HttpClientCurlBuilder::class,
            HttpClientCurlBuilder::forRequest(new GuzzleRequest('GET', 'https://laravel.com'))
        );
    }

    public static function methodsReceivingArrayableDataProvider()
    {
        return [
            'patch' => ['PATCH'],
            'put' => ['PUT'],
            'post' => ['POST'],
            'get' => ['GET'],
            'delete' => ['DELETE'],
        ];
    }
}
