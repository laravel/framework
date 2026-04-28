<?php

namespace Illuminate\Tests\Log;

use Exception;
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
        $this->assertSame('bar', $exceptionData['foo']);
        $this->assertSame(ContextProvidingException::class, $exceptionData['class']);
    }

    public function testExceptionContextIsNotDuplicatedWhenGoingThroughReport()
    {
        $exception = new ContextProvidingException('Something went wrong');

        $this->app->make(ExceptionHandlerContract::class)->report($exception);

        $formatted = $this->getFormattedJson();

        // Context should be at the top level (from the handler)
        $this->assertSame('bar', $formatted['context']['foo']);

        // But NOT enriched inside the normalized exception (formatter should skip)
        $exceptionData = $formatted['context']['exception'];
        $this->assertArrayNotHasKey('foo', $exceptionData);
    }

    public function testStackDriverEnrichesBothHandlersOnDirectLogging()
    {
        $handlerA = new TestHandler();
        $handlerB = new TestHandler();

        $monolog = new Monolog('test', [$handlerA, $handlerB]);
        foreach ([$handlerA, $handlerB] as $h) {
            $h->setFormatter(new JsonFormatter());
        }

        $exception = new ContextProvidingException('Stack test');

        $monolog->error('fail', ['exception' => $exception]);

        foreach (['handlerA' => $handlerA, 'handlerB' => $handlerB] as $name => $h) {
            $formatted = $this->getFormattedJson($h);
            $exceptionData = $formatted['context']['exception'];

            $this->assertSame('bar', $exceptionData['foo'], "Expected enriched context on {$name}");
            $this->assertSame(ContextProvidingException::class, $exceptionData['class']);
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

            $this->assertSame('bar', $formatted['context']['foo'], "Context should be at top level on {$name}");

            $exceptionData = $formatted['context']['exception'];
            $this->assertArrayNotHasKey('foo', $exceptionData, "Formatter should not enrich on {$name}");
        }
    }

    public function testPreviousExceptionContextIsAlsoEnriched()
    {
        $previous = new ContextProvidingException('Root cause');
        $outer = new RuntimeException('Wrapper', 0, $previous);

        Log::error('fail', ['exception' => $outer]);

        $formatted = $this->getFormattedJson();
        $exceptionData = $formatted['context']['exception'];

        $this->assertSame(RuntimeException::class, $exceptionData['class']);
        $this->assertArrayHasKey('previous', $exceptionData);

        $previousData = $exceptionData['previous'];
        $this->assertSame(ContextProvidingException::class, $previousData['class']);
        $this->assertSame('bar', $previousData['foo']);
    }

    public function testReportEnrichesPreviousExceptionContext()
    {
        $exception = new RuntimeException('Wrapper', 0, new ContextProvidingException('Root cause'));

        $this->app->make(ExceptionHandlerContract::class)->report($exception);

        $formatted = $this->getFormattedJson();

        // The outer exception has no context() method, so nothing at the top level
        $this->assertArrayNotHasKey('foo', $formatted['context']);

        $exceptionData = $formatted['context']['exception'];

        // Outer exception should NOT be enriched (isReporting matches it)
        $this->assertArrayNotHasKey('foo', $exceptionData);

        // Previous exception SHOULD be enriched (isReporting does not match it)
        $this->assertArrayHasKey('previous', $exceptionData);
        $previousData = $exceptionData['previous'];
        $this->assertSame(ContextProvidingException::class, $previousData['class']);
        $this->assertSame('bar', $previousData['foo']);
    }

    public function testExceptionWithoutContextMethodIsNotEnriched()
    {
        Log::error('fail', ['exception' => new RuntimeException('Plain exception')]);

        $formatted = $this->getFormattedJson();
        $exceptionData = $formatted['context']['exception'];

        $this->assertSame(RuntimeException::class, $exceptionData['class']);
        $this->assertSame('Plain exception', $exceptionData['message']);
        $this->assertArrayNotHasKey('foo', $exceptionData);
    }

    public function testContextCallbacksAreIncludedInFormatterEnrichment()
    {
        $this->app->make(ExceptionHandlerContract::class)->buildContextUsing(function (Throwable $e) {
            return ['callback_key' => 'callback_value'];
        });

        $exception = new ContextProvidingException('With callbacks');

        Log::error('fail', ['exception' => $exception]);

        $formatted = $this->getFormattedJson();
        $exceptionData = $formatted['context']['exception'];

        $this->assertSame('bar', $exceptionData['foo']);
        $this->assertSame('callback_value', $exceptionData['callback_key']);
    }

    public function testNonScalarContextValuesAreNormalized()
    {
        $exception = new ObjectContextException('Has objects in context');

        Log::error('fail', ['exception' => $exception]);

        $formatted = $this->getFormattedJson();
        $exceptionData = $formatted['context']['exception'];

        $this->assertIsArray($exceptionData['nested']);
        $this->assertSame(ObjectContextException::class, $exceptionData['class']);
    }

    public function testBothOuterAndPreviousContextEnrichedOnDirectLogging()
    {
        $previous = new ContextProvidingException('Root cause');
        $outer = new AnotherContextProvidingException('Wrapper', 0, $previous);

        Log::error('fail', ['exception' => $outer]);

        $formatted = $this->getFormattedJson();
        $exceptionData = $formatted['context']['exception'];

        // Outer exception should have its own context
        $this->assertSame('outer_value', $exceptionData['outer_key']);
        $this->assertSame(AnotherContextProvidingException::class, $exceptionData['class']);

        // Previous exception should have its own context
        $this->assertArrayHasKey('previous', $exceptionData);
        $previousData = $exceptionData['previous'];
        $this->assertSame('bar', $previousData['foo']);
        $this->assertSame(ContextProvidingException::class, $previousData['class']);

        // Context keys should not bleed between exceptions
        $this->assertArrayNotHasKey('foo', $exceptionData);
        $this->assertArrayNotHasKey('outer_key', $previousData);
    }

    public function testBothOuterAndPreviousContextOnReport()
    {
        $previous = new ContextProvidingException('Root cause');
        $outer = new AnotherContextProvidingException('Wrapper', 0, $previous);

        $this->app->make(ExceptionHandlerContract::class)->report($outer);

        $formatted = $this->getFormattedJson();

        // Outer's context should be at the top level (from the handler)
        $this->assertSame('outer_value', $formatted['context']['outer_key']);

        $exceptionData = $formatted['context']['exception'];

        // Outer should NOT be enriched by the formatter (isReporting matches)
        $this->assertArrayNotHasKey('outer_key', $exceptionData);

        // Previous SHOULD be enriched by the formatter (isReporting does not match)
        $this->assertArrayHasKey('previous', $exceptionData);
        $previousData = $exceptionData['previous'];
        $this->assertSame('bar', $previousData['foo']);
        $this->assertSame(ContextProvidingException::class, $previousData['class']);
    }

    public function testFormatterHandlesNormalizationDepthLimit()
    {
        $formatter = new JsonFormatter();
        $formatter->setMaxNormalizeDepth(3);

        $handler = new TestHandler();
        $handler->setFormatter($formatter);
        $monolog = new Monolog('test', [$handler]);

        $inner = new ContextProvidingException('inner');
        $outer = new ContextProvidingException('outer', 0, $inner);

        $monolog->error('fail', ['exception' => $outer]);

        $formatted = $this->getFormattedJson($handler);
        $exceptionData = $formatted['context']['exception'];

        // Outermost exception at depth 2 — context normalize called at depth 3,
        // within limit so the array is returned (values inside may be depth-truncated)
        $this->assertArrayHasKey('foo', $exceptionData);

        // Previous exception at depth 3 — context normalize called at depth 4,
        // exceeds limit so normalize returns a string. is_array() guard skips enrichment.
        $this->assertArrayHasKey('previous', $exceptionData);
        $this->assertArrayNotHasKey('foo', $exceptionData['previous']);
        $this->assertSame(ContextProvidingException::class, $exceptionData['previous']['class']);
    }

    public function testNoHandlerSet_mergesExceptionContext()
    {
        $this->app->bind(ExceptionHandlerContract::class, function () {
            throw new Exception('this never works');
        });
        Log::warning('fail', ['exception' => new ContextProvidingException('Oh no!')]);

        $formatted = $this->getFormattedJson();

        $exceptionData = $formatted['context']['exception'];
        $this->assertSame('bar', $exceptionData['foo']);
        $this->assertSame(ContextProvidingException::class, $exceptionData['class']);
    }

    private function getFormattedJson(?TestHandler $handler = null): array
    {
        $handler ??= $this->app->make('log')->driver()->getLogger()->getHandlers()[0];
        $records = $handler->getRecords();
        $this->assertNotEmpty($records, 'Expected at least one log record');

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
