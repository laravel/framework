<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RoutingResponseFactoryTest extends TestCase
{
    protected ResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ResponseFactory(
            m::mock(ViewFactory::class),
            m::mock(Redirector::class)
        );
    }

    public function testMakeResponse()
    {
        $response = $this->factory->make('hello', 200, ['X-Foo' => 'bar']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('hello', $response->getContent());
        $this->assertSame('bar', $response->headers->get('X-Foo'));
    }

    public function testCreatedResponse()
    {
        $response = $this->factory->created();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testCreatedResponseWithContentAndHeaders()
    {
        $response = $this->factory->created('{"id":1}', ['X-Foo' => 'bar']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertSame('{"id":1}', $response->getContent());
        $this->assertSame('bar', $response->headers->get('X-Foo'));
    }

    public function testNoContentResponse()
    {
        $response = $this->factory->noContent();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testNoContentResponseWithHeaders()
    {
        $response = $this->factory->noContent(204, ['X-Foo' => 'bar']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame('bar', $response->headers->get('X-Foo'));
    }
}
