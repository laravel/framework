<?php

namespace Illuminate\Tests\Log;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Log\Formatters\JsonFormatter;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use Orchestra\Testbench\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class JsonFormatterTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['logging.default' => 'testing']);
        config(['logging.channels' => [
            'testing' => [
                'driver' => 'monolog',
                'handler' => TestHandler::class,
                'formatter' => JsonFormatter::class,
            ],
        ]]);
    }

    public function testExceptionContextIsEnrichedOnDirectLogging()
    {
        Log::error('fail', ['exception' => new ContextProvidingException('Something went wrong')]);

        $formatted = $this->getFormattedJson();
        $exceptionData = $formatted['context']['exception'];

        self::assertSame('bar', $exceptionData['foo']);
        self::assertSame(ContextProvidingException::class, $exceptionData['class']);
    }

    public function testExceptionContextIsNotDuplicatedWhenGoingThroughReport()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler);
        $this->app->instance(LoggerInterface::class, $logger);

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $exception = new ContextProvidingException('Something went wrong');

        $exceptionHandler->report($exception);

        $formatted = $this->getFormattedJson($handler);

        // Context should be at the top level (from the handler)
        self::assertSame('bar', $formatted['context']['foo']);

        // But NOT enriched inside the normalized exception (formatter should skip)
        $exceptionData = $formatted['context']['exception'];
        self::assertArrayNotHasKey('foo', $exceptionData);
    }

    public function testStackDriverEnrichesBothHandlersOnDirectLogging()
    {
        $handlerA = new TestHandler();
        $handlerB = new TestHandler();

        $monolog = new Monolog('test', [$handlerA, $handlerB]);
        foreach ([$handlerA, $handlerB] as $h) {
            $h->setFormatter(new JsonFormatter());
        }

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $exception = new ContextProvidingException('Stack test');

        $monolog->error('fail', ['exception' => $exception]);

        foreach (['handlerA' => $handlerA, 'handlerB' => $handlerB] as $name => $h) {
            $formatted = $this->getFormattedJson($h);
            $exceptionData = $formatted['context']['exception'];

            self::assertSame('bar', $exceptionData['foo'], "Expected enriched context on {$name}");
            self::assertSame(ContextProvidingException::class, $exceptionData['class']);
        }
    }

    public function testStackDriverSkipsEnrichmentOnBothHandlersWhenReporting()
    {
        $handlerA = new TestHandler();
        $handlerB = new TestHandler();

        $monolog = new Monolog('test', [$handlerA, $handlerB]);
        foreach ([$handlerA, $handlerB] as $h) {
            $h->setFormatter(new JsonFormatter());
        }

        $this->app->instance(LoggerInterface::class, new Logger($monolog));

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $exception = new ContextProvidingException('Stack report test');

        $exceptionHandler->report($exception);

        foreach (['handlerA' => $handlerA, 'handlerB' => $handlerB] as $name => $h) {
            $formatted = $this->getFormattedJson($h);

            self::assertSame('bar', $formatted['context']['foo'], "Context should be at top level on {$name}");

            $exceptionData = $formatted['context']['exception'];
            self::assertArrayNotHasKey('foo', $exceptionData, "Formatter should not enrich on {$name}");
        }
    }

    public function testPreviousExceptionContextIsAlsoEnriched()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler);

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $previous = new ContextProvidingException('Root cause');
        $outer = new RuntimeException('Wrapper', 0, $previous);

        $logger->error('fail', ['exception' => $outer]);

        $formatted = $this->getFormattedJson($handler);
        $exceptionData = $formatted['context']['exception'];

        self::assertSame(RuntimeException::class, $exceptionData['class']);
        self::assertArrayHasKey('previous', $exceptionData);

        $previousData = $exceptionData['previous'];
        self::assertSame(ContextProvidingException::class, $previousData['class']);
        self::assertSame('bar', $previousData['foo']);
    }

    public function testReportEnrichesPreviousExceptionContext()
    {
        $handler = new TestHandler();
        $monolog = new Monolog('test', [$handler]);
        $handler->setFormatter(new JsonFormatter());
        $this->app->instance(LoggerInterface::class, new Logger($monolog));

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $previous = new ContextProvidingException('Root cause');
        $outer = new RuntimeException('Wrapper', 0, $previous);

        $exceptionHandler->report($outer);

        $formatted = $this->getFormattedJson($handler);

        // The outer exception has no context() method, so nothing at the top level
        self::assertArrayNotHasKey('foo', $formatted['context']);

        $exceptionData = $formatted['context']['exception'];

        // Outer exception should NOT be enriched (isReporting matches it)
        self::assertArrayNotHasKey('foo', $exceptionData);

        // Previous exception SHOULD be enriched (isReporting does not match it)
        self::assertArrayHasKey('previous', $exceptionData);
        $previousData = $exceptionData['previous'];
        self::assertSame(ContextProvidingException::class, $previousData['class']);
        self::assertSame('bar', $previousData['foo']);
    }

    public function testExceptionWithoutContextMethodIsNotEnriched()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler);

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $exception = new RuntimeException('Plain exception');

        $logger->error('fail', ['exception' => $exception]);

        $formatted = $this->getFormattedJson($handler);
        $exceptionData = $formatted['context']['exception'];

        self::assertSame(RuntimeException::class, $exceptionData['class']);
        self::assertSame('Plain exception', $exceptionData['message']);
        self::assertArrayNotHasKey('foo', $exceptionData);
    }

    public function testContextCallbacksAreIncludedInFormatterEnrichment()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler);

        $exceptionHandler = new Handler($this->app);
        $exceptionHandler->buildContextUsing(function (Throwable $e) {
            return ['callback_key' => 'callback_value'];
        });
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $exception = new ContextProvidingException('With callbacks');

        $logger->error('fail', ['exception' => $exception]);

        $formatted = $this->getFormattedJson($handler);
        $exceptionData = $formatted['context']['exception'];

        self::assertSame('bar', $exceptionData['foo']);
        self::assertSame('callback_value', $exceptionData['callback_key']);
    }

    public function testGracefulFallbackWhenContainerCannotResolveHandler()
    {
        Container::setInstance(new Container());

        $handler = new TestHandler();
        $monolog = new Monolog('test', [$handler]);
        $handler->setFormatter(new JsonFormatter());

        $exception = new ContextProvidingException('No handler bound');

        $monolog->error('fail', ['exception' => $exception]);

        $formatted = $this->getFormattedJson($handler);
        $exceptionData = $formatted['context']['exception'];

        self::assertSame(ContextProvidingException::class, $exceptionData['class']);
        self::assertSame('No handler bound', $exceptionData['message']);
        self::assertArrayNotHasKey('foo', $exceptionData);
    }

    public function testNonScalarContextValuesAreNormalized()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler);

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $exception = new ObjectContextException('Has objects in context');

        $logger->error('fail', ['exception' => $exception]);

        $formatted = $this->getFormattedJson($handler);
        $exceptionData = $formatted['context']['exception'];

        self::assertIsArray($exceptionData['nested']);
        self::assertSame(ObjectContextException::class, $exceptionData['class']);
    }

    public function testBothOuterAndPreviousContextEnrichedOnDirectLogging()
    {
        $handler = new TestHandler();
        $logger = $this->createLogger($handler);

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $previous = new ContextProvidingException('Root cause');
        $outer = new AnotherContextProvidingException('Wrapper', 0, $previous);

        $logger->error('fail', ['exception' => $outer]);

        $formatted = $this->getFormattedJson($handler);
        $exceptionData = $formatted['context']['exception'];

        // Outer exception should have its own context
        self::assertSame('outer_value', $exceptionData['outer_key']);
        self::assertSame(AnotherContextProvidingException::class, $exceptionData['class']);

        // Previous exception should have its own context
        self::assertArrayHasKey('previous', $exceptionData);
        $previousData = $exceptionData['previous'];
        self::assertSame('bar', $previousData['foo']);
        self::assertSame(ContextProvidingException::class, $previousData['class']);

        // Context keys should not bleed between exceptions
        self::assertArrayNotHasKey('foo', $exceptionData);
        self::assertArrayNotHasKey('outer_key', $previousData);
    }

    public function testBothOuterAndPreviousContextOnReport()
    {
        $handler = new TestHandler();
        $monolog = new Monolog('test', [$handler]);
        $handler->setFormatter(new JsonFormatter());
        $this->app->instance(LoggerInterface::class, new Logger($monolog));

        $exceptionHandler = new Handler($this->app);
        $this->app->instance(ExceptionHandlerContract::class, $exceptionHandler);

        $previous = new ContextProvidingException('Root cause');
        $outer = new AnotherContextProvidingException('Wrapper', 0, $previous);

        $exceptionHandler->report($outer);

        $formatted = $this->getFormattedJson($handler);

        // Outer's context should be at the top level (from the handler)
        self::assertSame('outer_value', $formatted['context']['outer_key']);

        $exceptionData = $formatted['context']['exception'];

        // Outer should NOT be enriched by the formatter (isReporting matches)
        self::assertArrayNotHasKey('outer_key', $exceptionData);

        // Previous SHOULD be enriched by the formatter (isReporting does not match)
        self::assertArrayHasKey('previous', $exceptionData);
        $previousData = $exceptionData['previous'];
        self::assertSame('bar', $previousData['foo']);
        self::assertSame(ContextProvidingException::class, $previousData['class']);
    }

    private function createTestHandler(): TestHandler
    {
        return new TestHandler();
    }

    private function createLogger(TestHandler $handler): Logger
    {
        $monolog = new Monolog('test', [$handler]);
        $handler->setFormatter(new JsonFormatter());

        return new Logger($monolog);
    }

    private function getFormattedJson(?TestHandler $handler = null): array
    {
        $handler ??= $this->app->make('log')->driver()->getLogger()->getHandlers()[0];
        $records = $handler->getRecords();
        self::assertNotEmpty($records, 'Expected at least one log record');

        $formatted = $records[0]['formatted'];

        return json_decode($formatted, true, 512, JSON_THROW_ON_ERROR);
    }
}

class ContextProvidingException extends Exception
{
    public function context(): array
    {
        return ['foo' => 'bar'];
    }
}

class AnotherContextProvidingException extends Exception
{
    public function context(): array
    {
        return ['outer_key' => 'outer_value'];
    }
}

class ObjectContextException extends Exception
{
    public function context(): array
    {
        return [
            'nested' => new \stdClass(),
        ];
    }
}
