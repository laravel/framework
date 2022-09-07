<?php

namespace Illuminate\Tests;

use Illuminate\Console\Exceptions\ProcessFailedException;
use Illuminate\Console\Exceptions\ProcessNotRunningException;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Illuminate\Console\Process;
use Illuminate\Console\Process\DelayedStart;
use Illuminate\Console\Process\Factory;
use Illuminate\Console\Process\Pool;
use Illuminate\Console\Process\Results\FakeResult;
use Illuminate\Console\Process\Results\Result;
use Mockery as m;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

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

    public function testCommandGetsEscaped()
    {
        $this->factory->fake();

        $result = $this->factory->run(['ls', '-la']);

        $this->factory->assertRan(function ($process) {
            return windows_os() ? $process->command() === 'ls -la' : $process->command() === "'ls' '-la'";
        });
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
            'taylor*' => $this->factory::result('drwxr-xr-x   25 taylorotwell'),
            '*joe*' => $this->factory::result('drwxr-xr-x   25 joe'),
            '*' => $this->factory::result('drwxr-xr-x   25 root'),
        ]);

        $result = $this->factory->run('fooo');
        $this->assertSame('some failure', $result->output());
        $this->assertTrue($result->failed());

        $result = $this->factory->run('nuno');
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->run('wwwjoewww');
        $this->assertSame('drwxr-xr-x   25 joe', $result->output());

        $result = $this->factory->run('taylor otwell');
        $this->assertSame('drwxr-xr-x   25 taylorotwell', $result->output());

        $result = $this->factory->run($this->ls());
        $this->assertSame('drwxr-xr-x   25 root', $result->output());
    }

    public function testOutputAsCallback()
    {
        $this->factory->fake(function ($process) {
            return $this->factory::result('my stdout output');
        });

        $outputViaCallback = '';
        $typeViaCallback = null;

        $output = $this->factory->run('ls', function ($output, $type) use (&$outputViaCallback, &$typeViaCallback) {
            $typeViaCallback = $type;
            $outputViaCallback = $output;
        })->output();

        $this->assertSame($outputViaCallback, $output);
        $this->assertSame('my stdout output', $output);
        $this->assertSame(Process::STDOUT, $typeViaCallback);
    }

    public function testOutput()
    {
        $this->factory->fake(function ($process) {
            return $this->factory::result(['my stdout line 1', 'my stdout line 2']);
        });

        $outputViaCallback = '';
        $typeViaCallback = null;

        $output = $this->factory->output(function ($output, $type) use (&$outputViaCallback, &$typeViaCallback) {
            $typeViaCallback = $type;
            $outputViaCallback = $output;
        })->run('ls')->output();

        $this->assertSame($outputViaCallback, $output);
        $this->assertSame("my stdout line 1\nmy stdout line 2", $outputViaCallback);
        $this->assertSame(Process::STDOUT, $typeViaCallback);
    }

    public function testErrorOutput()
    {
        $this->factory->fake(function ($process) {
            return $this->factory::result('', 1, ['my stderr line 1', 'my stderr line 2']);
        });

        $outputViaCallback = '';
        $typeViaCallback = null;

        $output = $this->factory->output(function ($output, $type) use (&$outputViaCallback, &$typeViaCallback) {
            $typeViaCallback = $type;
            $outputViaCallback = $output;
        })->run('ls')->errorOutput();

        $this->assertSame($outputViaCallback, $output);
        $this->assertSame("my stderr line 1\nmy stderr line 2", $outputViaCallback);
        $this->assertSame(Process::STDERR, $typeViaCallback);
    }

    public function testResultErrorOutput()
    {
        $this->factory->fake(function () {
            return $this->factory::result('my stdout output', 1, 'my stderr output');
        });

        $result = $this->factory->run('');
        $this->assertSame('my stdout output', $result->output());
        $this->assertTrue($result->failed());
        $this->assertSame('my stderr output', $result->errorOutput());
    }

    public function testErrorOutputAsCallback()
    {
        $this->factory->fake(function ($process) {
            return $this->factory::result('', 1, 'my stderr output');
        });

        $outputViaCallback = '';
        $typeViaCallback = null;

        $output = $this->factory->run('ls', function ($output, $type) use (&$outputViaCallback, &$typeViaCallback) {
            $typeViaCallback = $type;
            $outputViaCallback = $output;
        })->errorOutput();

        $this->assertSame($outputViaCallback, $output);
        $this->assertSame('my stderr output', $output);
        $this->assertSame(Process::STDERR, $typeViaCallback);
    }

    public function testResultOutputWhenFakeDoesNotExist()
    {
        $this->factory->fake([
            'nuno' => $this->factory::result('drwxr-xr-x   25 nunomaduro'),
        ]);

        $result = $this->factory->run('nuno');
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessTest', $result->output());
        $this->assertTrue($result->ok());

        $this->factory->fake();

        $result = $this->factory->run('nuno');
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

        tap(new FakeResult('foo', 0, ''))->wait();
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
        $this->factory->fake();

        $result = $this->factory->run('sleep 2');
        $this->assertSame(60.0, $result->process()->getTimeout());

        $result = $this->factory->forever()->run('sleep 2');
        $this->assertSame(null, $result->process()->getTimeout());

        $result = $this->factory->timeout(1)->run('sleep 2');
        $this->assertSame(1.0, $result->process()->getTimeout());

        $result = $this->factory->timeout(500)->run('sleep 2');
        $this->assertSame(500.0, $result->process()->getTimeout());
    }

    public function testSignals()
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('Test requires pcntl extension.');
        }

        $result = $this->factory->path(__DIR__)->run($this->ls());
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

    public function testResultRunning()
    {
        $result = $this->factory->run($this->ls());
        $this->assertTrue($result->running());
        $this->assertTrue($result->ok());
        $this->assertFalse($result->running());

        $this->factory->fake();

        $result = $this->factory->run($this->ls());

        $this->assertTrue($result->running());
        $this->assertTrue($result->ok());
        $this->assertFalse($result->running());
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

    public function testCommand()
    {
        $result = $this->factory->path(__DIR__)->command($this->ls())->run();
        $this->assertStringContainsString('ProcessTest', (string) $result);
        $this->assertStringContainsString('ProcessTest', $result->toString());
        $this->assertTrue($result->ok());

        $this->factory->fake(fn () => $this->factory::result('ProcessOutput'));

        $result = $this->factory->path(__DIR__)->command($this->ls())->run();
        $this->assertStringContainsString('ProcessOutput', (string) $result);
        $this->assertStringContainsString('ProcessOutput', $result->toString());
        $this->assertTrue($result->ok());
    }

    public function testDelayedRun()
    {
        $this->factory->fake();

        $result = $this->factory->delayStart()->run($this->ls());
        $this->assertInstanceOf(DelayedStart::class, $result);
        $this->assertFalse($result->process()->isRunning()); // because of fake...
        $this->assertTrue($result->ok());
    }

    public function testProcessesOnPoolMustCallRun()
    {
        $this->factory->fake();

        $this->expectException(ProcessNotRunningException::class);

        $this->factory->pool(function (Pool $pool) {
            return [$pool->path(__DIR__)];
        });
    }

    public function testPoolResults()
    {
        $this->factory->fake([
            'one' => $this->factory::result(['My line 1']),
            'two' => $this->factory::result(['My line 1', 'My line 2'], 1),
            'three' => $this->factory::result(['My line 1', 'My line 2', 'My line 3'], 143),
        ]);

        $results = $this->factory->pool(fn (Pool $pool) => [
            $pool->run('one'),
            $pool->run('two'),
            $pool->run('three'),
        ]);

        $this->assertCount(3, $results);

        $this->assertTrue($results[0]->ok());
        $this->assertSame(['My line 1'], $results[0]->toArray());

        $this->assertTrue($results[1]->failed());
        $this->assertSame(1, $results[1]->exitCode());
        $this->assertSame(['My line 1', 'My line 2'], $results[1]->toArray());

        $this->assertTrue($results[2]->failed());
        $this->assertSame(143, $results[2]->exitCode());
        $this->assertSame(['My line 1', 'My line 2', 'My line 3'], $results[2]->toArray());
    }

    public function testProcessGetters()
    {
        $this->factory->fake();

        $result = $this->factory->path(__DIR__)
            ->timeout(45.0)
            ->run($this->ls());

        $process = $result->process();

        $this->assertSame($this->ls(), $process->command());
        $this->assertSame(45.0, $process->timeout());
        $this->assertSame(__DIR__, $process->path());
    }

    public function testAssertRan()
    {
        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertRan(function ($process) {
            return $process->command() === 'ls';
        });

        $factory = $this->factory->assertRan('ls');

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertRanWithArray()
    {
        $this->factory->fake();

        $this->factory->run(['curl', 'https://google.com']);

        $this->factory->assertRan(['curl', 'https://google.com']);

        if (windows_os()) {
            $this->factory->assertRan('curl "https://google.com"');
        } else {
            $this->factory->assertRan("'curl' 'https://google.com'");
        }
    }

    public function testAssertRanMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertRan('nop');
    }

    public function testAssertRanInOrder()
    {
        $this->factory->fake();

        $this->factory->run('ls -l0');
        $this->factory->run('ls -l1');
        $this->factory->run('ls -l2');

        $this->factory->assertRanInOrder([
            fn ($process) => $process->command() === 'ls -l0',
            fn ($process) => $process->command() === 'ls -l1',
            fn ($process) => $process->command() === 'ls -l2',
        ]);

        $factory = $this->factory->assertRanInOrder(['ls -l0', 'ls -l1', 'ls -l2']);

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertRanInOrderWithArray()
    {
        $this->factory->fake();

        $this->factory->run(['ls', '-l0']);
        $this->factory->run(['ls', '-l1']);
        $this->factory->run(['ls', '-l2']);

        $this->factory->assertRanInOrder([
            ['ls', '-l0'],
            ['ls', '-l1'],
            ['ls', '-l2'],
        ]);
    }

    public function testAssertRanInOrderMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls -l0');
        $this->factory->run('ls -l1');
        $this->factory->run('ls -l2');

        $this->factory->assertRanInOrder(['ls -l0', 'ls -l2', 'ls -l1']);
    }

    public function testAssertNotRan()
    {
        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertNotRan(function ($process) {
            return $process->command() === 'sleep';
        });

        $factory = $this->factory->assertNotRan('sleep');

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertNotRanMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertNotRan('ls');
    }

    public function testAssertNothingRan()
    {
        $this->factory->fake();

        $factory = $this->factory->assertNothingRan();

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertNothingRanMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertNothingRan();
    }

    public function testAssertRanCount()
    {
        $this->factory->fake();

        $this->factory->assertRanCount(0);

        $this->factory->run('ls');

        $factory = $this->factory->assertRanCount(1);

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertRanCountMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertRanCount(3);
    }

    protected function ls()
    {
        return windows_os() ? 'dir' : 'ls';
    }
}
