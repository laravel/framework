<?php

namespace Illuminate\Tests\Foundation;

use JMac\Testing\Matching\Argument;
use JMac\Testing\Double;
use Closure;
use Exception;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\Repository;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Lottery;
use Illuminate\Support\MessageBag;
use Illuminate\Testing\Assert;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Mockery;
use OutOfRangeException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FoundationExceptionsHandlerTest extends TestCase
{
    use InteractsWithExceptionHandling;

    protected $config;

    protected $viewFactory;

    protected $container;

    protected $handler;

    protected $request;

    protected function setUp(): void
    {
        $this->config = Double::for(Config::class);

        $this->viewFactory = Double::for(ViewFactory::class);

        $this->request = Double::for(Request::class);

        $this->container = Container::setInstance(new Container);

        $this->container->instance('config', $this->config);

        $this->container->instance(ViewFactory::class, $this->viewFactory);

        $this->container->instance(ResponseFactoryContract::class, new ResponseFactory(
            $this->viewFactory,
            Double::for(Redirector::class)
        ));

        $this->handler = new Handler($this->container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    protected function getRealLogger(): LoggerSpy
    {
        $this->container->instance(LoggerInterface::class, $logger = new LoggerSpy);

        return $logger;
    }

    public function testHandlerReportsExceptionAsContext()
    {
        $logger = $this->getRealLogger();

        $this->handler->report(new RuntimeException('Exception message'));

        $this->assertSame('error', $logger->logs[0]['level']);
        $this->assertSame('Exception message', $logger->logs[0]['message']);
        $this->assertArrayHasKey('exception', $logger->logs[0]['context']);
    }

    public function testHandlerCallsContextMethodIfPresent()
    {
        $logger = $this->getRealLogger();

        $this->handler->report(new ContextProvidingException('Exception message'));

        $this->assertSame('error', $logger->logs[0]['level']);
        $this->assertSame('Exception message', $logger->logs[0]['message']);
        $this->assertSame('bar', $logger->logs[0]['context']['foo']);
    }

    public function testHandlerReportsExceptionWhenUnReportable()
    {
        $logger = $this->getRealLogger();

        $this->handler->report(new UnReportableException('Exception message'));

        $this->assertSame('error', $logger->logs[0]['level']);
        $this->assertSame('Exception message', $logger->logs[0]['message']);
        $this->assertArrayHasKey('exception', $logger->logs[0]['context']);
    }

    public function testHandlerReportsExceptionWithCustomLogLevel()
    {
        $logger = $this->getRealLogger();

        $this->handler->level(InvalidArgumentException::class, LogLevel::CRITICAL);
        $this->handler->level(OutOfRangeException::class, 'custom');

        $this->handler->report(new InvalidArgumentException('Critical message'));
        $this->handler->report(new RuntimeException('Error message'));
        $this->handler->report(new OutOfRangeException('Custom message'));

        $this->assertSame('critical', $logger->logs[0]['level']);
        $this->assertSame('Critical message', $logger->logs[0]['message']);
        $this->assertArrayHasKey('exception', $logger->logs[0]['context']);

        $this->assertSame('error', $logger->logs[1]['level']);
        $this->assertSame('Error message', $logger->logs[1]['message']);
        $this->assertArrayHasKey('exception', $logger->logs[1]['context']);

        $this->assertSame('custom', $logger->logs[2]['level']);
        $this->assertSame('Custom message', $logger->logs[2]['message']);
        $this->assertArrayHasKey('exception', $logger->logs[2]['context']);
    }

    public function testHandlerIgnoresNotReportableExceptions()
    {
        $logger = Double::for(LoggerInterface::class);
        $this->container->instance(LoggerInterface::class, $logger);
        $logger->expects('log')->never();

        $this->handler->ignore(RuntimeException::class);

        $this->handler->report(new RuntimeException('Exception message'));
    }

    public function testHandlerCallsReportMethodWithDependencies()
    {
        $reporter = Double::for(ReportingService::class);
        $this->container->instance(ReportingService::class, $reporter);
        $reporter->expects('send')->with('Exception message');

        $logger = Double::for(LoggerInterface::class);
        $this->container->instance(LoggerInterface::class, $logger);
        $logger->expects('log')->never();

        $this->handler->report(new ReportableException('Exception message'));
    }

    public function testHandlerReportsExceptionUsingCallableClass()
    {
        $reporter = Double::for(ReportingService::class);
        $reporter->expects('send')->with('Exception message');

        $logger = Double::for(LoggerInterface::class);
        $this->container->instance(LoggerInterface::class, $logger);
        $logger->expects('log')->never();

        $this->handler->reportable(new CustomReporter($reporter));

        $this->handler->report(new CustomException('Exception message'));
    }

    public function testShouldReturnJson()
    {
        $e = new Exception('My custom error message');

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $shouldReturnJson = (fn () => $this->shouldReturnJson($request, $e))->call($this->handler);
        $this->assertTrue($shouldReturnJson);

        $request = Request::create('/');

        $shouldReturnJson = (fn () => $this->shouldReturnJson($request, $e))->call($this->handler);
        $this->assertFalse($shouldReturnJson);
    }

    public function testShouldReturnJsonWhen()
    {
        $this->request->expects('expectsJson')->never();
        $exception = new Exception('My custom error message');

        $request = $this->request;

        $this->handler->shouldRenderJsonWhen(function ($r, $e) use ($request, $exception) {
            $this->assertSame($request, $r);
            $this->assertSame($exception, $e);

            return true;
        });

        $shouldReturnJson = (fn () => $this->shouldReturnJson($request, $exception))->call($this->handler);
        $this->assertTrue($shouldReturnJson);

        $this->handler->shouldRenderJsonWhen(function ($r, $e) use ($request, $exception) {
            $this->assertSame($request, $r);
            $this->assertSame($exception, $e);

            return false;
        });

        $shouldReturnJson = (fn () => $this->shouldReturnJson($request, $exception))->call($this->handler);
        $this->assertFalse($shouldReturnJson);

        $this->assertSame(6, Assert::getCount());
    }

    public function testReturnsJsonWithStackTraceWhenAjaxRequestAndDebugTrue()
    {
        $this->container->instance('config', new Config(['app' => ['debug' => true]]));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $this->handler->render($request, new Exception('My custom error message'))->getContent();

        $this->assertStringNotContainsString('<!DOCTYPE html>', $response);
        $this->assertStringContainsString('"message": "My custom error message"', $response);
        $this->assertStringContainsString('"file":', $response);
        $this->assertStringContainsString('"line":', $response);
        $this->assertStringContainsString('"trace":', $response);
    }

    public function testReturnsCustomResponseFromRenderableCallback()
    {
        $this->handler->renderable(function (CustomException $e, $request) {
            $this->assertSame($this->request, $request);

            return response()->json(['response' => 'My custom exception response']);
        });

        $response = $this->handler->render($this->request, new CustomException)->getContent();

        $this->assertSame('{"response":"My custom exception response"}', $response);
    }

    public function testReturnsCustomResponseFromCallableClass()
    {
        $this->handler->renderable(new CustomRenderer);

        $response = $this->handler->render($this->request, new CustomException)->getContent();

        $this->assertSame('{"response":"The CustomRenderer response"}', $response);
    }

    public function testReturnsResponseFromRenderableException()
    {
        $response = $this->handler->render(Request::create('/'), new RenderableException)->getContent();

        $this->assertSame('{"response":"My renderable exception response"}', $response);
    }

    public function testReturnsResponseFromMappedRenderableException()
    {
        $this->handler->map(RuntimeException::class, RenderableException::class);

        $response = $this->handler->render(Request::create('/'), new RuntimeException)->getContent();

        $this->assertSame('{"response":"My renderable exception response"}', $response);
    }

    public function testShouldntRetryDefaultsToFalse()
    {
        $this->assertFalse($this->handler->shouldStopRetries(new CustomException));
    }

    public function testShouldntRetryUsesRegisteredExceptionClass()
    {
        $this->handler->dontRetry(CustomException::class);

        $this->assertTrue($this->handler->shouldStopRetries(new CustomException));
    }

    public function testShouldntRetryUsesRegisteredCallback()
    {
        $this->handler->dontRetryWhen(function (CustomException $e) {
            return true;
        });

        $this->assertTrue($this->handler->shouldStopRetries(new CustomException));
    }

    public function testShouldntRetryIgnoresFalseCallbackResult()
    {
        $this->handler->dontRetryWhen(function (CustomException $e) {
            return false;
        });

        $this->assertFalse($this->handler->shouldStopRetries(new CustomException));
    }

    public function testReturnsCustomResponseWhenExceptionImplementsResponsable()
    {
        $response = $this->handler->render($this->request, new ResponsableException)->getContent();

        $this->assertSame('{"response":"My responsable exception response"}', $response);
    }

    public function testReturnsJsonWithoutStackTraceWhenAjaxRequestAndDebugFalseAndExceptionMessageIsMasked()
    {
        $this->container->instance('config', new Config(['app' => ['debug' => false]]));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $this->handler->render($request, new Exception('This error message should not be visible'))->getContent();

        $this->assertStringContainsString('"message": "Server Error"', $response);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $response);
        $this->assertStringNotContainsString('This error message should not be visible', $response);
        $this->assertStringNotContainsString('"file":', $response);
        $this->assertStringNotContainsString('"line":', $response);
        $this->assertStringNotContainsString('"trace":', $response);
    }

    public function testReturnsJsonWithoutStackTraceWhenAjaxRequestAndDebugFalseAndHttpExceptionErrorIsShown()
    {
        $this->container->instance('config', new Config(['app' => ['debug' => false]]));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $this->handler->render($request, new HttpException(403, 'My custom error message'))->getContent();

        $this->assertStringContainsString('"message": "My custom error message"', $response);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $response);
        $this->assertStringNotContainsString('"message": "Server Error"', $response);
        $this->assertStringNotContainsString('"file":', $response);
        $this->assertStringNotContainsString('"line":', $response);
        $this->assertStringNotContainsString('"trace":', $response);
    }

    public function testReturnsJsonWithoutStackTraceWhenAjaxRequestAndDebugFalseAndAccessDeniedHttpExceptionErrorIsShown()
    {
        $this->container->instance('config', new Config(['app' => ['debug' => false]]));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $this->handler->render($request, new AccessDeniedHttpException('My custom error message'))->getContent();

        $this->assertStringContainsString('"message": "My custom error message"', $response);
        $this->assertStringNotContainsString('<!DOCTYPE html>', $response);
        $this->assertStringNotContainsString('"message": "Server Error"', $response);
        $this->assertStringNotContainsString('"file":', $response);
        $this->assertStringNotContainsString('"line":', $response);
        $this->assertStringNotContainsString('"trace":', $response);
    }

    public function testValidateFileMethod()
    {
        $argumentExpected = ['input' => 'My input value'];
        $argumentActual = null;

        $this->container->singleton('redirect', function () use (&$argumentActual) {
            $redirector = Double::for(Redirector::class);

            $responder = Double::for(RedirectResponse::class);
            $redirector->expects('to')->returns($responder);

            $responder->expects('withInput')->with(Argument::satisfies(function ($argument) use (&$argumentActual) {
                    $argumentActual = $argument;

                    return true;
                }))->returns($responder);

            $responder->expects('withErrors')->returns($responder);

            return $redirector;
        });

        $file = new UploadedFile(__FILE__, 'photo.jpg', null, null, true);

        $request = Request::create('/', 'POST', $argumentExpected, [], ['photo' => $file]);

        $validator = Double::for(Validator::class);
        $validator->expects('errors')->times(2)->returns(new MessageBag(['error' => 'My custom validation exception']));

        $validationException = new ValidationException($validator);
        $validationException->redirectTo = '/';

        $this->handler->render($request, $validationException);

        $this->assertEquals($argumentExpected, $argumentActual);
    }

    public function testSuspiciousOperationReturns400WithoutReporting()
    {
        $this->config->expects('get')->with('app.debug', null)->returns(true);
        $this->request->expects('expectsJson')->returns(true);

        $response = $this->handler->render($this->request, new SuspiciousOperationException('Invalid method override "__CONSTRUCT"'));

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('"message": "Bad request."', $response->getContent());

        $logger = Double::for(LoggerInterface::class);
        $this->container->instance(LoggerInterface::class, $logger);
        $logger->expects('log')->never();

        $this->handler->report(new SuspiciousOperationException('Invalid method override "__CONSTRUCT"'));
    }

    public function testRecordsNotFoundReturns404WithoutReporting()
    {
        $this->config->expects('get')->with('app.debug', null)->returns(true);
        $this->request->expects('expectsJson')->returns(true);

        $response = $this->handler->render($this->request, new RecordsNotFoundException);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('"message": "Not found."', $response->getContent());

        $logger = Double::for(LoggerInterface::class);
        $this->container->instance(LoggerInterface::class, $logger);
        $logger->expects('log')->never();

        $this->handler->report(new RecordsNotFoundException);
    }

    public function testMultipleRecordsFoundIsReported()
    {
        $logger = $this->getRealLogger();

        $this->handler->report(new MultipleRecordsFoundException(2));

        $this->assertSame('error', $logger->logs[0]['level']);
        $this->assertSame('2 records were found.', $logger->logs[0]['message']);
        $this->assertArrayHasKey('exception', $logger->logs[0]['context']);
    }

    public function testItReturnsSpecificErrorViewIfExists()
    {
        $this->container->instance(ViewFactory::class, new FakeErrorViewFactory(['errors::502']));

        $handler = new class($this->container) extends Handler
        {
            public function getErrorView($e)
            {
                return $this->getHttpExceptionView($e);
            }
        };

        $this->assertSame('errors::502', $handler->getErrorView(new HttpException(502)));
    }

    public function testItReturnsFallbackErrorViewIfExists()
    {
        $this->container->instance(ViewFactory::class, new FakeErrorViewFactory(['errors::5xx']));

        $handler = new class($this->container) extends Handler
        {
            public function getErrorView($e)
            {
                return $this->getHttpExceptionView($e);
            }
        };

        $this->assertSame('errors::5xx', $handler->getErrorView(new HttpException(502)));
    }

    public function testItReturnsNullIfNoErrorViewExists()
    {
        $this->container->instance(ViewFactory::class, new FakeErrorViewFactory([]));

        $handler = new class($this->container) extends Handler
        {
            public function getErrorView($e)
            {
                return $this->getHttpExceptionView($e);
            }
        };

        $this->assertNull($handler->getErrorView(new HttpException(404)));
    }

    private function executeScenarioWhereErrorViewThrowsWhileRenderingAndDebugIs($debug)
    {
        $viewFactory = new FakeErrorViewFactory(
            ['errors::404'], new Exception('Rendering this view throws an exception')
        );

        $this->container->instance(ViewFactory::class, $viewFactory);
        $this->container->instance(ResponseFactoryContract::class, new ResponseFactory(
            $viewFactory, Double::for(Redirector::class)
        ));
        $this->container->instance(LoggerInterface::class, new LoggerSpy);

        $this->container->instance('config', new Config(['app' => ['debug' => $debug]]));

        $handler = new class($this->container) extends Handler
        {
            protected function registerErrorViewPaths()
            {
            }

            public function getErrorView($e)
            {
                return $this->renderHttpException($e);
            }
        };

        $this->assertInstanceOf(SymfonyResponse::class, $handler->getErrorView(new HttpException(404)));
    }

    public function testItDoesNotCrashIfErrorViewThrowsWhileRenderingAndDebugFalse()
    {
        // When debug is false, the exception thrown while rendering the error view
        // should not bubble as this may trigger an infinite loop.
        $this->executeScenarioWhereErrorViewThrowsWhileRenderingAndDebugIs(false);
    }

    public function testItDoesNotCrashIfErrorViewThrowsWhileRenderingAndDebugTrue()
    {
        // When debug is true, it is OK to bubble the exception thrown while rendering
        // the error view as the debug handler should handle this gracefully.

        $this->expectExceptionObject(new Exception('Rendering this view throws an exception'));
        $this->executeScenarioWhereErrorViewThrowsWhileRenderingAndDebugIs(true);
    }

    public function testAssertExceptionIsThrown()
    {
        $this->assertThrows(function () {
            throw new Exception;
        });
        $this->assertThrows(function () {
            throw new CustomException;
        });
        $this->assertThrows(function () {
            throw new CustomException;
        }, CustomException::class);
        $this->assertThrows(function () {
            throw new Exception('Some message.');
        }, expectedMessage: 'Some message.');
        $this->assertThrows(function () {
            throw new CustomException('Some message.');
        }, expectedMessage: 'Some message.');
        $this->assertThrows(function () {
            throw new CustomException('Some message.');
        }, expectedClass: CustomException::class, expectedMessage: 'Some message.');

        try {
            $this->assertThrows(function () {
                throw new Exception;
            }, CustomException::class);
            $testFailed = true;
        } catch (AssertionFailedError) {
            $testFailed = false;
        }

        if ($testFailed) {
            Assert::fail('assertThrows failed: non matching exceptions are thrown.');
        }

        try {
            $this->assertThrows(function () {
                throw new Exception('Some message.');
            }, expectedClass: Exception::class, expectedMessage: 'Other message.');
            $testFailed = true;
        } catch (AssertionFailedError) {
            $testFailed = false;
        }

        if ($testFailed) {
            Assert::fail('assertThrows failed: non matching message are thrown.');
        }

        $this->assertThrows(function () {
            throw new CustomException('Some message.');
        }, function (CustomException $exception) {
            return $exception->getMessage() === 'Some message.';
        });

        try {
            $this->assertThrows(function () {
                throw new CustomException('Some message.');
            }, function (CustomException $exception) {
                return false;
            });
            $testFailed = true;
        } catch (AssertionFailedError) {
            $testFailed = false;
        }

        if ($testFailed) {
            Assert::fail('assertThrows failed: exception callback succeeded.');
        }

        try {
            $this->assertThrows(function () {
                throw new Exception('Some message.');
            }, function (CustomException $exception) {
                return true;
            });
            $testFailed = true;
        } catch (AssertionFailedError) {
            $testFailed = false;
        }

        if ($testFailed) {
            Assert::fail('assertThrows failed: non matching exceptions are thrown.');
        }
    }

    public function testAssertNoExceptionIsThrown()
    {
        try {
            $this->assertDoesntThrow(function () {
                throw new Exception;
            });

            $testFailed = true;
        } catch (AssertionFailedError) {
            $testFailed = false;
        }

        if ($testFailed) {
            Assert::fail('assertDoesntThrow failed: thrown exception was not detected.');
        }

        try {
            $this->assertDoesntThrow(function () {
            });

            $testFailed = false;
        } catch (AssertionFailedError) {
            $testFailed = true;
        }

        if ($testFailed) {
            Assert::fail('assertDoesntThrow failed: exception was detected while no exception was thrown.');
        }
    }

    public function testItReportsDuplicateExceptions()
    {
        $reported = [];
        $this->handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        $this->handler->report($one = new RuntimeException('foo'));
        $this->handler->report($one);
        $this->handler->report($two = new RuntimeException('foo'));

        $this->assertSame($reported, [$one, $one, $two]);
    }

    public function testItCanDedupeExceptions()
    {
        $reported = [];
        $e = new RuntimeException('foo');
        $this->handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        $this->handler->dontReportDuplicates();
        $this->handler->report($one = new RuntimeException('foo'));
        $this->handler->report($one);
        $this->handler->report($two = new RuntimeException('foo'));

        $this->assertSame($reported, [$one, $two]);
    }

    public function testItCanSkipExceptionReportingUsingCallback()
    {
        $reported = [];
        $e1 = new RuntimeException('foo');
        $e2 = new RuntimeException('bar');

        $this->handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        $this->handler->dontReportWhen(function (\Throwable $e) {
            return $e->getMessage() === 'foo';
        });

        $this->handler->report($e1);
        $this->handler->report($e2);
        $this->handler->report($e1);

        $this->assertSame($reported, [$e2]);
    }

    public function testItDoesNotThrottleExceptionsByDefault()
    {
        $reported = [];
        $this->handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        for ($i = 0; $i < 100; $i++) {
            $this->handler->report(new RuntimeException("Exception {$i}"));
        }

        $this->assertCount(100, $reported);
    }

    public function testItDoesNotThrottleExceptionsWhenNullReturned()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                //
            }
        };
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        for ($i = 0; $i < 100; $i++) {
            $handler->report(new RuntimeException("Exception {$i}"));
        }

        $this->assertCount(100, $reported);
    }

    public function testItDoesNotThrottleExceptionsWhenUnlimitedLimit()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                return Limit::none();
            }
        };
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        for ($i = 0; $i < 100; $i++) {
            $handler->report(new RuntimeException("Exception {$i}"));
        }

        $this->assertCount(100, $reported);
    }

    public function testItCanSampleExceptionsByClass()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                return match (true) {
                    $e instanceof RuntimeException => Lottery::odds(2, 10),
                    default => parent::throttle($e),
                };
            }
        };
        Lottery::forceResultWithSequence([
            true, false, false, false, false,
            true, false, false, false, false,
        ]);
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        for ($i = 0; $i < 10; $i++) {
            $handler->report(new Exception("Exception {$i}"));
            $handler->report(new RuntimeException("RuntimeException {$i}"));
        }

        [$runtimeExceptions, $baseExceptions] = collect($reported)->partition(fn ($e) => $e instanceof RuntimeException);
        $this->assertCount(10, $baseExceptions);
        $this->assertCount(2, $runtimeExceptions);
    }

    public function testItRescuesExceptionsWhileThrottlingAndReports()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                throw new RuntimeException('Something went wrong in the throttle method.');
            }
        };
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });

        $handler->report(new Exception('Something in the app went wrong.'));

        $this->assertCount(1, $reported);
        $this->assertSame('Something in the app went wrong.', $reported[0]->getMessage());
    }

    public function testItRescuesExceptionsIfThereIsAnIssueResolvingTheRateLimiter()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                return Limit::perDay(1);
            }
        };
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });
        $resolved = false;
        $this->container->bind(RateLimiter::class, function () use (&$resolved) {
            $resolved = true;

            throw new Exception('Error resolving rate limiter.');
        });

        $handler->report(new Exception('Something in the app went wrong.'));

        $this->assertTrue($resolved);
        $this->assertCount(1, $reported);
        $this->assertSame('Something in the app went wrong.', $reported[0]->getMessage());
    }

    public function testItRescuesExceptionsIfThereIsAnIssueWithTheRateLimiter()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                return Limit::perDay(1);
            }
        };
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });
        $this->container->instance(RateLimiter::class, $limiter = new class(new Repository(new NullStore)) extends RateLimiter
        {
            public $attempted = false;

            public function attempt($key, $maxAttempts, Closure $callback, $decaySeconds = 60)
            {
                $this->attempted = true;

                throw new Exception('Unable to connect to Redis.');
            }
        });

        $handler->report(new Exception('Something in the app went wrong.'));

        $this->assertTrue($limiter->attempted);
        $this->assertCount(1, $reported);
        $this->assertSame('Something in the app went wrong.', $reported[0]->getMessage());
    }

    public function testItCanRateLimitExceptions()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                return Limit::perMinute(7);
            }
        };
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });
        $this->container->instance(RateLimiter::class, $limiter = new class(new Repository(new ArrayStore)) extends RateLimiter
        {
            public $attempted = 0;

            public function attempt($key, $maxAttempts, Closure $callback, $decaySeconds = 60)
            {
                $this->attempted++;

                return parent::attempt(...func_get_args());
            }
        });
        Carbon::setTestNow(Carbon::today());

        for ($i = 0; $i < 100; $i++) {
            $handler->report(new Exception('Something in the app went wrong.'));
        }

        $this->assertSame(100, $limiter->attempted);
        $this->assertCount(7, $reported);
        $this->assertSame('Something in the app went wrong.', $reported[0]->getMessage());

        Carbon::setTestNow(Carbon::now()->addMinute());

        for ($i = 0; $i < 100; $i++) {
            $handler->report(new Exception('Something in the app went wrong.'));
        }

        $this->assertSame(200, $limiter->attempted);
        $this->assertCount(14, $reported);
        $this->assertSame('Something in the app went wrong.', $reported[0]->getMessage());
    }

    public function testRateLimitExpiresOnBoundary()
    {
        $handler = new class($this->container) extends Handler
        {
            protected function throttle($e)
            {
                return Limit::perMinute(1);
            }
        };
        $reported = [];
        $handler->reportable(function (\Throwable $e) use (&$reported) {
            $reported[] = $e;

            return false;
        });
        $this->container->instance(RateLimiter::class, $limiter = new class(new Repository(new ArrayStore)) extends RateLimiter
        {
            public $attempted = 0;

            public function attempt($key, $maxAttempts, Closure $callback, $decaySeconds = 60)
            {
                $this->attempted++;

                return parent::attempt(...func_get_args());
            }
        });

        Carbon::setTestNow('2000-01-01 00:00:00.000');
        $handler->report(new Exception('Something in the app went wrong 1.'));
        Carbon::setTestNow('2000-01-01 00:00:59.999');
        $handler->report(new Exception('Something in the app went wrong 1.'));

        $this->assertSame(2, $limiter->attempted);
        $this->assertCount(1, $reported);
        $this->assertSame('Something in the app went wrong 1.', $reported[0]->getMessage());

        Carbon::setTestNow('2000-01-01 00:01:00.000');
        $handler->report(new Exception('Something in the app went wrong 2.'));
        Carbon::setTestNow('2000-01-01 00:01:59.999');
        $handler->report(new Exception('Something in the app went wrong 2.'));

        $this->assertSame(4, $limiter->attempted);
        $this->assertCount(2, $reported);
        $this->assertSame('Something in the app went wrong 2.', $reported[1]->getMessage());
    }
}

class CustomException extends Exception
{
}

class ResponsableException extends Exception implements Responsable
{
    public function toResponse($request)
    {
        return response()->json(['response' => 'My responsable exception response']);
    }
}

class ReportableException extends Exception
{
    public function report(ReportingService $reportingService)
    {
        $reportingService->send($this->getMessage());
    }
}

class UnReportableException extends Exception
{
    public function report()
    {
        return false;
    }
}

class RenderableException extends Exception
{
    public function render($request)
    {
        return response()->json(['response' => 'My renderable exception response']);
    }
}

class ContextProvidingException extends Exception
{
    public function context()
    {
        return [
            'foo' => 'bar',
        ];
    }
}

class LoggerSpy implements LoggerInterface
{
    use LoggerTrait;

    public array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}

class FakeErrorViewFactory implements ViewFactory
{
    public function __construct(protected array $views = [], protected ?Exception $makeThrows = null)
    {
    }

    public function exists($view)
    {
        return in_array($view, $this->views, true);
    }

    public function make($view, $data = [], $mergeData = [])
    {
        if ($this->makeThrows) {
            throw $this->makeThrows;
        }
    }

    public function file($path, $data = [], $mergeData = [])
    {
    }

    public function share($key, $value = null)
    {
    }

    public function composer($views, $callback)
    {
    }

    public function creator($views, $callback)
    {
    }

    public function addNamespace($namespace, $hints)
    {
    }

    public function replaceNamespace($namespace, $hints)
    {
    }
}

class CustomReporter
{
    private $service;

    public function __construct(ReportingService $service)
    {
        $this->service = $service;
    }

    public function __invoke(CustomException $e)
    {
        $this->service->send($e->getMessage());

        return false;
    }
}

class CustomRenderer
{
    public function __invoke(CustomException $e, $request)
    {
        return response()->json(['response' => 'The CustomRenderer response']);
    }
}

interface ReportingService
{
    public function send($message);
}
