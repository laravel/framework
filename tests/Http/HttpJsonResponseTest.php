<?php

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class HttpJsonResponseTest extends PHPUnit_Framework_TestCase
{
    public function testSeAndRetrieveJsonableData()
    {
        $response = new Illuminate\Http\JsonResponse(new JsonResponseTestJsonableObject);
        $data = $response->getData();
        $this->assertInstanceOf('StdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSeAndRetrieveJsonSerializeData()
    {
        $response = new Illuminate\Http\JsonResponse(new JsonResponseTestJsonSerializeObject);
        $data = $response->getData();
        $this->assertInstanceOf('StdClass', $data);
        $this->assertEquals('bar', $data->foo);
    }

    public function testSeAndRetrieveArrayableData()
    {
        $response = new Illuminate\Http\JsonResponse(new JsonResponseTestArrayableObject);
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

    public function testSetAndRetrieveDefaultOptions()
    {
        $response = new Illuminate\Http\JsonResponse(['foo' => 'bar']);
        $this->assertSame(0, $response->getJsonOptions());
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
