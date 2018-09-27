<?php

namespace Illuminate\Tests\Http;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class HttpJsonResponseTest extends TestCase
{
    /**
     * @dataProvider setAndRetrieveDataProvider
     *
     * @param  $data
     */
    public function testSetAndRetrieveData($data): void
    {
        $response = new JsonResponse($data);

        $this->assertInstanceOf(\stdClass::class, $response->getData());
        $this->assertEquals('bar', $response->getData()->foo);
    }

    public function setAndRetrieveDataProvider(): array
    {
        return [
            'Jsonable data' => [new JsonResponseTestJsonableObject],
            'JsonSerializable data' => [new JsonResponseTestJsonSerializeObject],
            'Arrayable data' => [new JsonResponseTestArrayableObject],
            'Array data' => [['foo' => 'bar']],
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

    /**
     * @param mixed $data
     *
     * @expectedException \InvalidArgumentException
     *
     * @dataProvider jsonErrorDataProvider
     */
    public function testInvalidArgumentExceptionOnJsonError($data)
    {
        new JsonResponse(['data' => $data]);
    }

    /**
     * @param mixed $data
     *
     * @dataProvider jsonErrorDataProvider
     */
    public function testGracefullyHandledSomeJsonErrorsWithPartialOutputOnError($data)
    {
        new JsonResponse(['data' => $data], 200, [], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * @return array
     */
    public function jsonErrorDataProvider()
    {
        // Resources can't be encoded
        $resource = tmpfile();

        // Recursion can't be encoded
        $recursiveObject = new \stdClass();
        $objectB = new \stdClass();
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
    public function jsonSerialize()
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
