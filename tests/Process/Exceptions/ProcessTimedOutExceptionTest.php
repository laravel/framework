<?php

namespace Illuminate\Tests\Process\Exceptions;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Process\FakeProcessResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException as SymfonyRuntimeException;
use Symfony\Component\Process\Process;

class ProcessTimedOutExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfSymfonyRuntimeException(): void
    {
        $exception = new ProcessTimedOutException($this->symfonyTimeoutException(), new FakeProcessResult);

        $this->assertInstanceOf(SymfonyRuntimeException::class, $exception);
    }

    public function testExceptionHoldsResultAndInheritsMessageAndCodeFromOriginal(): void
    {
        $original = $this->symfonyTimeoutException();
        $result = new FakeProcessResult;

        $exception = new ProcessTimedOutException($original, $result);

        $this->assertSame($result, $exception->result);
        $this->assertSame($original->getMessage(), $exception->getMessage());
        $this->assertSame($original->getCode(), $exception->getCode());
        $this->assertSame($original, $exception->getPrevious());
    }

    protected function symfonyTimeoutException(): SymfonyProcessTimedOutException
    {
        $process = new Process(['php', '-v']);
        $process->setTimeout(5);

        return new SymfonyProcessTimedOutException($process, SymfonyProcessTimedOutException::TYPE_GENERAL);
    }
}
