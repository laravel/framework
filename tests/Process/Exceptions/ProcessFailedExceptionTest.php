<?php

namespace Illuminate\Tests\Process\Exceptions;

use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\FakeProcessResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProcessFailedExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $result = new FakeProcessResult(command: 'exit 1', exitCode: 1);

        $exception = new ProcessFailedException($result);

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsResultAndUsesExitCodeAsCode(): void
    {
        $result = new FakeProcessResult(command: 'exit 1', exitCode: 1);

        $exception = new ProcessFailedException($result);

        $this->assertSame($result, $exception->result);
        $this->assertSame(1, $exception->getCode());
        $this->assertSame("The command \"exit 1\" failed.\n\nExit Code: 1", $exception->getMessage());
    }

    public function testExceptionMessageIncludesOutput(): void
    {
        $result = new FakeProcessResult(command: 'ls', exitCode: 1, output: 'file.txt');

        $exception = new ProcessFailedException($result);

        $this->assertSame(
            "The command \"ls\" failed.\n\nExit Code: 1\n\nOutput:\n================\nfile.txt\n",
            $exception->getMessage()
        );
    }

    public function testExceptionMessageIncludesErrorOutput(): void
    {
        $result = new FakeProcessResult(command: 'ls', exitCode: 1, errorOutput: 'permission denied');

        $exception = new ProcessFailedException($result);

        $this->assertSame(
            "The command \"ls\" failed.\n\nExit Code: 1\n\nError Output:\n================\npermission denied\n",
            $exception->getMessage()
        );
    }
}
