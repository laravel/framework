<?php

namespace Illuminate\Tests;

use Illuminate\Console\Exceptions\ProcessFailedException;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Illuminate\Console\Exceptions\ProcessTimedOutException;
use Illuminate\Console\Process\Factory;
use Illuminate\Console\Process\Results\FakeResult;
use Illuminate\Console\Process\Results\Result;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ProcessTest extends TestCase
{
    /**
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory;
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testResultOk()
    {
        $this->factory->fake();

        $result = $this->factory->run($this->ls());

        $this->assertTrue($result->ok());
    }

    public function testResultFailed()
    {
        $this->factory->fake(function () {
            return $this->factory::result('my-process-output', 1);
        });

        $result = $this->factory->run($this->ls());

        $this->assertTrue($result->failed());
    }

    public function testResultOutput()
    {
        $this->factory->fake(function ($process) {
            if (str($process->getCommandLine())->contains('fooo')) {
                return $this->factory::result('some failure', 1);
            }
        });

        $this->factory->fake([
            'nuno' => $this->factory::result('drwxr-xr-x   25 nunomaduro'),
            'taylor' => $this->factory::result('drwxr-xr-x   25 taylorotwell'),
            '*' => $this->factory::result('drwxr-xr-x   25 root'),
        ]);

        $result = $this->factory->run('fooo');
        $this->assertSame('some failure', $result->output());
        $this->assertTrue($result->failed());

        $result = $this->factory->run(['nuno']);
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->run('taylor');
        $this->assertSame('drwxr-xr-x   25 taylorotwell', $result->output());

        $result = $this->factory->run($this->ls());
        $this->assertSame('drwxr-xr-x   25 root', $result->output());
    }

    public function testResultOutputWhenFakeDoesNotExist()
    {
        $this->factory->fake([
            'nuno' => $this->factory::result('drwxr-xr-x   25 nunomaduro'),
        ]);

        $result = $this->factory->run(['nuno']);
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessTest', $result->output());
        $this->assertTrue($result->ok());

        $this->factory->fake();

        $result = $this->factory->run(['nuno']);
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('', $result->output());
        $this->assertTrue($result->ok());
    }

    public function testProcessExceptionIsThrownIfTheProcessFails()
    {
        $exception = null;
        $result = $this->factory::result('my-failure', 1);

        $this->factory->fake(fn () => $result);

        try {
            $this->factory->run($this->ls())->throw();
        } catch (ProcessFailedException $exception) {
            // ..
        }

        $this->assertInstanceOf(ProcessFailedException::class, $exception);

        $this->assertSame($result, $exception->result());
        $this->assertStringContainsString($this->ls(), $exception->process()->getCommandLine());
        $this->assertNull($exception->getPrevious());
    }

    public function testResultEnsuresTheProcessStarts()
    {
        $this->expectException(ProcessNotStartedException::class);

        if (windows_os()) {
            $this->expectExceptionMessage("The process \"{$this->ls()}\" failed to start.");
        } else {
            $this->expectExceptionMessage("The process \"'{$this->ls()}'\" failed to start.");
        }

        new Result(new Process([$this->ls()]));
    }

    public function testFakeResultEnsuresTheProcessStarts()
    {
        $this->expectException(ProcessNotStartedException::class);
        $this->expectExceptionMessage('The process failed to start.');

        tap(new FakeResult('foo', 0))->wait();
    }

    public function testWait()
    {
        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessTest', $result->wait()->output());
        $this->assertTrue($result->ok());

        $this->factory->fake();

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('', $result->wait()->output());
        $this->assertTrue($result->ok());
    }

    public function testDoesNotThrow()
    {
        $result = $this->factory->path(__DIR__)->run($this->ls())->throw();
        $this->assertStringContainsString('ProcessTest', $result->wait()->output());
        $this->assertTrue($result->ok());

        $this->factory->fake();

        $result = $this->factory->path(__DIR__)->run($this->ls())->throw();
        $this->assertStringContainsString('', $result->wait()->output());
        $this->assertTrue($result->ok());
    }

    public function testTimeout()
    {
        $exception = null;

        $result = $this->factory->path(__DIR__)->timeout(1)->run($this->sleep(3));

        $this->assertSame(1.0, $result->process()->getTimeout());

        try {
            $result->wait();
        } catch (ProcessTimedOutException $exception) {
            // ..
        }

        $this->assertInstanceOf(ProcessTimedOutException::class, $exception);
        $this->assertTrue($exception->result()->failed());
        $this->assertSame(143, $exception->result()->exitCode());
    }

    public function testSignals()
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('Test requires pcntl extension.');
        }

        $result = $this->factory->path(__DIR__)->run($this->sleep(5));
        $result->process()->signal(SIGKILL);

        $this->assertTrue($result->failed());
        $this->assertSame(128 + SIGKILL, $result->exitCode());
    }

    public function testPath()
    {
        $this->factory->fake();

        $result = $this->factory->path(__DIR__)->run($this->ls());

        $this->assertSame(__DIR__, $result->process()->getWorkingDirectory());
    }

    public function testForever()
    {
        $this->factory->fake();

        $result = $this->factory->forever()->run($this->ls());

        $this->assertNull($result->process()->getTimeout());
    }

    public function testResultToString()
    {
        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessTest', (string) $result);
        $this->assertStringContainsString('ProcessTest', $result->toString());
        $this->assertTrue($result->ok());

        $this->factory->fake(fn () => $this->factory::result('ProcessOutput'));

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessOutput', (string) $result);
        $this->assertStringContainsString('ProcessOutput', $result->toString());
        $this->assertTrue($result->ok());
    }

    public function testResultToArray()
    {
        $this->factory->fake([
            'one' => $this->factory::result(['My line 1']),
            'two' => $this->factory::result(['My line 1', 'My line 2']),
            'three' => $this->factory::result("My line 1\nMy line 2\nMy line 3"),
        ]);

        $result = $this->factory->run('one');
        $this->assertCount(1, $result->toArray());
        $this->assertSame('My line 1', $result->toArray()[0]);

        $result = $this->factory->run('two');
        $this->assertCount(2, $result->toArray());
        $this->assertSame('My line 1', $result->toArray()[0]);
        $this->assertSame('My line 2', $result->toArray()[1]);

        $result = $this->factory->run('three');
        $this->assertCount(3, $result->toArray());
        $this->assertSame('My line 1', $result->toArray()[0]);
        $this->assertSame('My line 2', $result->toArray()[1]);
        $this->assertSame('My line 3', $result->toArray()[2]);
    }

    public function testResultArrayAccess()
    {
        $this->factory->fake(fn () => $this->factory::result(['My line 1', 'My line 2']));

        $result = $this->factory->run('two');

        $this->assertCount(2, $result->toArray());
        $this->assertSame('My line 1', $result[0]);
        $this->assertSame('My line 2', $result[1]);
    }

    public function testResultAsIterator()
    {
        $this->factory->fake(fn () => $this->factory::result(['My line 1', 'My line 2']));

        $result = $this->factory->run('two');

        $output = iterator_to_array($result);

        $this->assertCount(2, $output);
        $this->assertSame('My line 1', $output[0]);
        $this->assertSame('My line 2', $output[1]);
    }

    public function testWithArguments()
    {
        $result = $this->factory->path(__DIR__)->withArguments([$this->ls()])->run();
        $this->assertStringContainsString('ProcessTest', (string) $result);
        $this->assertStringContainsString('ProcessTest', $result->toString());
        $this->assertTrue($result->ok());

        $this->factory->fake(fn () => $this->factory::result('ProcessOutput'));

        $result = $this->factory->path(__DIR__)->withArguments([$this->ls()])->run();
        $this->assertStringContainsString('ProcessOutput', (string) $result);
        $this->assertStringContainsString('ProcessOutput', $result->toString());
        $this->assertTrue($result->ok());
    }

    protected function ls()
    {
        return windows_os() ? 'dir' : 'ls';
    }

    protected function sleep($seconds)
    {
        return windows_os() ? sprintf('timeout /t %s', $seconds) : sprintf('sleep %s', $seconds);
    }
}
