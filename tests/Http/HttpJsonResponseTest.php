<?php

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class HttpJsonResponseTest extends PHPUnit_Framework_TestCase
{
    public function testSeAndRetrieveJsonableData()
    {
        $response = new Illuminate\Http\JsonResponse(new JsonableObject);
        $data = $response->getData();
        $this->assertInstanceOf('StdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSeAndRetrieveJsonSerializeData()
    {
        $response = new Illuminate\Http\JsonResponse(new JsonSerializeObject);
        $data = $response->getData();
        $this->assertInstanceOf('StdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSeAndRetrieveArrayableData()
    {
        $response = new Illuminate\Http\JsonResponse(new ArrayableObject);
        $data = $response->getData();
        $this->assertInstanceOf('StdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSetAndRetrieveData()
    {
        $response = new Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $data = $response->getData();
        $this->assertInstanceOf('StdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSetAndRetrieveOptions()
    {
        $response = new Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $response->setJsonOptions(JSON_PRETTY_PRINT);
        $this->assertSame(JSON_PRETTY_PRINT, $response->getJsonOptions());
    }

    public function testSetAndRetrieveStatusCode()
    {
        $response = new Illuminate\Http\JsonResponse(['foo' => 'bar'], 404);
        $this->assertSame(404, $response->getStatusCode());

        $response = new Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }
}

class JsonableObject implements Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }
}

class JsonSerializeObject implements JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'bar'];
    }
}

class ArrayableObject implements Arrayable
{
    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}
