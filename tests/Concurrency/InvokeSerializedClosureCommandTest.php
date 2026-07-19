<?php

namespace Illuminate\Tests\Concurrency;

use Exception;
use Illuminate\Concurrency\Console\InvokeSerializedClosureCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class InvokeSerializedClosureCommandTest extends TestCase
{
    public function testItCapturesParametersBackedByProperties()
    {
        $parameters = $this->parametersFor(new CapturableParametersException('https://example.com', 400));

        $this->assertSame(['uri' => 'https://example.com', 'statusCode' => 400], $parameters);
    }

    public function testItCapturesNullAndFalseyParameterValues()
    {
        $this->assertSame(['status' => null], $this->parametersFor(new NullableParameterException(null)));
        $this->assertSame(['status' => 0], $this->parametersFor(new NullableParameterException(0)));
    }

    public function testItCapturesTheBaseExceptionConstructorParameters()
    {
        $parameters = $this->parametersFor(new Exception('boom', 42));

        $this->assertSame(['message' => 'boom', 'code' => 42, 'previous' => null], $parameters);
    }

    public function testItCapturesNothingWhenAParameterIsNotBackedByAProperty()
    {
        $this->assertSame([], $this->parametersFor(new UnbackedParameterException('payments')));
    }

    public function testItCapturesNothingWhenAParameterValueIsNotJsonRepresentable()
    {
        $this->assertSame([], $this->parametersFor(new Exception('boom', 0, new RuntimeException('previous'))));
    }

    public function testItCapturesNothingWhenTheConstructorIsInherited()
    {
        $this->assertSame([], $this->parametersFor(new InheritedConstructorException('boom')));
    }

    protected function parametersFor(Throwable $e): array
    {
        $command = new class extends InvokeSerializedClosureCommand
        {
            public function parametersFor(Throwable $e): array
            {
                return $this->exceptionParameters($e);
            }
        };

        return $command->parametersFor($e);
    }
}

class CapturableParametersException extends Exception
{
    public function __construct(public string $uri, public int $statusCode)
    {
        parent::__construct("Request to {$uri} failed with status {$statusCode}");
    }
}

class NullableParameterException extends RuntimeException
{
    public function __construct(public ?int $status = null)
    {
        parent::__construct('Exception with nullable parameter');
    }
}

class UnbackedParameterException extends Exception
{
    public function __construct(string $context)
    {
        parent::__construct("Failed in {$context}");
    }
}

class InheritedConstructorException extends Exception
{
}
