<?php

namespace Illuminate\Tests\Http;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use JsonSerializable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class HttpJsonResponseTest extends TestCase
{
    #[DataProvider('setAndRetrieveDataProvider')]
    public function testSetAndRetrieveData($data)
    {
        $response = new JsonResponse($data);

        $this->assertInstanceOf(stdClass::class, $response->getData());
        $this->assertSame('bar', $response->getData()->foo);
    }

    public static function setAndRetrieveDataProvider()
    {
        return [
            'Jsonable data' => [new JsonResponseTestJsonableObject],
            'JsonSerializable data' => [new JsonResponseTestJsonSerializeObject],
            'Arrayable data' => [new JsonResponseTestArrayableObject],
            'Array data' => [['foo' => 'bar']],
            'stdClass data' => [(object) ['foo' => 'bar']],
        ];
    }

    public function testGetOriginalContent()
    {
        $response = new JsonResponse(new JsonResponseTestArrayableObject);
        $this->assertInstanceOf(JsonResponseTestArrayableObject::class, $response->getOriginalContent());

        $response = new JsonResponse;
        $response->setData(new JsonResponseTestArrayableObject);
        $this->assertInstanceOf(JsonResponseTestArrayableObject::class, $response->getOriginalContent());
    }

    public function testSetAndRetrieveOptions()
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $response->setEncodingOptions(JSON_PRETTY_PRINT);
        $this->assertSame(JSON_PRETTY_PRINT, $response->getEncodingOptions());
    }

    public function testSetAndRetrieveDefaultOptions()
    {
        $response = new JsonResponse(['foo' => 'bar']);
        $this->assertSame(0, $response->getEncodingOptions());
    }

    public function testSetAndRetrieveStatusCode()
    {
        $response = new JsonResponse(['foo' => 'bar'], 404);
        $this->assertSame(404, $response->getStatusCode());

        $response = new JsonResponse(['foo' => 'bar']);
        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }

    #[DataProvider('jsonErrorDataProvider')]
    public function testInvalidArgumentExceptionOnJsonError($data)
    {
        $this->expectException(InvalidArgumentException::class);

        new JsonResponse(['data' => $data]);
    }

    #[DataProvider('jsonErrorDataProvider')]
    public function testGracefullyHandledSomeJsonErrorsWithPartialOutputOnError($data)
    {
        new JsonResponse(['data' => $data], 200, [], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public static function jsonErrorDataProvider()
    {
        // Resources can't be encoded
        $resource = tmpfile();

        // Recursion can't be encoded
        $recursiveObject = new stdClass;
        $objectB = new stdClass;
        $recursiveObject->b = $objectB;
        $objectB->a = $recursiveObject;

        // NAN or INF can't be encoded
        $nan = NAN;

        return [
            [$resource],
            [$recursiveObject],
            [$nan],
        ];
    }

    public function testFromJsonString()
    {
        $json_string = '{"foo":"bar"}';
        $response = JsonResponse::fromJsonString($json_string);

        $this->assertSame('bar', $response->getData()->foo);
    }
}

class JsonResponseTestJsonableObject implements Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }
}

class JsonResponseTestJsonSerializeObject implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return ['foo' => 'bar'];
    }
}

class JsonResponseTestArrayableObject implements Arrayable
{
    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}
