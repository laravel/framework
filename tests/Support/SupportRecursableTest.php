<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Support\Recursable;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SupportRecursableTest extends TestCase
{
    public function testForSetsObjectIfBlank()
    {
        $one = (object) [];
        $two = (object) [];

        $recursableOne = new Recursable(fn () => 'foo', 'bar', null);
        $recursableTwo = new Recursable(fn () => 'foo', 'bar', $one);

        $this->assertNull($recursableOne->object);
        $this->assertSame($one, $recursableTwo->object);

        $this->assertSame($recursableOne, $recursableOne->for($two));
        $this->assertSame($two, $recursableOne->object);

        $this->assertSame($recursableTwo, $recursableTwo->for($two));
        $this->assertSame($one, $recursableTwo->object);
    }

    public function testReturnAlwaysOverridesOnRecursion()
    {
        $recursable = new Recursable(fn () => 'foo', null, null);

        $this->assertNull($recursable->onRecursion);

        $this->assertSame($recursable, $recursable->return('bar'));
        $this->assertSame('bar', $recursable->onRecursion);

        $callable = fn () => 'qux';

        $this->assertSame($recursable, $recursable->return($callable));
        $this->assertSame($callable, $recursable->onRecursion);

        $this->assertSame($recursable, $recursable->return('baz'));
        $this->assertSame('baz', $recursable->onRecursion);
    }

    #[DataProvider('backtraceProvider')]
    public function testTargetFromTrace(array $trace, array $target, string $signature)
    {
        $this->assertSame($target, RecursableStub::expose_targetFromTrace($trace));
    }

    #[DataProvider('limitProvider')]
    public function testTargetFromTraceWithRealBacktrace(int $limit)
    {
        $target = RecursableStub::expose_targetFromTrace(test_backtrace($limit));

        $this->assertSame(__FILE__, $target['file']);
        $this->assertSame(__CLASS__, $target['class']);
        $this->assertSame(__FUNCTION__, $target['function']);
        $this->assertSame($this, $target['object']);
    }

    public function testTargetFromTraceWithSingleFrameBacktrace()
    {
        $target = RecursableStub::expose_targetFromTrace(test_backtrace(1));

        $this->assertSame(__FILE__, $target['file']);
        $this->assertSame('', $target['class']);
        $this->assertSame('', $target['function']);
        $this->assertSame(null, $target['object']);
    }

    #[DataProvider('backtraceProvider')]
    public function testSignatureFromTrace(array $trace, array $target, string $signature)
    {
        $this->assertSame($signature, RecursableStub::expose_signatureFromTrace($trace));
    }

    #[DataProvider('limitProvider')]
    public function testSignatureFromTraceWithRealBacktrace(int $limit)
    {
        $this->assertSame(
            sprintf('%s:%s@%s', __FILE__, __CLASS__, __FUNCTION__),
            RecursableStub::expose_signatureFromTrace(test_backtrace($limit)),
        );
    }

    public function testSignatureFromTraceWithSingleFrameBacktrace()
    {
        $trace = test_backtrace(1);

        $this->assertSame(
            sprintf('%s:%d', __FILE__, $trace[0]['line']),
            RecursableStub::expose_signatureFromTrace($trace),
        );
    }

    #[DataProvider('backtraceProvider')]
    public function testHashFromTrace(array $trace, array $target, string $signature)
    {
        $this->assertSame(hash('xxh128', $signature), RecursableStub::expose_hashFromTrace($trace));
    }

    #[DataProvider('limitProvider')]
    public function testHashFromTraceWithRealBacktrace(int $limit)
    {
        $this->assertSame(
            hash('xxh128', sprintf('%s:%s@%s', __FILE__, __CLASS__, __FUNCTION__)),
            RecursableStub::expose_hashFromTrace(test_backtrace($limit)),
        );
    }

    public function testHashFromTraceWithSingleFrameBacktrace()
    {
        $trace = test_backtrace(1);

        $this->assertSame(
            hash('xxh128', sprintf('%s:%d', __FILE__, $trace[0]['line'])),
            RecursableStub::expose_hashFromTrace($trace),
        );
    }

    #[DataProvider('signatureProvider')]
    public function testHashFromSignature(string $signature)
    {
        $this->assertSame(
            hash('xxh128', $signature),
            RecursableStub::expose_hashFromSignature($signature),
        );
    }

    #[DataProvider('backtraceProvider')]
    public function testObjectFromTrace(array $trace, array $target, string $signature)
    {
        $this->assertSame($target['object'], RecursableStub::expose_objectFromTrace($trace));
    }

    #[DataProvider('limitProvider')]
    public function testObjectFromTraceWithRealBacktrace(int $limit)
    {
        $this->assertSame($this, RecursableStub::expose_objectFromTrace(test_backtrace($limit)));
    }

    public function testObjectFromTraceWithSingleFrameBacktrace()
    {
        $this->assertSame(null, RecursableStub::expose_objectFromTrace(test_backtrace(1)));
    }

    #[DataProvider('backtraceProvider')]
    public function testFromTraceCreatesRecursable(array $trace, array $target, string $signature)
    {
        $callback = fn () => 'foo';
        $onRecursion = 'bar';

        $recursable = Recursable::fromTrace($trace, $callback, $onRecursion);

        $this->assertSame($target['object'], $recursable->object);
        $this->assertSame($callback, $recursable->callback);
        $this->assertSame($onRecursion, $recursable->onRecursion);
        $this->assertSame(hash('xxh128', $signature), $recursable->hash);
        $this->assertSame($signature, $recursable->signature);
    }

    #[DataProvider('limitProvider')]
    public function testFromTraceCreatesRecursableWithRealBacktrace(int $limit)
    {
        $callback = fn () => 'foo';
        $onRecursion = 'bar';
        $signature = sprintf('%s:%s@%s', __FILE__, __CLASS__, __FUNCTION__);

        $recursable = Recursable::fromTrace(test_backtrace($limit), $callback, $onRecursion);

        $this->assertSame($this, $recursable->object);
        $this->assertSame($callback, $recursable->callback);
        $this->assertSame($onRecursion, $recursable->onRecursion);
        $this->assertSame(hash('xxh128', $signature), $recursable->hash);
        $this->assertSame($signature, $recursable->signature);
    }

    public function testFromTraceCreatesRecursableWithSingleFrameBacktrace()
    {
        $callback = fn () => 'foo';
        $onRecursion = 'bar';
        $trace = test_backtrace(1);
        $signature = sprintf('%s:%d', __FILE__, $trace[0]['line']);

        $recursable = Recursable::fromTrace($trace, $callback, $onRecursion);

        $this->assertSame(null, $recursable->object);
        $this->assertSame($callback, $recursable->callback);
        $this->assertSame($onRecursion, $recursable->onRecursion);
        $this->assertSame(hash('xxh128', $signature), $recursable->hash);
        $this->assertSame($signature, $recursable->signature);
    }

    #[DataProvider('signatureProvider')]
    public function testFromSignatureCreatesRecursable(string $signature)
    {
        $callback = fn () => 'foo';
        $onRecursion = 'bar';
        $object = (object) [];

        $recursable = Recursable::fromSignature($signature, $callback, $onRecursion, $object);

        $this->assertSame($object, $recursable->object);
        $this->assertSame($callback, $recursable->callback);
        $this->assertSame($onRecursion, $recursable->onRecursion);
        $this->assertSame(hash('xxh128', $signature ?: Recursable::BLANK_SIGNATURE), $recursable->hash);
        $this->assertSame($signature ?: Recursable::BLANK_SIGNATURE, $recursable->signature);
    }

    public static function backtraceProvider(): array
    {
        $empty = ['file' => '', 'class' => '', 'function' => '', 'line' => 0, 'object' => null];
        $object = (object) [];

        return [
            'no frames' => [[], $empty, ':0'],
            'one empty frame' => [[[]], $empty, ':0'],
            'two empty frames' => [[[], []], $empty, ':0'],
            'empty first frame' => [
                [[], ['class' => 'SomeClass', 'function' => 'someMethod', 'object' => $object]],
                [...$empty, 'class' => 'SomeClass', 'function' => 'someMethod', 'object' => $object],
                ':SomeClass@someMethod',
            ],
            'single frame' => [
                [['file' => '/path/to/file.php', 'line' => 42]],
                [...$empty, 'file' => '/path/to/file.php', 'line' => 42],
                '/path/to/file.php:42',
            ],
            'full trace' => [
                [['file' => '/path/to/file.php', 'line' => 42], ['class' => 'SomeClass', 'function' => 'someMethod', 'object' => $object]],
                ['file' => '/path/to/file.php', 'class' => 'SomeClass', 'function' => 'someMethod', 'line' => 42, 'object' => $object],
                '/path/to/file.php:SomeClass@someMethod',
            ],
        ];
    }

    public static function limitProvider(): array
    {
        return [
            'two frames' => [2],
            'three frames' => [3],
            'full trace' => [0],
        ];
    }

    public static function signatureProvider(): array
    {
        return [
            'blank' => [''],
            'global function' => ['/some/file.php:42'],
            'class' => ['/some/file.php:SomeClass@someMethod'],
            'random' => [base64_encode(random_bytes(16))],
        ];
    }
}

function test_backtrace(int $limit = 2): array
{
    return debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit);
}

class RecursableStub extends Recursable
{
    public static function __callStatic(string $method, array $parameters)
    {
        $method = str_starts_with($method, 'expose_') ? Str::after($method, 'expose_') : $method;

        return method_exists(static::class, $method)
            ? static::$method(...$parameters)
            : throw new BadMethodCallException(sprintf('Static Method %s::%s does not exist.', static::class, $method));
    }
}
