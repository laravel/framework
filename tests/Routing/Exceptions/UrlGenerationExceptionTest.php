<?php

namespace Illuminate\Tests\Routing\Exceptions;

use Exception;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class UrlGenerationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException()
    {
        $route = new Route('GET', 'foo/{bar}', ['as' => 'foo']);

        $exception = UrlGenerationException::forMissingParameters($route);

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testForMissingParametersWithoutParameters()
    {
        $route = new Route('GET', 'foo/{bar}', ['as' => 'foo']);

        $exception = UrlGenerationException::forMissingParameters($route);

        $this->assertSame(
            'Missing required parameters for [Route: foo] [URI: foo/{bar}].',
            $exception->getMessage()
        );
    }

    public function testForMissingParametersWithParameters()
    {
        $route = new Route('GET', 'foo/{bar}/{baz}', ['as' => 'foo']);

        $exception = UrlGenerationException::forMissingParameters($route, ['bar', 'baz']);

        $this->assertSame(
            'Missing required parameters for [Route: foo] [URI: foo/{bar}/{baz}] [Missing parameters: bar, baz].',
            $exception->getMessage()
        );
    }

    public function testForMissingParametersWithSingleParameter()
    {
        $route = new Route('GET', 'foo/{bar}', ['as' => 'foo']);

        $exception = UrlGenerationException::forMissingParameters($route, ['bar']);

        $this->assertSame(
            'Missing required parameter for [Route: foo] [URI: foo/{bar}] [Missing parameter: bar].',
            $exception->getMessage()
        );
    }
}
