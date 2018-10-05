<?php

namespace Illuminate\Tests\Foundation;

use stdClass;
use Exception;
use Mockery as m;
use RuntimeException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Routing\Redirector;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Exceptions\Handler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;

class FoundationExceptionsHandlerTest extends TestCase
{
    protected $config;

    protected $container;

    protected $handler;

    protected $request;

    public function setUp()
    {
        $this->config = m::mock(Config::class);

        $this->request = m::mock(stdClass::class);

        $this->container = Container::setInstance(new Container);

        $this->container->singleton('config', function () {
            return $this->config;
        });

        $this->container->singleton(ResponseFactoryContract::class, function () {
            return new ResponseFactory(
                m::mock(Factory::class),
                m::mock(Redirector::class)
            );
        });

        $this->handler = new Handler($this->container);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testHandlerReportsExceptionAsContext()
    {
        $logger = m::mock(LoggerInterface::class);
        $this->container->instance(LoggerInterface::class, $logger);
        $logger->shouldReceive('error')->withArgs(['Exception message', m::hasKey('exception')]);

        $this->handler->report(new RuntimeException('Exception message'));
    }

    public function testReturnsJsonWithStackTraceWhenAjaxRequestAndDebugTrue()
    {
        $this->config->shouldReceive('get')->with('app.debug', null)->once()->andReturn(true);
        $this->request->shouldReceive('expectsJson')->once()->andReturn(true);

        $response = $this->handler->render($this->request, new Exception('My custom error message'))->getContent();

        $this->assertNotContains('<!DOCTYPE html>', $response);
        $this->assertContains('"message": "My custom error message"', $response);
        $this->assertContains('"file":', $response);
        $this->assertContains('"line":', $response);
        $this->assertContains('"trace":', $response);
    }

    public function testReturnsCustomResponseWhenExceptionImplementsResponsable()
    {
        $response = $this->handler->render($this->request, new CustomException)->getContent();

        $this->assertSame('{"response":"My custom exception response"}', $response);
    }

    public function testReturnsJsonWithoutStackTraceWhenAjaxRequestAndDebugFalseAndExceptionMessageIsMasked()
    {
        $this->config->shouldReceive('get')->with('app.debug', null)->once()->andReturn(false);
        $this->request->shouldReceive('expectsJson')->once()->andReturn(true);

        $response = $this->handler->render($this->request, new Exception('This error message should not be visible'))->getContent();

        $this->assertContains('"message": "Server Error"', $response);
        $this->assertNotContains('<!DOCTYPE html>', $response);
        $this->assertNotContains('This error message should not be visible', $response);
        $this->assertNotContains('"file":', $response);
        $this->assertNotContains('"line":', $response);
        $this->assertNotContains('"trace":', $response);
    }

    public function testReturnsJsonWithoutStackTraceWhenAjaxRequestAndDebugFalseAndHttpExceptionErrorIsShown()
    {
        $this->config->shouldReceive('get')->with('app.debug', null)->once()->andReturn(false);
        $this->request->shouldReceive('expectsJson')->once()->andReturn(true);

        $response = $this->handler->render($this->request, new HttpException(403, 'My custom error message'))->getContent();

        $this->assertContains('"message": "My custom error message"', $response);
        $this->assertNotContains('<!DOCTYPE html>', $response);
        $this->assertNotContains('"message": "Server Error"', $response);
        $this->assertNotContains('"file":', $response);
        $this->assertNotContains('"line":', $response);
        $this->assertNotContains('"trace":', $response);
    }

    public function testReturnsJsonWithoutStackTraceWhenAjaxRequestAndDebugFalseAndAccessDeniedHttpExceptionErrorIsShown()
    {
        $this->config->shouldReceive('get')->with('app.debug', null)->once()->andReturn(false);
        $this->request->shouldReceive('expectsJson')->once()->andReturn(true);

        $response = $this->handler->render($this->request, new AccessDeniedHttpException('My custom error message'))->getContent();

        $this->assertContains('"message": "My custom error message"', $response);
        $this->assertNotContains('<!DOCTYPE html>', $response);
        $this->assertNotContains('"message": "Server Error"', $response);
        $this->assertNotContains('"file":', $response);
        $this->assertNotContains('"line":', $response);
        $this->assertNotContains('"trace":', $response);
    }
}

class CustomException extends Exception implements Responsable
{
    public function toResponse($request)
    {
        return response()->json(['response' => 'My custom exception response']);
    }
}
