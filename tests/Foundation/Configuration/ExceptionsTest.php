<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionsTest extends TestCase
{
    public function testStopIgnoring()
    {
        $container = new Container;
        $exceptions = new Exceptions($handler = new class($container) extends Handler
        {
            public function getDontReport(): array
            {
                return array_merge($this->dontReport, $this->internalDontReport);
            }
        });

        $this->assertContains(HttpException::class, $handler->getDontReport());
        $exceptions = $exceptions->stopIgnoring(HttpException::class);
        $this->assertInstanceOf(Exceptions::class, $exceptions);
        $this->assertNotContains(HttpException::class, $handler->getDontReport());

        $this->assertContains(ModelNotFoundException::class, $handler->getDontReport());
        $exceptions->stopIgnoring([ModelNotFoundException::class]);
        $this->assertNotContains(ModelNotFoundException::class, $handler->getDontReport());
    }

    public function testShouldRenderJsonWhen()
    {
        $exceptions = new Exceptions(new Handler(new Container));

        $shouldReturnJson = (fn () => $this->shouldReturnJson(new Request, new Exception()))->call($exceptions->handler);
        $this->assertFalse($shouldReturnJson);

        $exceptions->shouldRenderJsonWhen(fn () => true);
        $shouldReturnJson = (fn () => $this->shouldReturnJson(new Request, new Exception()))->call($exceptions->handler);
        $this->assertTrue($shouldReturnJson);

        $exceptions->shouldRenderJsonWhen(fn () => false);
        $shouldReturnJson = (fn () => $this->shouldReturnJson(new Request, new Exception()))->call($exceptions->handler);
        $this->assertFalse($shouldReturnJson);
    }

    public function testHideValidationErrors()
    {
        $container = Container::setInstance(new Container);
        $config = new \Illuminate\Config\Repository;
        $container->instance('config', $config);
        $container->instance(ViewFactory::class, $viewFactory = m::mock(ViewFactory::class));
        $container->instance(ResponseFactoryContract::class, new ResponseFactory(
            $viewFactory,
            m::mock(Redirector::class)
        ));

        $handler = new Handler($container);

        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, ['name' => ''], ['name' => 'required']);
        $validator->fails();
        $validationException = new ValidationException($validator);

        // By default, validation errors are included.
        $response = (fn () => $this->invalidJson(new Request, $validationException))->call($handler);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('name', $data['errors']);

        // When HIDE_VALIDATION_ERRORS is enabled, errors should be omitted.
        $config->set('app.hide_validation_errors', true);
        $response = (fn () => $this->invalidJson(new Request, $validationException))->call($handler);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('errors', $data);
        $this->assertEquals('The given data was invalid.', $data['message']);

        Container::setInstance(null);
    }
}
