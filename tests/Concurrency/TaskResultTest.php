<?php

namespace Illuminate\Tests\Concurrency;

use Exception;
use Illuminate\Concurrency\TaskResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TaskResultTest extends TestCase
{
    #[DataProvider('resultValues')]
    public function testSuccessEnvelopesRoundTripValues(mixed $value)
    {
        $envelope = TaskResult::success($value);

        $this->assertTrue($envelope['successful']);
        $this->assertEquals($value, TaskResult::unwrap($envelope));
    }

    public static function resultValues(): array
    {
        return [
            'string' => ['value'],
            'integer' => [42],
            'zero' => [0],
            'false' => [false],
            'null' => [null],
            'empty string' => [''],
            'array' => [['a' => 1, 'b' => [2, 3]]],
            'object' => [(object) ['name' => 'Taylor']],
        ];
    }

    public function testFailureEnvelopeCapturesExceptionMetadata()
    {
        $envelope = TaskResult::failure(new RuntimeException('Something broke'));

        $this->assertFalse($envelope['successful']);
        $this->assertSame(RuntimeException::class, $envelope['exception']);
        $this->assertSame('Something broke', $envelope['message']);
        $this->assertSame(__FILE__, $envelope['file']);
        $this->assertIsInt($envelope['line']);
        $this->assertSame([], $envelope['parameters']);
    }

    public function testUnwrapRethrowsFailuresUsingTheMessage()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Something broke');

        TaskResult::unwrap(TaskResult::failure(new RuntimeException('Something broke')));
    }

    public function testUnwrapReconstructsExceptionsWithConstructorParameters()
    {
        $envelope = TaskResult::failure(new TaskResultTestExceptionWithParam(
            'https://api.example.com', 400, 'Bad Request', 'Invalid payload',
        ));

        try {
            TaskResult::unwrap($envelope);
        } catch (TaskResultTestExceptionWithParam $e) {
            $this->assertSame('https://api.example.com', $e->uri);
            $this->assertSame(400, $e->statusCode);
            $this->assertSame('Bad Request', $e->reason);
            $this->assertSame('Invalid payload', $e->responseBody);

            return;
        }

        $this->fail('The expected exception was not thrown.');
    }

    #[DataProvider('falseyParameters')]
    public function testUnwrapPreservesFalseyConstructorParameters(int|bool|string $value)
    {
        $envelope = TaskResult::failure(new TaskResultTestExceptionWithFalseyParam($value));

        try {
            TaskResult::unwrap($envelope);
        } catch (TaskResultTestExceptionWithFalseyParam $e) {
            $this->assertSame($value, $e->value);

            return;
        }

        $this->fail('The expected exception was not thrown.');
    }

    public static function falseyParameters(): array
    {
        return [
            'zero' => [0],
            'false' => [false],
            'empty string' => [''],
        ];
    }

    public function testUnwrapFallsBackWhenTheConstructorCannotBeSatisfied()
    {
        $envelope = TaskResult::failure(new TaskResultTestExceptionWithRequiredThrowable(
            'Query failed', new RuntimeException('The underlying failure'),
        ));

        try {
            TaskResult::unwrap($envelope);
        } catch (RuntimeException $e) {
            $this->assertSame(
                TaskResultTestExceptionWithRequiredThrowable::class.': Query failed',
                $e->getMessage(),
            );

            return;
        }

        $this->fail('The expected exception was not thrown.');
    }

    public function testUnwrapFallsBackWhenTheExceptionClassDoesNotExist()
    {
        $envelope = TaskResult::failure(new RuntimeException('Original message'));

        $envelope['exception'] = 'App\\Exceptions\\DoesNotExist';

        try {
            TaskResult::unwrap($envelope);
        } catch (RuntimeException $e) {
            $this->assertSame('App\\Exceptions\\DoesNotExist: Original message', $e->getMessage());

            return;
        }

        $this->fail('The expected exception was not thrown.');
    }

    public function testFailureEnvelopeIgnoresInheritedConstructors()
    {
        $envelope = TaskResult::failure(new TaskResultTestExceptionWithoutConstructor('Inherited'));

        $this->assertSame([], $envelope['parameters']);

        $this->expectException(TaskResultTestExceptionWithoutConstructor::class);
        $this->expectExceptionMessage('Inherited');

        TaskResult::unwrap($envelope);
    }
}

class TaskResultTestExceptionWithParam extends Exception
{
    public function __construct(
        public string $uri,
        public int $statusCode,
        public string $reason,
        public string|array $responseBody = '',
    ) {
        parent::__construct("API request to {$uri} failed with status $statusCode $reason");
    }
}

class TaskResultTestExceptionWithFalseyParam extends Exception
{
    public function __construct(public int|bool|string $value)
    {
        parent::__construct('Exception with falsey parameter');
    }
}

class TaskResultTestExceptionWithoutConstructor extends Exception
{
}

class TaskResultTestExceptionWithRequiredThrowable extends Exception
{
    public function __construct(string $message, \Throwable $previous)
    {
        parent::__construct($message, 0, $previous);
    }
}
